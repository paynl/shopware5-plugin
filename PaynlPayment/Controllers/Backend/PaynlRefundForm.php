<?php

use PaynlPayment\Models\Transaction;
use Shopware\Models\Order\Detail;
use \Shopware\Models\Shop\Currency;

/**
 * Example:
 * https://github.com/shopwareLabs/SwagLightweightModule
 */
class Shopware_Controllers_Backend_PaynlRefundForm extends Enlight_Controller_Action implements \Shopware\Components\CSRFWhitelistAware
{

    public function preDispatch()
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');
    }

    public function indexAction()
    {
        /** @var \PaynlPayment\Components\Api $paynlApi */
        $paynlApi = $this->get('paynl_payment.api');

        $paynlPaymentId = $this->request->getParam('paynlPaymentId');
        $messages = $this->request->getParam('messages');

        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getModelManager()->getRepository(Transaction\Transaction::class);

        /** @var Transaction\Transaction $transaction */
        $transaction = $transactionRepository->findOneBy(['paynlPaymentId' => $paynlPaymentId]);
        $order = $transaction->getOrder();

        $shop = $order->getShop();
        $paynlApi->setShop($shop);

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

        $apiTransaction = $paynlApi->getTransaction($transaction->getTransactionId());

        $currencyRepository = $this->getModelManager()->getRepository(Currency::class);
        /** @var Currency $currencyObj */
        $currencyObj = $currencyRepository->findOneBy(['currency' => $order->getCurrency()]);


        return $this->view->assign([
            'customerName' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            'orderNumber' => $order->getNumber(),
            'currency' => $transaction->getCurrency(),
            'currencyFactor' => $order->getCurrencyFactor(),
            'currencySymbol' => $currencyObj->getSymbol(),
            'orderAmount' => $transaction->getAmount(),
            'shippingAmount' => $order->getInvoiceShipping(),
            'details' => $arrDetails,
            'paynlPaymentId' => $paynlPaymentId,
            'refundedAmount' => $apiTransaction->getRefundedAmount(),
            'availableForRefund' => $apiTransaction->getAmount()-$apiTransaction->getRefundedAmount(),
            'messages' => $messages
        ]);
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
            $paynlApi->refund($transaction, $amount, $description, $products);
            $messages[] = ['type' => 'success', 'content' => 'Refund successful'];
        } catch (\Exception $e) {
            $messages[] = ['type' => 'danger', 'content' => $e->getMessage()];
        }

        $this->forward('index', null, null, ['paynlPaymentId' => $paynlPaymentId, 'messages' => $messages]);
    }

    public function getWhitelistedCSRFActions()
    {
        return ['index', 'refund'];
    }
}