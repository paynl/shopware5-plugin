<?php
require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

class Shopware_Plugins_Frontend_PaymentPayNL_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    public function afterInit()
    {
        require_once $this->Path() . '/vendor/autoload.php';
    }

    /**
     *
     * @return string version
     */
    public function getVersion()
    {
        return '2.0.6';
    }

    /**
     *
     * @return array info
     */
    public function getInfo()
    {
        return array('version' => $this->getVersion(),
            'author' => 'Andy Pieters <andy@pay.nl>',
            'label' => 'Pay.nl',
            'source' => $this->getSource(),
            'description' => 'Add the Pay.nl Payment methods to you Shopware Store',
            'license' => 'MIT',
            'support' => 'support@pay.nl',
            'link' => 'http://www.pay.nl',
            'changes' => '',
            'copyright' => 'Copyright Pay.nl 2015',
            'revision' => '');
    }

    /**
     *
     * @return boolean installed
     */
    public function install()
    {
        $this->createForm();

        $this->registerController('Frontend', 'PaymentPaynl');

        return true;
    }

    /**
     *
     * @return boolean uninstalled
     */
    public function uninstall()
    {
        return true;
    }

    public function update($oldVersion)
    {
        // nothing to do;
        return true;
    }

    private function refreshPaymentMethods($serviceId, $apiToken)
    {
        \Paynl\Config::setApiToken($apiToken);
        \Paynl\Config::setServiceId($serviceId);

        $availableMethods        = \Paynl\Paymentmethods::getList();
        $installedPaymentMethods = $this->getPaymentMethods();

        foreach ($installedPaymentMethods as $installedPaymentMethod) {
            $name            = $installedPaymentMethod->getName();
            $paymentMethodId = substr($name, strrpos($name, '_') + 1);

            $key = array_search($paymentMethodId,
                array_column($availableMethods, 'id'));
            if ($key === false) {
                //not available anymore so lets delete it
                $row = Shopware()->Payments()->fetchRow(array('id' => $installedPaymentMethod->getId(),
                    'pluginId' => $this->getId()));
                if (!is_null($row)) {
                    //$row->delete();
                }
            } else {
                // no need to install so remove from available methods
		if (!is_null($row)) {
                    $row->action = 'payment_paynl/direct';
                    $row->save();
                }
                unset($availableMethods[$key]);
            }
        }
        //install new payment methods
        foreach ($availableMethods as $method) {
            $this->addPaymentMethod($method['id'], $method['name']);
        }
    }

    public function enable()
    {
        $serviceId = $this->Config()->get('serviceId');
        $apiToken  = $this->Config()->get('apiToken');
        if (!empty($serviceId) && !empty($apiToken)) {
            $this->refreshPaymentMethods($serviceId, $apiToken);
            $paymentMethods = $this->getPaymentMethods();

            foreach ($paymentMethods as $paymentMethod) {
                $paymentMethod->setActive(true);
                $this->get('models')->flush($paymentMethod);
            }
        }
        return true;
    }

    public function disable()
    {
        $paymentMethods = $this->getPaymentMethods();
        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethod->setActive(false);
            $this->get('models')->flush($paymentMethod);
        }

        return true;
    }

    /**
     *
     * @return Shopware\Models\Payment\Payment[]
     */
    public function getPaymentMethods()
    {
        $arrPaymentMethods = $this->Payments()->findBy(array('pluginId' => $this->getId()));

        $arrResult = array();

        if (!empty($arrPaymentMethods)) {
            foreach ($arrPaymentMethods as $paymentMethod) {
                // strange thing, i use findBy and it still returns other payment methods
                if ($paymentMethod->getPluginId() == $this->getId()) {
                    $arrResult[] = $paymentMethod;
                }
            }
        }
        return $arrResult;
    }

    private function removePaymentMethods()
    {

        $paymentMethods = $this->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethod->delete();
        }

        return true;
    }

    public function addPaymentMethod($paymentOptionId, $name)
    {
        $this->createPayment(array(
            'name' => 'paynl_'.$paymentOptionId,
            'description' => $name,
            'active' => 1,
            'action' => 'payment_paynl/direct',
            'additionalDescription' => '<img src="https://www.pay.nl/images/payment_profiles/50x32/'.$paymentOptionId.'.png" />',
        ));
    }

    public function createForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'apiToken',
            array(
            'label' => 'API Token',
            'require' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ));

        $form->setElement('text', 'serviceId',
            array(
            'label' => 'Service Id',
            'require' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ));

        $form->setElement('text', 'transactionDescription',
            array(
                'label' => 'Transactie omschrijving',
                'require' => false,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
            ));
    }
}
