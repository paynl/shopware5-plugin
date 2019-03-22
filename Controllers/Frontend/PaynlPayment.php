<?php

use Shopware\Components\CSRFWhitelistAware;
use PaynlPayment\Models\Transaction;
use Shopware\Models\Order;

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
            if($config->placeOrderOnStart()){
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
        $action = $this->request->isPost()?$this->request->getPost('action'):$this->request->get('action');
        $transactionId = $this->request->isPost()?$this->request->getPost('order_id'):$this->request->get('order_id');

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
        $successUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID'=>$transactionId]) . '?utm_nooverride=1';
        $cancelUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'confirm']);

        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        /** @var \PaynlPayment\Components\Basket $basketService */
        $basketService = $this->container->get('paynl_payment.basket');

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

            if ($apiTransaction->isBeingVerified()) {
                $shouldCreate = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PENDING, $shouldCreate);
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
        if (!$isExchange) {
            if ($canceled) {
                if($config->placeOrderOnStart()){
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
                $transaction->getOrder()->getPaymentStatus()->getName(),
                $transaction->getOrder()->getPaymentStatus()->getId(),
                $transaction->getOrder()->getNumber()
            );
        } else {
            if($shouldCreate){
                throw new \Exception('Order should have been created, but an error has occurred');
            }
            return "No action, order was not created";
        }
    }
    private function fixSession(Transaction\Transaction $transaction){
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
            /** @var \PaynlPayment\Components\Order $orderService */
            $orderService = $this->container->get('paynl_payment.order');
            $this->savePaymentStatus($transaction->getPaynlPaymentId(), $transaction->getTransactionId(), $status, $config->sendStatusMail());
            $transaction->setStatusById($status);
            $this->getTransactionRepository()->save($transaction);

            if(!$transaction->isOrderMailSent()){
                $sOrder = Shopware()->Modules()->Order();
                $sOrder->sendMail($transaction->getOrderMailVariables());               
            }
            if($status == Transaction\Transaction::STATUS_CANCEL){
                $orderService->restockOrder($transaction);
            }
            if($status == Transaction\Transaction::STATUS_PAID && $transaction->isRestocked()){
                // we need to uncancel the order (decrease stock)
                $orderService->unCancelOrder($transaction);
            }
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