<?php

use Shopware\Components\CSRFWhitelistAware;
use PaynlPayment\Models\Transaction;
use Shopware\Models\Order;
use \PaynlPayment\Models\TransactionLog;


class Shopware_Controllers_Frontend_PaynlPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    public function indexAction()
    {
        if (substr($this->getPaymentShortName(), 0, 6) !== 'paynl_') {
            throw new Exception('Payment is not a Pay.nl Payment method');
        }

        $this->forward('redirect');
    }

    public function preDispatch()
    {
        parent::preDispatch();

        if ($this->Request()->get('action')) {
            $actionName = $this->Request()->get('action') . 'Action';
            if (!method_exists($this, $actionName)) {
                $this->notifyAction();
            }
        }
    }

    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }

    /**
     * Start the payment and redirect
     */
    public function redirectAction()
    {
        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getTransactionRepository();


        $signature = $this->persistBasket();

        /** @var \PaynlPayment\Components\Api $paynlApi */
        $paynlApi = $this->get('paynl_payment.api');
        try {
            $result = $paynlApi->startPayment($this, $signature);
            if ($config->placeOrderOnStart()) {
                /** @var Transaction\Transaction $transaction */
                $transaction = $transactionRepository->findOneBy(['transactionId' => $result->getTransactionId()]);

                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PENDING, true);
            }
            if ($result->getRedirectUrl()) $this->redirect($result->getRedirectUrl());
        } catch (Exception $e) {
            // todo error handling
        }
    }

    public function notifyAction()
    {
        $action = $this->request->isPost() ? $this->request->getPost('action') : $this->request->get('action');
        $transactionId = $this->request->isPost() ? $this->request->getPost('order_id') : $this->request->get('order_id');

        if ($action == 'pending') die('TRUE| Ignoring pending');

        try {
            $result = $this->processPayment($transactionId, true);
            die('TRUE|' . $result);
        } catch (Exception $e) {
            die('FALSE|' . $e->getMessage());
        }
    }

    public function returnAction()
    {
        $transactionId = $this->request->get('orderId');
        $this->processPayment($transactionId, false);
    }

    private function processPayment($transactionId, $isExchange = false)
    {
        $successUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $transactionId]) . '?utm_nooverride=1';
        $cancelUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'confirm']);

        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        /** @var \PaynlPayment\Components\Basket $basketService */
        $basketService = $this->container->get('paynl_payment.basket');

        /** @var \PaynlPayment\Components\Order $orderService */
        $orderService = $this->container->get('paynl_payment.order');

        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getTransactionRepository();
        /** @var Transaction\Transaction $transaction */
        $transaction = $transactionRepository->findOneBy(['transactionId' => $transactionId]);

        $canceled = false;
        $shouldCreate = false;

        try {
            // status en amount ophalen.
            $config->loginSDK();
            $apiTransaction = \Paynl\Transaction::get($transactionId);
            $order = $transaction->getOrder();
            if (!empty($order) && $order->getPaymentStatus()->getId() == Transaction\Transaction::STATUS_NEEDS_REVIEW) {
                // if order status is 'in review', only handle manual declined or paid
                $transactionData = $apiTransaction->getData();
                if ($transactionData['paymentDetails']['state'] != -64 && !$apiTransaction->isPaid()) {
                    throw new Exception('Invalid status for \'needs review\' Only manual declined and paid are handled', 999);
                } elseif ($transactionData['paymentDetails']['state'] == -64) {
                    if(!$transaction->isDeclinedMailSent() && $config->sendTransactionDeclinedMail()){
                        $orderService->sendDeclinedMail($order);
                        $transaction->setIsDeclinedMailSent(true);
                        $transactionRepository->save($transaction);
                    }
                }
            }
            if ($apiTransaction->isBeingVerified()) {
                $shouldCreate = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_NEEDS_REVIEW, $shouldCreate);
            } elseif ($apiTransaction->isPending() && !$isExchange) {
                $shouldCreate = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PENDING, $shouldCreate);
            } elseif ($apiTransaction->isRefunded()) {
                $shouldCreate = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_REFUND, $shouldCreate);
            } elseif ($apiTransaction->isAuthorized()) {
                $shouldCreate = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_AUTHORIZED, $shouldCreate);
            } elseif ($apiTransaction->isPaid()) {
                $shouldCreate = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PAID, $shouldCreate);
            } elseif ($apiTransaction->isCanceled()) {
                $canceled = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_CANCEL, $shouldCreate);
            }
        } catch (Exception $e) {
            if ($isExchange && $e->getCode() == 999) {
                return $e->getMessage();
            }
        }
        if ($transaction->getOrder() && !$transaction->isStockMailSent()) {
            $isStockMailSent = $orderService->checkStockAndMail($transaction->getOrder());
            // make sure the email is only sent once
            $transaction->setIsStockMailSent($isStockMailSent);
            $transactionRepository->save($transaction);
        }

        if (!$isExchange) {
            if ($canceled) {
                if ($config->placeOrderOnStart()) {
                    // basket moet terug worden geplaatst
                    $basketService->restoreBasket($transaction);
                }
                return $this->redirect($cancelUrl);
            } else {
                $this->fixSession($transaction);
                return $this->redirect($successUrl);
            }
        }

        if ($transaction->getOrder()) {
            return sprintf("Status updated to: %s (%s) orderNumber: %s",
                $transaction->getStatus()->getName(),
                $transaction->getStatus()->getId(),
                $transaction->getOrder()->getNumber()
            );
        } else {
            if ($shouldCreate) {
                throw new \Exception('Order should have been created, but an error has occurred');
            }
            return "No action, order was not created";
        }
    }

    private function fixSession(Transaction\Transaction $transaction)
    {
        // remove the basket
        Shopware()->Modules()->Basket()->clearBasket();
        $sOrderVariables = Shopware()->Session()->offsetGet('sOrderVariables');

        $sOrderVariables['sOrderNumber'] = $transaction->getOrder()->getNumber();
        Shopware()->Session()->offsetSet('sOrderVariables', $sOrderVariables);
    }

    private function updateStatus(Transaction\Transaction $transaction, $status, $shouldCreate = false)
    {
        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        if (!is_null($transaction->getStatus()) && $transaction->getStatus()->getId() == $status) {
            throw new Exception('Already processed', 999);
        }
        // order exists
        if ($transaction->getOrder()) {
            $order = $transaction->getOrder();
            if ($order->getPaymentStatus()->getId() === Transaction\Transaction::STATUS_PAID) {
                throw new Exception('Not updating, order already paid', 999);
            }
            if ($status === Transaction\Transaction::STATUS_PAID && $transaction->getAmount() != $order->getInvoiceAmount()) {
                $status = Transaction\Transaction::STATUS_NEEDS_REVIEW;
                $comment = $order->getInternalComment();
                $comment .= "Paid amount does not match order total.\n Paid amount: {$transaction->getAmount()} Order total: {$order->getInvoiceAmount()}\n";
                $order->setInternalComment($comment);
                $this->getModelManager()->persist($order);
            }

            /** @var \PaynlPayment\Components\Order $orderService */
            $orderService = $this->container->get('paynl_payment.order');
            $transactionLog = new TransactionLog\TransactionLog();

            $transactionLog->setTransaction($transaction);
            $transactionLog->setStatusBefore($transaction->getStatus());
            $transactionLog->setStatusAfterById($status);
            $this->getModelManager()->persist($transactionLog);

            $stockBefore = $orderService->getStock($order);

            $this->savePaymentStatus($transaction->getPaynlPaymentId(), $transaction->getTransactionId(), $status, $config->sendStatusMail());
            $transaction->setStatusById($status);
            $this->getTransactionRepository()->save($transaction);

            if (!$transaction->isOrderMailSent()) {
                $this->getModelManager()->flush();
                // save changes to database to make sure the order is updated
                $sOrder = Shopware()->Modules()->Order();

                $variables = $transaction->getOrderMailVariables();
                // we need to set the user email ourself
                $sOrder->sUserData['additional'] = $variables['additional'];

                $sOrder->sendMail($variables);
            }
            if ($status == Transaction\Transaction::STATUS_CANCEL) {
                $orderService->restockOrder($transaction);
            }
            if ($status == Transaction\Transaction::STATUS_PAID && $transaction->isRestocked()) {
                // we need to uncancel the order (decrease stock)
                $orderService->unCancelOrder($transaction);
            }

            $stockAfter = $orderService->getStock($order);

            foreach ($stockAfter as $row) {
                /** @var \Shopware\Models\Article\Detail $articleDetail */
                $articleDetail = $row['articleDetail'];
                $stock = $row['stock'];
                $before = array_filter($stockBefore,
                    function ($var) use ($articleDetail) {
                        return $articleDetail->getId() == $var['articleDetail']->getId();
                    });
                if (!empty($before)) {
                    $before = array_pop($before);
                    $logDetail = new TransactionLog\Detail();
                    $logDetail->setArticleDetail($articleDetail);
                    $logDetail->setTransactionLog($transactionLog);
                    $logDetail->setStockBefore($before['stock']);
                    $logDetail->setStockAfter($stock);
                    $this->getModelManager()->persist($logDetail);
                }
            }

            $this->getModelManager()->flush();
            return true;
        }
        // order does not exist
        if (!$shouldCreate) {
            return false;
        }
        // Exchange has a new session, so we need to add the variables used for order creation
        if (!$this->get('session')->sUserId) {
            $this->get('session')->sUserId = $transaction->getCustomer()->getId();
            $this->get('session')->sComment = $transaction->getSComment();
            $this->get('session')->sDispatch = $transaction->getSDispatch();
        }


        $transaction->getSDispatch();
        $basket = $this->loadBasketFromSignature($transaction->getSignature());
        $this->verifyBasketSignature($transaction->getSignature(), $basket);

        $orderNumber = $this->saveOrder($transaction->getPaynlPaymentId(), $transaction->getTransactionId(), $status);
        $order = $this->getOrder($orderNumber);
        $transaction->setOrder($order);
        $transaction->setStatusById($status);

        $this->getTransactionRepository()->save($transaction);
        $this->getModelManager()->flush();

        return true;
    }


    /**
     * @return Transaction\Repository
     */
    private function getTransactionRepository()
    {
        return $this->container->get('models')->getRepository(Transaction\Transaction::class);
    }

    /**
     * @param $orderNumber
     * @return Order\Order|null
     */
    private function getOrder($orderNumber)
    {
        /** @var Order\Repository $repository */
        $repository = $this->container->get('models')->getRepository(Order\Order::class);

        return $repository->findOneBy(['number' => $orderNumber]);
    }
}