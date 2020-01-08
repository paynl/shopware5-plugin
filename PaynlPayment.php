<?php

namespace PaynlPayment;

/**
 * The plugin can be installed via composer or via the package file.
 * When installed via composer, the sdk is automaticly loaded in the vendor directory.
 * In the package file this is included, but need to be loaded
 */
if(!class_exists('\Paynl\Config') && file_exists(__DIR__.'/vendor/autoload.php')){
    require_once (__DIR__.'/vendor/autoload.php');
}

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

class PaynlPayment extends Plugin
{
    public function install(InstallContext $context)
    {
        $this->createTables();
        $this->initPaymentIdIncrementer($context);
        $this->migrate();

        parent::install($context);
    }

    public function update(UpdateContext $context)
    {
        $this->createTables();
        $this->initPaymentIdIncrementer($context);
        $this->migrate();

        parent::update($context);
    }

    public function uninstall(UninstallContext $context)
    {
      $this->disablePaymentMethods($context->getPlugin());

      if (!$context->keepUserData()) {
        $this->removeAllTables();
      }

      parent::uninstall($context);
    }

    private function removeAllTables()
    {
      try {
        $db = $this->container->get('db');
        $db->executeQuery('DROP TABLE IF EXISTS `paynl_transactions`');
        $db->executeQuery('DROP TABLE IF EXISTS `s_plugin_paynl_transactions`');
      } catch (\Exception $e) {
        $this->container->get('pluginlogger')->addError('PAY. Uninstall: ' . $e->getMessage());
      }
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

        try {
          $config->loginSDK();
          $methods = Paymentmethods::getList();
        } catch (\Exception $e) {
          $this->log('PAY.: Activation error: ' . $e->getMessage());
          throw new \Exception('Activation error. Please enter valid: Token-Code, API-token and Service-ID');
        }

        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        foreach ($methods as $key => $method) {
            $options = [
                'name' => 'paynl_' . $method['id'],
                'description' => $method['name'],
                'action' => 'PaynlPayment',
                'active' => true,
                'additionalDescription' =>
                    '<img src="https://static.pay.nl/payment_profiles/50x32/' . $method['id'] . '.png" />'
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

    private function initPaymentIdIncrementer($context)
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

   private function migrate()
    {
      $db = $this->container->get('db');

      try {
        $bRenamed = false;
        $db->executeQuery('select 1 from `paynl_transactions` LIMIT 1');

        try {
          $db->executeQuery('select 1 from `s_plugin_paynl_transactions` LIMIT 1');
        } catch (\Exception $e) {
          $db->executeQuery('RENAME TABLE `paynl_transactions` TO `s_plugin_paynl_transactions`');
          $db->executeQuery('ALTER TABLE `s_plugin_paynl_transactions` ADD `s_comment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `exceptions`');
          $db->executeQuery('ALTER TABLE `s_plugin_paynl_transactions` ADD `s_dispatch` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `s_comment`');
          $bRenamed = true;
        }

        # If `paynl_transacions` didnt exist, it would've generated an exception by now and next two queries will not be executed .
        if ($bRenamed === false) {
          $db->executeQuery('INSERT IGNORE INTO `s_plugin_paynl_transactions` (paynl_payment_id, transaction_id, signature, amount, currency, created_at, updated_at, customer_id, order_id, payment_id, status_id)
          SELECT `paynl_payment_id`, `transaction_id`, `signature`, `amount`, `currency`, `created_at`, `updated_at`, `customer_id`, `order_id`, `payment_id`, `status_id` FROM `paynl_transactions`');
          $db->executeQuery('DROP TABLE `paynl_transactions` ');
        }
      } catch (\Exception $exception) {
        $this->log('PAY.: Migration: ' . $exception->getMessage());
      }
    }

    private function log($message)
    {
      $this->container->get('pluginlogger')->addNotice($message);
    }

}
