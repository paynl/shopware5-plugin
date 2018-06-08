<?php

/**
 * Description of PaynlPayment
 *
 * @author andy
 */
class Shopware_Controllers_Frontend_PaymentPaynl extends Shopware_Controllers_Frontend_Payment implements \Shopware\Components\CSRFWhitelistAware
{

    const STATUS_PENDING = 17;
    const STATUS_CANCEL = 35;
    const STATUS_PAID = 12;
    const STATUS_PARTIAL_PAID = 11;

    const CONFIRM_BEFORE_PAYMENT = array(
        136,// overboeking
        577, // sofort (digital services)
        559, // sofort (e-commerce)
        595, // sofort (high-risk)
    );

    protected $config;

    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }


    public function preDispatch()
    {
        $shop = false;
        if ($this->container->has('shop')) {
            $shop = $this->container->get('shop');
        }

        if (!$shop) {
            $shop = $this->container->get('models')->getRepository(Shopware\Models\Shop\Shop::class)->getActiveDefault();
        }

        $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName('PaymentPayNL', $shop);
        $this->config = $config;

        if ($this->Request()->get('action')) {
            $actionName = $this->Request()->get('action') . 'Action';
            if (!method_exists($this, $actionName)) {
                $this->notifyAction();
            }
        }
    }

    public function notifyAction()
    {
        $transactionId = $this->Request()->get('order_id');

        $serviceId = $this->config['serviceId'];
        $apiToken = $this->config['apiToken'];

        \Paynl\Config::setApiToken($apiToken);
        \Paynl\Config::setServiceId($serviceId);

        $transaction = \Paynl\Transaction::get($transactionId);


        if ($transaction->isPending()) {
            die('TRUE| Ignoring PENDING');
        }

        $strStatus = 'Unknown (nothing updated)';

        $extraMessage = '';

        if ($transaction->isPaid()) {
            $strStatus = "PAID";
            $status = self::STATUS_PAID;
            $amount = (int)round($this->getAmount());
            $paidAmount = (int)round($transaction->getPaidCurrencyAmount());

            if ($paidAmount != $amount) {
                $status = self::STATUS_PARTIAL_PAID;

                $extraMessage = " Amount: {$amount} Paidamount: {$paidAmount}";
            }
            $this->saveOrder($transactionId, $transactionId, $status);

        } elseif ($transaction->isCanceled()) {
            $strStatus = "CANCELED";
            // only update if the order exists, if it doesn't, don't touch it because we want to keep the basket alive
            if ($this->isOrderCreated($transactionId)) {
                $this->saveOrder($transactionId, $transactionId, self::STATUS_CANCEL);
            }
        } elseif ($transaction->isBeingVerified()) {
            // Save the order to prevent the session from expiring
            $strStatus = "VERIFY";
            $this->saveOrder($transactionId, $transactionId, self::STATUS_PENDING);
        }

        die('TRUE| Status updated to ' . $strStatus . $extraMessage);
    }

    public function indexAction()
    {
        $this->redirect(array('action' => 'direct', 'forceSecure' => true));
    }

    public function directAction()
    {
        $router = $this->Front()->Router();

        $serviceId = $this->config['serviceId'];
        $apiToken = $this->config['apiToken'];

        $description = $this->config['transactionDescription'];
        if (!$description) {
            $description = null;
        }

        \Paynl\Config::setApiToken($apiToken);
        \Paynl\Config::setServiceId($serviceId);

        $name = $this->getPaymentShortName();
        $paymentMethodId = substr($name, strrpos($name, '_') + 1);

        $user = $this->getUser();
        $basket = $this->getBasket();

        $shippingAddress = \Paynl\Helper::splitAddress($user['shippingaddress']['street']);
        $invoiceAddress = \Paynl\Helper::splitAddress($user['billingaddress']['street']);

        $startData = array(
            'amount' => $this->getAmount(),
            'returnUrl' => $router->assemble(array('action' => 'return', 'forceSecure' => true)),

            'description' => $description,
            'currency' => $basket['sCurrencyName'],
            'exchangeUrl' => $router->assemble(array('action' => 'notify', 'forceSecure' => true, 'appendSession' => true)),
            'paymentMethod' => $paymentMethodId,
            'testmode' => 0,
            'language' => $this->getLanguage(),
            'ipaddress' => $this->request->getClientIp(),
            'enduser' => array(
                'initials' => substr($user['shippingaddress']['firstname'], 0, 1),
                'lastName' => $user['shippingaddress']['lastname'],
                'phoneNumber' => $user['shippingaddress']['phone'],
                'emailAddress' => $user['additional']['user']['email'],
            ),
            'address' => array(
                'streetName' => $shippingAddress[0],
                'houseNumber' => $shippingAddress[1],
                'zipCode' => $user['shippingaddress']['zipcode'],
                'city' => $user['shippingaddress']['city'],
                'country' => $user['additional']['countryShipping']['countryiso'],
            ),
            'invoiceAddress' => array(
                'initials' => substr($user['billingaddress']['firstname'], 0, 1),
                'lastName' => $user['billingaddress']['lastname'],
                'streetName' => $invoiceAddress[0],
                'houseNumber' => $invoiceAddress[1],
                'zipCode' => $user['billingaddress']['zipcode'],
                'city' => $user['billingaddress']['city'],
                'country' => $user['additional']['country']['countryiso'],
            ));


        $startData['products'] = array();
        foreach ($basket['content'] as $product) {
            $startData['products'][] = array(
                'id' => $product['articleID'],
                'name' => $product['articlename'],
                'price' => $product['priceNumeric'],
                'tax' => $product['priceNumeric'] - $product['netprice'],
                'qty' => $product['quantity'],
            );
        }


        if (isset($basket['sShippingcosts']) && $basket['sShippingcosts'] != 0) {
            $startData['products'][] = array(
                'id' => 'shipping',
                'name' => 'Verzendkosten',
                'price' => $basket['sShippingcostsWithTax'],
                'tax' => $basket['sShippingcostsTax'],
                'qty' => 1
            );
        }

        try {
            $result = \Paynl\Transaction::start($startData);

            if (in_array($paymentMethodId, self::CONFIRM_BEFORE_PAYMENT)) {
                $this->saveOrder($result->getTransactionId(), $result->getTransactionId(), self::STATUS_PENDING);
            }

            $this->redirect($result->getRedirectUrl());
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
            die();
        }
    }

    protected function getLanguage()
    {

        $language = Shopware()->Shop()->getLocale()->getLanguage();
        $language = strtolower($language);
        if (strlen($language) < 2)
            return "de";
        else
            return substr($language, 0, 2);
    }

    private function isOrderCreated($transactionId)
    {
        $sql = '
            SELECT id FROM s_order
            WHERE transactionID=? AND temporaryID=?
            AND status!=-1
        ';
        $orderId = Shopware()->Db()->fetchOne($sql, [
            $transactionId,
            $transactionId,
        ]);

        return !empty($orderId);
    }

    public function returnAction()
    {
        $transactionId = $this->Request()->get('orderId');

        $serviceId = $this->config['serviceId'];
        $apiToken = $this->config['apiToken'];

        \Paynl\Config::setApiToken($apiToken);
        \Paynl\Config::setServiceId($serviceId);

        $transaction = \Paynl\Transaction::get($transactionId);

        if ($transaction->isPaid() || $transaction->isPending()) {
            if (!$this->isOrderCreated($transactionId)) {
                $this->saveOrder($transactionId, $transactionId, self::STATUS_PENDING);
            }

            $this->redirect(array('controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $transactionId));
            return true;
        } else {

            $this->redirect(array('controller' => 'checkout', 'action' => 'confirm', 'sUniqueID' => $transactionId));
            return true;
        }
    }

}
