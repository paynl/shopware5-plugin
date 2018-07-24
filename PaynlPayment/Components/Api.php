<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 29-6-18
 * Time: 10:30
 */

namespace PaynlPayment\Components;


use League\Flysystem\Exception;
use PaynlPayment\Models\Transaction;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\NumberRangeIncrementerInterface;
use Shopware\Components\Routing\Router;
use Shopware\Models\Customer;
use Shopware\Models\Payment;

class Api
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Transaction\Repository
     */
    private $transactionRepository;

    /**
     * @var Customer\Repository
     */
    private $customerRepository;

    /**
     * @var Payment\Repository
     */
    private $paymentRepository;

    /**
     * @var NumberRangeIncrementerInterface
     */
    private $numberIncrementer;

    /**
     * @var Router
     */
    private $router;

    public function __construct(
        Config $config,
        ModelManager $modelManager,
        Router $router,
        NumberRangeIncrementerInterface $numberIncrementer
    )
    {
        $this->config = $config;
        $this->modelManager = $modelManager;
        $this->router = $router;
        $this->numberIncrementer = $numberIncrementer;

        $this->transactionRepository = $modelManager->getRepository(Transaction\Transaction::class);
        $this->customerRepository = $modelManager->getRepository(Customer\Customer::class);
        $this->paymentRepository = $modelManager->getRepository(Payment\Payment::class);
    }

    public function startPayment(\Shopware_Controllers_Frontend_PaynlPayment $controller, $signature)
    {

        $payment_name = $controller->getPaymentShortName();
        if (substr($payment_name, 0, 6) !== 'paynl_') {
            throw new Exception('Payment is not a Pay.nl Payment method');
        }

        $paymentId = $this->numberIncrementer->increment('paynl_payment_id');

        $paymentOptionId = explode('_', $payment_name);
        $paymentOptionId = $paymentOptionId[1];

        $arrUser = $controller->getUser();
        /** @var Customer\Customer $customer */
        $customer = $this->customerRepository->find($arrUser['additional']['user']['id']);

        $basket = $controller->getBasket();
        $amount = $controller->getAmount();
        $currency = $controller->getCurrencyShortName();

        /** @var Payment\Payment $payment */
        $payment = $this->paymentRepository->findOneBy(['name' => $payment_name]);

        $transaction = $this->transactionRepository->createNew($customer, $paymentId, $payment, $signature, $amount, $currency);
        $arrStartData = $this->getStartData($amount, $paymentOptionId, $currency, $paymentId, $signature, $arrUser, $basket);

        try {
            $this->config->loginSDK();
            $result = \Paynl\Transaction::start($arrStartData);
            $transaction->setTransactionId($result->getTransactionId());
            $this->transactionRepository->save($transaction);

            return $result;
        } catch (\Exception $e) {
            $transaction->addException($e);
            $this->transactionRepository->save($transaction);
            throw $e;
        }
    }

    private function getStartData($amount, $paymentOptionId, $currency, $paymentId, $signature, $arrUser, $basket)
    {
        $arrStartData = [
            // Basic data
            'amount' => $amount,
            'paymentMethod' => $paymentOptionId,
            'currency' => $currency,
            'description' => $paymentId,
            'orderNumber' => $paymentId,
            'extra1' => $signature,
            'testmode' => $this->config->testMode(),

            // Urls
            'returnUrl' => $this->router->assemble(['controller' => 'PaynlPayment', 'action' => 'return', 'forceSecure' => true]),
            'exchangeUrl' => $this->router->assemble(['controller' => 'PaynlPayment', 'action' => 'notify', 'forceSecure' => true, 'appendSession' => true]),

            // Products
            'products' => $this->getProducts($basket),
        ];

        $addresses = $this->formatAddresses($arrUser);
        $arrStartData = array_merge($arrStartData, $addresses);

        return $arrStartData;
    }

    /**
     * @param array $basket
     * @return array Product formatted for the SDK
     */
    private function getProducts($basket)
    {
        $products = [];

        foreach ($basket['content'] as $product) {
            array_push($products, [
                'id' => $product['articleID'],
                'name' => $product['articlename'],
                'price' => $this->formatFloat($product['price']),
                'vatPercentage' => $this->formatFloat($product['tax_rate']),
                'qty' => $product['quantity'],
                'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE
            ]);
        }

        return $products;
    }

    /**
     * For some reason some numbers are pre-formatted using a , instead of a . for decimals.
     * This function fixes that, so a real float is used.
     *
     * @param $input
     * @return float
     */
    private function formatFloat($input)
    {
        return floatval(str_replace(',', '.', str_replace('.', '', $input)));
    }

    /**
     * @param $arrUser
     * @return array Addresses and enduser formatted for the sdk
     */
    private function formatAddresses($arrUser)
    {
        $arrShippingAddress = \Paynl\Helper::splitAddress($arrUser['shippingaddress']['street']);
        $arrBillingAddress = \Paynl\Helper::splitAddress($arrUser['billingaddress']['street']);

        $femaleSalutations = $this->config->femaleSalutations();
        $genderShipping = 'M';
        $genderBilling = 'M';

        if(in_array(trim($arrUser['shippingaddress']['salutation']), $femaleSalutations)) $genderShipping = 'F';
        if(in_array(trim($arrUser['billingaddress']['salutation']), $femaleSalutations)) $genderBilling = 'F';

        $arrResult = [
            'enduser' => [
                'initials' => $arrUser['additional']['user']['firstname'],
                'lastName' => $arrUser['additional']['user']['lastname'],
                'emailAddress' => $arrUser['additional']['user']['email'],
                'customerReference' => $arrUser['additional']['user']['customernumber'],
                'gender' => $genderShipping
            ],
            'address' => [
                'streetName' => $arrShippingAddress[0],
                'houseNumber' => $arrShippingAddress[1],
                'zipCode' => $arrUser['shippingaddress']['zipcode'],
                'city' => $arrUser['shippingaddress']['city'],
                'country' => $arrUser['additional']['countryShipping']['countryiso']
            ],
            'invoiceAddress' => [
                'initials' => $arrUser['billingaddress']['firstname'],
                'lastName' => $arrUser['billingaddress']['lastname'],
                'streetName' => $arrBillingAddress[0],
                'houseNumber' => $arrBillingAddress[1],
                'zipCode' => $arrUser['billingaddress']['zipcode'],
                'city' => $arrUser['billingaddress']['city'],
                'country' => $arrUser['additional']['country']['countryiso'],
                'gender' => $genderBilling
            ]
        ];

        return $arrResult;
    }

}