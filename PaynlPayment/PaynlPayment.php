<?php

namespace PaynlPayment;

use Doctrine\ORM\Tools\SchemaTool;
use Paynl\Paymentmethods;
use PaynlPayment\Components\Config;
use PaynlPayment\Models\Transaction;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

require_once(__DIR__ . '/vendor/autoload.php');

class PaynlPayment extends Plugin
{
    public function install(InstallContext $context)
    {
        $this->createTables();
        $this->initPaymentIdIncrementer();

        parent::install($context);
    }

    public function update(UpdateContext $context)
    {
        $this->createTables();
        $this->initPaymentIdIncrementer();

        parent::update($context);
    }

    public function uninstall(UninstallContext $context)
    {
        $this->disablePaymentMethods($context->getPlugin());

        parent::uninstall($context);
    }

    public function activate(ActivateContext $context)
    {
        $plugin = $context->getPlugin();
        $this->disablePaymentMethods($plugin);
        $this->installPaymentMethods($plugin);

        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context)
    {
        $this->disablePaymentMethods($context->getPlugin());

        parent::deactivate($context);
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_StartDispatch' => 'requireAutoloader',
        ];
    }


    /**
     * Require composer autoloader
     */
    public function requireAutoloader()
    {
        if (file_exists($this->getPath() . '/vendor/autoload.php')) {
            require_once($this->getPath() . '/vendor/autoload.php');
        }
    }


    private function disablePaymentMethods(\Shopware\Models\Plugin\Plugin $plugin)
    {
        $em = $this->container->get('models');

        $payments = $plugin->getPayments();

        foreach ($payments as $payment) {
            $payment->setActive(false);
        }

        $em->flush();
    }

    /**
     * @param \Shopware\Models\Plugin\Plugin $plugin
     */
    private function installPaymentMethods(\Shopware\Models\Plugin\Plugin $plugin)
    {
        /** @var Config $config */
        $config = new Config($this->container->get('shopware.plugin.cached_config_reader'));

        $config->loginSDK();
        $methods = Paymentmethods::getList();

        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        foreach ($methods as $key => $method) {
            $options = [
                'name' => 'paynl_' . $method['id'],
                'description' => $method['name'],
                'action' => 'PaynlPayment',
                'active' => true,
                'additionalDescription' =>
                    '<img src="https://www.pay.nl/images/payment_profiles/50x32/' . $method['id'] . '.png" />'
            ];
            $installer->createOrUpdate($plugin->getName(), $options);
        }
    }

    /**
     * Create tables required for this plugin
     */
    private function createTables()
    {
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);

        $classes = $this->getClasses($modelManager);

        $tool->updateSchema($classes, true);
    }

    /**
     * Get the model classes for this plugin
     *
     * @param ModelManager $modelManager
     * @return array
     */
    private function getClasses(ModelManager $modelManager)
    {
        return [
            $modelManager->getClassMetadata(Transaction\Transaction::class)
        ];
    }

    private function initPaymentIdIncrementer()
    {
        $db = $this->container->get('db');

        $name = 'paynl_payment_id';

        $rows = $db->executeQuery('SELECT * FROM s_order_number WHERE name = :name', ['name' => $name])->fetchAll();

        if (count($rows) < 1) {
            $db->executeQuery('INSERT INTO `s_order_number` (`number`, `name`, `desc`) VALUES (:number, :name, :description)', [
                'number' => 1000000,
                'name' => $name,
                'description' => 'Payment id for pay.nl payments'
            ]);
        }
    }
}