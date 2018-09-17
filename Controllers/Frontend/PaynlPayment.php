<?php

use Shopware\Components\CSRFWhitelistAware;
use PaynlPayment\Models\Transaction;
use Shopware\Models\Payment;
use Shopware\Models\Customer;
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
        $signature = $this->persistBasket();

        /** @var \PaynlPayment\Components\Api $paynlApi */
        $paynlApi = $this->get('paynl_payment.api');
        try {
            $result = $paynlApi->startPayment($this, $signature);
            if ($result->getRedirectUrl()) $this->redirect($result->getRedirectUrl());
        } catch (Exception $e) {
            // todo error handling
        }
    }

    public function notifyAction()
    {
        $action = $this->request->get('action');
        if ($action == 'pending') die('TRUE| Ignoring pending');

        try {
            $transactionId = $this->request->get('order_id');
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
        $successUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'finish']) . '?utm_nooverride=1';
        $cancelUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'confirm']);

        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getTransactionRepository();
        /** @var Transaction\Transaction $transaction */
        $transaction = $transactionRepository->findOneBy(['transactionId' => $transactionId]);

        $canceled = false;

        try {
            // status en amount ophalen.
            $config->loginSDK();
            $apiTransaction = \Paynl\Transaction::get($transactionId);

            if ($apiTransaction->isBeingVerified()) {
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PENDING, true);
            } elseif ($apiTransaction->isPending() && !$isExchange) {
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PENDING, true);
            } elseif ($apiTransaction->isRefunded()) {
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_REFUND, true);
            } elseif ($apiTransaction->isAuthorized()) {
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_AUTHORIZED, true);
            } elseif ($apiTransaction->isPaid()) {
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_PAID, true);
            } elseif ($apiTransaction->isCanceled()) {
                $canceled = true;
                $this->updateStatus($transaction, Transaction\Transaction::STATUS_CANCEL, false);
            }
        } catch (Exception $e) {
            if ($isExchange && $e->getCode() == 999) {
                return $e->getMessage();
            }
        }
        if (!$isExchange) {
            if ($canceled) {
                return $this->redirect($cancelUrl);
            } else {
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
            return "No action, order was not created";
        }
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
            $this->savePaymentStatus($transaction->getPaynlPaymentId(), $transaction->getTransactionId(), $status, $config->sendStatusMail());
            $transaction->setStatusById($status);
            $this->getTransactionRepository()->save($transaction);

            return true;
        }
        // order does not exist
        if (!$shouldCreate) {
            return false;
        }
        // if no userId is in the session, we need to put it there in order to verify the signature
        if (!$this->get('session')->sUserId) {
            $this->get('session')->sUserId = $transaction->getCustomer()->getId();
        }

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

    private function getOrder($orderNumber)
    {
        /** @var Order\Repository $repository */
        $repository = $this->container->get('models')->getRepository(Order\Order::class);

        return $repository->findOneBy(['number' => $orderNumber]);
    }
}