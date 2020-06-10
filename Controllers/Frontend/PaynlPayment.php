<?php

use Shopware\Components\CSRFWhitelistAware;
use PaynlPayment\Models\Transaction;
use Shopware\Models\Order;

class Shopware_Controllers_Frontend_PaynlPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    private $logger;

    /**
     * @param $message
     */
    private function log($message)
    {
        if (empty($this->logger)) {
            $this->logger = $this->container->get('pluginlogger');
        }

        $this->logger->addError($message);
    }

    public function indexAction()
    {
        if (substr($this->getPaymentShortName(), 0, 6) !== 'paynl_') {
            throw new \Exception('Payment is not a PAY. Payment method');
        }

        $this->forward('redirect');
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

            if ($result->getRedirectUrl()) {
                $this->redirect($result->getRedirectUrl());
            }
        } catch (Throwable $e) {
            $this->log(sprintf('PAY.: Could not start payment. Error: %s', $e->getMessage()));
            $this->View()->assign('message', $e->getMessage());
        }
    }

    // Pay. uses `action` GET parameter for its notifications request
    // Shopware5 interprets `action` parameter as controller method name and template name
    // To handle this case we use PHP magic __call() method
    public function __call($name, $value = null)
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender(true);
        $message = $this->notify();
        $this->Response()
            ->setBody($message)
            ->setHttpResponseCode(200);
    }

    private function notify()
    {
        $action =
            $this->request->isPost() ? $this->request->getPost('action') : $this->request->get('action');
        $transactionId =
            $this->request->isPost() ? $this->request->getPost('order_id') : $this->request->get('order_id');

        if ($action == 'pending') {
            return 'TRUE| Ignoring pending';
        }

        try {
            $result = $this->processPayment($transactionId, true);

            return sprintf('TRUE|%s', $result);
        } catch (Throwable $e) {
            $logMessage = sprintf('PAY.: Could not process payment. Error: %s', $e->getMessage());
            $this->log($logMessage);

            return sprintf('FALSE|%s', $e->getMessage());
        }
    }

    public function returnAction()
    {
        $transactionId = $this->request->get('orderId');
        $this->processPayment($transactionId, false);
    }

    /**
     * @param $transactionId
     * @param bool $isExchange
     * @return string|void
     * @throws Exception
     */
    private function processPayment($transactionId, $isExchange = false)
    {
        $successUrl = sprintf($this->Front()->Router()->assemble([
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $transactionId
        ]), '?utm_nooverride=1');

        $cancelUrl = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'confirm']);

        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getTransactionRepository();
        /** @var Transaction\Transaction $transaction */
        $transaction = $transactionRepository->findOneBy(['transactionId' => $transactionId]);

        $canceled = false;
        $shouldCreate = false;

        try {
            if (empty($transaction)) {
                throw new Exception('Could not find transaction', 999);
            }

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
        } catch (Throwable $e) {
            if ($isExchange && $e->getCode() == 999) {
                return $e->getMessage();
            }
        }

        if (!$isExchange) {
            if ($canceled) {
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
            if ($shouldCreate) {
                throw new Exception('Order should have been created, but an error has occurred');
            }

            return "No action, order was not created";
        }
    }

    /**
     * @param Transaction\Transaction $transaction
     */
    private function fixSession(Transaction\Transaction $transaction)
    {
        // remove the basket
        Shopware()->Modules()->Basket()->clearBasket();
        $sOrderVariables = Shopware()->Session()->offsetGet('sOrderVariables');
        if (!is_null($transaction->getOrder())) {
            $sOrderVariables['sOrderNumber'] = $transaction->getOrder()->getNumber();
        } else {
            // No order is saved with the transaction (yet)
            // try to load the order by transactionId (other way around)
            $paymentId = $transaction->getPaynlPaymentId();
            $orderRepository = Shopware()->Models()->getRepository(Order\Order::class);
            /** @var Order\Order $order */
            $order = $orderRepository->findOneBy([
                'transactionId' => $paymentId,
                'temporaryId' => $transaction->getTransactionId()]);

            // if this fails, i have no way to get the order, it's simply not yet created.
            if (!is_null($order)) {
                $sOrderVariables['sOrderNumber'] = $order->getNumber();
            }
        }

        Shopware()->Session()->offsetSet('sOrderVariables', $sOrderVariables);
    }

    /**
     * @param Transaction\Transaction $transaction
     * @param $status
     * @param bool $shouldCreate
     * @return bool
     * @throws Exception
     */
    private function updateStatus(Transaction\Transaction $transaction, $status, $shouldCreate = false)
    {
        /** @var \PaynlPayment\Components\Config $config */
        $config = $this->container->get('paynl_payment.config');

        if (!is_null($transaction->getStatus()) && $transaction->getStatus()->getId() == $status) {
            throw new Exception('Already processed', 999);
        }
        // order exists
        if ($transaction->getOrder()) {
            $this->savePaymentStatus(
                $transaction->getPaynlPaymentId(),
                $transaction->getTransactionId(),
                $status,
                $config->sendStatusMail()
            );

            $transaction->setStatusById($status);
            $this->getTransactionRepository()->save($transaction);

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
