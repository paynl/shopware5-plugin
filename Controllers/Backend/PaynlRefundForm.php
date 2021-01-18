<?php

use PaynlPayment\Models\Transaction;
use Shopware\Models\Order\Detail;
use Shopware\Models\Shop\Currency;

/**
 * Example:
 * https://github.com/shopwareLabs/SwagLightweightModule
 */
class Shopware_Controllers_Backend_PaynlRefundForm extends Enlight_Controller_Action implements \Shopware\Components\CSRFWhitelistAware
{
    private $logger;

    public function preDispatch()
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');
    }

    public function indexAction()
    {
        /** @var \PaynlPayment\Components\Api $paynlApi */
        $paynlApi = $this->get('paynl_payment.api');

        /** @var \PaynlPayment\Components\Config $paynlConfig */
        $paynlConfig = $this->get('paynl_payment.config');

        $paynlPaymentId = $this->request->getParam('paynlPaymentId');

        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getModelManager()->getRepository(Transaction\Transaction::class);

        /** @var Transaction\Transaction $transaction */
        $transaction = $transactionRepository->findOneBy(['paynlPaymentId' => $paynlPaymentId]);
        $order = $transaction->getOrder();

        $shop = $order->getShop();
        $paynlApi->setShop($shop);
        $paynlConfig->setShop($shop);

        if ($paynlConfig->get('allow_refunds') == 0) {
            $this->forward('disabled');
        }

        $messages = $this->request->getParam('messages');

        $customer = $transaction->getCustomer();
        $arrDetails = [];
        foreach ($order->getDetails() as $detail) {
            /** @var Detail $detail */
            $arrDetail = [
                'id' => $detail->getArticleId(),
                'name' => $detail->getArticleName(),
                'quantity' => $detail->getQuantity(),
                'price' => $detail->getPrice()
            ];

            array_push($arrDetails, $arrDetail);
        }

        $currencyRepository = $this->getModelManager()->getRepository(Currency::class);
        /** @var Currency $currencyObj */
        $currencyObj = $currencyRepository->findOneBy(['currency' => $order->getCurrency()]);

        try {
            $apiTransaction = $paynlApi->getTransaction($transaction->getTransactionId());

            return $this->view->assign([
                'customerName' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'orderNumber' => $order->getNumber(),
                'transactionId' => $order->getTransactionId(),
                'currency' => $transaction->getCurrency(),
                'currencyFactor' => $order->getCurrencyFactor(),
                'currencySymbol' => $currencyObj->getSymbol(),
                'orderAmount' => $transaction->getAmount(),
                'paidCurrencyAmount' => $apiTransaction->getCurrencyAmount(),
                'shippingAmount' => $order->getInvoiceShipping(),
                'details' => $arrDetails,
                'paynlPaymentId' => $paynlPaymentId,
                'paynlOrderId' => $transaction->getTransactionId(),
                'refundedCurrencyAmount' => $apiTransaction->getRefundedCurrencyAmount(),
                'availableForRefund' => $apiTransaction->getAmount() - $apiTransaction->getRefundedAmount(),
                'messages' => $messages
            ]);
        } catch (Throwable $e) {
            $timestamp = time();
            $message = 'Pay. Refund Incident ID: %s Please contact your administrator.';
            $messages[] = ['type' => 'danger', 'content' => sprintf($message, $timestamp)];
            $logMessage = sprintf(
                'PAY. Refund Incident ID: %s Error: %s in %s:%s Stack trace: %s',
                $timestamp,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            $this->log($logMessage);
            $this->view()->assign(['messages' => $messages]);
        }
    }

    public function disabledAction()
    {
        // empty action, only shows a message
    }

    public function refundAction()
    {
        $post = $this->request->getPost();

        $paynlPaymentId = $post['paynlPaymentId'];
        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getModelManager()->getRepository(Transaction\Transaction::class);

        /** @var Transaction\Transaction $transaction */
        $transaction = $transactionRepository->findOneBy(['paynlPaymentId' => $paynlPaymentId]);
        $shop = $transaction->getOrder()->getShop();

        $amount = $post['amount'];
        $description = $post['description'];
        $products = $post['product'];

        /** @var \PaynlPayment\Components\Api $paynlApi */
        $paynlApi = $this->get('paynl_payment.api');
        $paynlApi->setShop($shop);

        $messages = [];

        try {
            $refundResult = $paynlApi->refund($transaction, $amount, $description, $products);

            $messages[] = ['type' => 'success', 'content' => 'Refund successful (' . $refundResult->getData()['description'] . ')'];
        } catch (Throwable $e) {
            $timestamp = time();
            $message = 'Pay. Refund Incident ID: %s Please contact your administrator.';
            $messages[] = ['type' => 'danger', 'content' => sprintf($message, $timestamp)];
            $logMessage = sprintf(
                'PAY. Refund Incident ID: %s Error: %s in %s:%s Stack trace: %s',
                $timestamp,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            $this->log($logMessage);
        }

        $this->forward('index', null, null, ['paynlPaymentId' => $paynlPaymentId, 'messages' => $messages]);
    }

    public function getWhitelistedCSRFActions()
    {
        return ['index', 'refund', 'disabled'];
    }

    /**
     * @param mixed $message
     */
    private function log($message)
    {
        if (empty($this->logger)) {
            $this->logger = $this->container->get('pluginlogger');
        }

        $this->logger->addError($message);
    }
}
