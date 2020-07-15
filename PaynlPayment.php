<?php

namespace PaynlPayment;

/**
 * The plugin can be installed via composer or via the package file.
 * When installed via composer, the sdk is automaticly loaded in the vendor directory.
 * In the package file this is included, but need to be loaded
 */
if (!class_exists('\Paynl\Config') && file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

use Doctrine\ORM\Tools\SchemaTool;
use Paynl\Paymentmethods;
use PaynlPayment\Components\Config;
use PaynlPayment\Helpers\ExtraFieldsHelper;
use PaynlPayment\Models\Banks\Banks;
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
    const PAYMENT_METHODS_TEMPLATES_DIRECTORY = __DIR__ . '/Resources/views/frontend/plugins/payment/';
    const PLUGIN_NAME = 'PaynlPayment';

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $this->createTables();
        $this->addUserAttributeColumn();
        $this->initPaymentIdIncrementer();
        $this->migrate();

        parent::install($context);
    }

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context)
    {
        $this->createTables();
        $this->initPaymentIdIncrementer();
        $this->migrate();

        parent::update($context);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->disablePaymentMethods($context->getPlugin());

        if (!$context->keepUserData()) {
            $this->removeAllTables();
        }

        $this->removeAttributeColumns();

        parent::uninstall($context);
    }

    private function removeAllTables()
    {
        try {
            $db = $this->container->get('db');
            $db->executeQuery('DROP TABLE IF EXISTS `paynl_transactions`');
            $db->executeQuery('DROP TABLE IF EXISTS `s_plugin_paynlpayment_transactions`');
            $db->executeQuery('DROP TABLE IF EXISTS `s_plugin_paynlpayment_payment_method_banks`');
        } catch (\Exception $e) {
            $this->container->get('pluginlogger')->addError('PAY. Uninstall: ' . $e->getMessage());
        }
    }

    /**
     * @param ActivateContext $context
     * @throws \Exception
     */
    public function activate(ActivateContext $context)
    {
        $plugin = $context->getPlugin();
        $this->disablePaymentMethods($plugin);
        $this->installPaymentMethods($plugin);

        parent::activate($context);
    }

    /**
     * @param DeactivateContext $context
     */
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

    /**
     * @param \Shopware\Models\Plugin\Plugin $plugin
     */
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
     * @throws \Exception
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

        /** @var Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');
        /** @var ModelManager $modelManager */
        $modelManager = $this->container->get('models');
        $paymentMethodBanks = $modelManager->getRepository(Banks::class);

        foreach ($methods as $method) {
            $options = [
                'name' => sprintf('paynl_%s', $method['id']),
                'class' => $method['brand']['id'] ?? '',
                'description' => $method['name'],
                'action' => 'PaynlPayment',
                'active' => true,
                'additionalDescription' => $method['brand']['public_description'] ?? ''
            ];

            $pluginTemplateName = sprintf('%d.%s', (int)$method['id'], 'tpl');
            if (is_file(self:: PAYMENT_METHODS_TEMPLATES_DIRECTORY . $pluginTemplateName)) {
                $options['template'] = $pluginTemplateName;
            }

            $installer->createOrUpdate($plugin->getName(), $options);
            if ((int)$method['id'] === 10 && !empty($method['banks'])) {
                $paymentMethodBanks->upsert($method['id'], $method['banks']);
            }
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
            $modelManager->getClassMetadata(Transaction\Transaction::class),
            $modelManager->getClassMetadata(Banks::class)
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

    private function migrate()
    {
        $db = $this->container->get('db');

        try {
            $bRenamed = false;
            $db->executeQuery('SELECT 1 FROM `paynl_transactions` LIMIT 1');

            try {
                $db->executeQuery('SELECT 1 FROM `s_plugin_paynlpayment_transactions` LIMIT 1');
            } catch (\Exception $e) {
                $db->executeQuery('RENAME TABLE `paynl_transactions` TO `s_plugin_paynlpayment_transactions`');
                $db->executeQuery('ALTER TABLE `s_plugin_paynlpayment_transactions` ADD `s_comment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `exceptions`');
                $db->executeQuery('ALTER TABLE `s_plugin_paynlpayment_transactions` ADD `s_dispatch` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `s_comment`');
                $bRenamed = true;
            }

            # If `paynl_transacions` didnt exist, it would've generated an exception by now and next two queries will not be executed .
            if ($bRenamed === false) {
                $db->executeQuery('INSERT IGNORE INTO `s_plugin_paynlpayment_transactions` (paynl_payment_id, transaction_id, signature, amount, currency, created_at, updated_at, customer_id, order_id, payment_id, status_id)
          SELECT `paynl_payment_id`, `transaction_id`, `signature`, `amount`, `currency`, `created_at`, `updated_at`, `customer_id`, `order_id`, `payment_id`, `status_id` FROM `paynl_transactions`');
                $db->executeQuery('DROP TABLE `paynl_transactions` ');
            }
        } catch (\Exception $exception) {
            $this->log('PAY.: Migration: ' . $exception->getMessage());
        }
    }

    /**
     * @param $message
     */
    private function log($message)
    {
        $this->container->get('pluginlogger')->addNotice($message);
    }

    private function addUserAttributeColumn()
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');

        if (!$this->columnExists(ExtraFieldsHelper::USER_ATTRIBUTES_TABLE, ExtraFieldsHelper::EXTRA_FIELD_COLUMN)) {
            $crudService->update(
                ExtraFieldsHelper::USER_ATTRIBUTES_TABLE,
                ExtraFieldsHelper::EXTRA_FIELD_COLUMN,
                'json',
                []
            );

            $this->rebuildAttributeModels([ExtraFieldsHelper::USER_ATTRIBUTES_TABLE]);
        }
    }

    /**
     * Remove extra attribute columns
     */
    private function removeAttributeColumns()
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');

        if ($this->columnExists(ExtraFieldsHelper::USER_ATTRIBUTES_TABLE, ExtraFieldsHelper::EXTRA_FIELD_COLUMN)) {
            $crudService->delete(ExtraFieldsHelper::USER_ATTRIBUTES_TABLE, ExtraFieldsHelper::EXTRA_FIELD_COLUMN);

            $this->rebuildAttributeModels([ExtraFieldsHelper::USER_ATTRIBUTES_TABLE]);
        }
    }

    /**
     * @param string $table
     * @param string $columnName
     * @return bool
     */
    private function columnExists(string $table, string $columnName): bool
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $column = $crudService->get($table, $columnName);

        return !empty($column);
    }

    /**
     * @param array $tables
     */
    private function rebuildAttributeModels(array $tables): void
    {
        $em = $this->container->get('models');
        $metaDataCache = $em->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();

        $em->generateAttributeModels($tables);
    }
}
