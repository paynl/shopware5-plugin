<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 29-6-18
 * Time: 10:30
 */

namespace PaynlPayment\Components;


use PaynlPayment\Models\Transaction;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\NumberRangeIncrementerInterface;
use Shopware\Components\Routing\Router;
use Shopware\Models\Customer;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment;
use Shopware\Models\Article;
use Shopware\Models\Shop\Shop;

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

    public function setShop(Shop $shop)
    {
        $this->config->setShop($shop);
    }

   /**
    * @param \Shopware_Controllers_Frontend_PaynlPayment $controller
    * @param $signature
    * @return \Paynl\Result\Transaction\Start
    * @throws \Exception
    */
    public function startPayment(\Shopware_Controllers_Frontend_PaynlPayment $controller, $signature)
    {
        $payment_name = $controller->getPaymentShortName();
        if (substr($payment_name, 0, 6) !== 'paynl_') {
            throw new \Exception('Payment is not a PAY. Payment method. Name: '. $payment_name);
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

        $sComment = Shopware()->Session()->sComment;
        $sDispatch = Shopware()->Session()->sDispatch;

        $transaction->setSComment($sComment);
        $transaction->setSDispatch($sDispatch);

        $arrStartData = $this->getStartData($amount, $paymentOptionId, $currency, $paymentId, $signature, $arrUser, $basket);
        $arrStartData['object'] = 'shopware 3.2';

        try {
            $this->config->loginSDK();
            $result = \Paynl\Transaction::start($arrStartData);
            $transaction->setTransactionId($result->getTransactionId());
            $this->transactionRepository->save($transaction);

            return $result;
        } catch (\Exception $objException) {
            $transaction->addException($objException);
            $this->transactionRepository->save($transaction);
            throw $objException;
        }
    }

  /**
   * @param $transactionId
   * @return \Paynl\Result\Transaction\Status
   * @throws \Paynl\Error\Api
   * @throws \Paynl\Error\Error
   */
    public function getTransaction($transactionId)
    {
        $this->config->loginSDK();
        return \Paynl\Transaction::status($transactionId);
    }

  /**
   * @param Transaction\Transaction $transaction
   * @param $amount
   * @param string $description
   * @param array $products
   * @return \Paynl\Result\Transaction\Refund
   * @throws \Doctrine\ORM\ORMException
   * @throws \Doctrine\ORM\OptimisticLockException
   * @throws \Doctrine\ORM\TransactionRequiredException
   * @throws \Paynl\Error\Api
   * @throws \Paynl\Error\Error
   */
    public function refund(Transaction\Transaction $transaction, $amount, $description = '', $products = [])
    {
        if(!$this->config->isRefundAllowed()){
            throw new \Exception('Cannot refund, because refund is disabled');
        }
        $this->config->loginSDK();
        $transactionId = $transaction->getTransactionId();
        $refundResult = \Paynl\Transaction::refund($transactionId, $amount, $description);

        $order = $transaction->getOrder();

        $refundedStatus = $this->modelManager->find(Status::class, Transaction\Transaction::STATUS_REFUND);

        $order->setPaymentStatus($refundedStatus);

        $this->modelManager->persist($order);

        $articleDetailRepository = $this->modelManager->getRepository(Article\Detail::class);

        if (!empty($products)) {
            foreach ($products as $id => $product) {
                if ($product['restock']) {
                    // Restock it
                    /** @var Article\Detail $articleDetail */
                    $articleDetail = $articleDetailRepository->findOneBy(['articleId' => $id]);
                    if (is_null($articleDetail)) continue;
                    $newStock = $articleDetail->getInStock() + $product['qty'];
                    $articleDetail->setInStock($newStock);
                    $this->modelManager->persist($articleDetail);
                }
            }
        }
        $this->modelManager->flush();
        return $refundResult;
    }

  /**
   * @param $amount
   * @param $paymentOptionId
   * @param $currency
   * @param $paymentId
   * @param $signature
   * @param $arrUser
   * @param $basket
   * @return array
   */
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
            'exchangeUrl' => $this->router->assemble(['controller' => 'PaynlPayment', 'action' => 'notify', 'forceSecure' => true]),

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

        if ($basket['sShippingcostsWithTax'] > 0) {
            $shipping = [
                'id' => 'shipping',
                'name' => 'Shipping',
                'price' => $basket['sShippingcostsWithTax'],
                'vatPercentage' => $basket['sShippingcostsTax'],
                'qty' => 1,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            ];
            array_push($products, $shipping);
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
        $femaleSalutations = $this->config->femaleSalutations();
        $gender = 'M';

        if (in_array(trim($arrUser['shippingaddress']['salutation']), $femaleSalutations)) $gender = 'F';

        $arrResult = [
            'enduser' => [
                'initials' => $arrUser['additional']['user']['firstname'],
                'lastName' => $arrUser['additional']['user']['lastname'],
                'emailAddress' => $arrUser['additional']['user']['email'],
                'customerReference' => $arrUser['additional']['user']['customernumber'],
                'gender' => $gender,
                'phoneNumber' => isset($arrUser['billingaddress']['phone']) ? $arrUser['billingaddress']['phone'] : '',
            ],
            'address' => $this->getShippingAddress($arrUser),
            'invoiceAddress' => $this->getInvoiceAddress($arrUser)
        ];

        if (isset($arrUser['additional']['user']['birthday']) && !empty($arrUser['additional']['user']['birthday'])) {
          $arrResult['enduser']['birthDate'] = $arrUser['additional']['user']['birthday'];
        }

        return $arrResult;
    }

  /**
   * @param $arrUser
   * @return array
   */
    private function getShippingAddress($arrUser)
    {
        $street = '';
        $houseNumber = '';
        $houseNumberExtension = '';

        if(!$this->config->useAdditionalAddressFields()){
            $arrShippingAddress = \Paynl\Helper::splitAddress($arrUser['shippingaddress']['street']);
            if(isset($arrShippingAddress[0])) $street = $arrShippingAddress[0];
            if(isset($arrShippingAddress[1])) $houseNumber = $arrShippingAddress[1];
        } else {
            $street = $arrUser['shippingaddress']['street'];
            $houseNumber = $arrUser['shippingaddress']['additionalAddressLine1'];
            $houseNumberExtension = $arrUser['shippingaddress']['additionalAddressLine2'];
        }

        return ['streetName' => $street,
                'houseNumber' => $houseNumber,
                'houseNumberExtension' => $houseNumberExtension,
                'zipCode' => $arrUser['shippingaddress']['zipcode'],
                'city' => $arrUser['shippingaddress']['city'],
                'country' => $arrUser['additional']['countryShipping']['countryiso']
            ];
    }

  /**
   * @param $arrUser
   * @return array
   */
    private function getInvoiceAddress($arrUser)
    {
        $street = '';
        $houseNumber = '';
        $houseNumberExtension = '';

        if(!$this->config->useAdditionalAddressFields()){
            $arrAddress = \Paynl\Helper::splitAddress($arrUser['billingaddress']['street']);
            if(isset($arrAddress[0])) $street = $arrAddress[0];
            if(isset($arrAddress[1])) $houseNumber = $arrAddress[1];
        } else {
            $street = $arrUser['billingaddress']['street'];
            $houseNumber = $arrUser['billingaddress']['additionalAddressLine1'];
            $houseNumberExtension = $arrUser['billingaddress']['additionalAddressLine2'];
        }

        $gender = 'M';
        $femaleSalutations = $this->config->femaleSalutations();
        if (in_array(trim($arrUser['billingaddress']['salutation']), $femaleSalutations)) $gender = 'F';

        return  [
            'initials' => $arrUser['billingaddress']['firstname'],
            'lastName' => $arrUser['billingaddress']['lastname'],
            'streetName' => $street,
            'houseNumber' => $houseNumber,
            'houseNumberExtension' => $houseNumberExtension,
            'zipCode' => $arrUser['billingaddress']['zipcode'],
            'city' => $arrUser['billingaddress']['city'],
            'country' => $arrUser['additional']['country']['countryiso'],
            'gender' => $gender
        ];
    }
}