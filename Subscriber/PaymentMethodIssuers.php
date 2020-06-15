<?php

namespace PaynlPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_View;
use PaynlPayment\Components\Config;
use PaynlPayment\Components\IssuersProvider;
use PaynlPayment\Helpers\ExtraFieldsHelper;

class PaymentMethodIssuers implements SubscriberInterface
{
    private $issuersProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Zend_Session_Abstract
     */
    private $session;

    /**
     * @var ExtraFieldsHelper
     */
    private $extraFieldsHelper;

    public function __construct(
        IssuersProvider $issuersProvider,
        Config $config,
        \Zend_Session_Abstract $session,
        ExtraFieldsHelper $extraFieldsHelper
    ) {
        $this->issuersProvider = $issuersProvider;
        $this->config = $config;
        $this->session = $session;
        $this->extraFieldsHelper = $extraFieldsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
            'Enlight_Controller_Action_PostDispatch_Frontend_Account' => 'onPostDispatchAccount',

            'Shopware_Modules_Admin_UpdatePayment_FilterSql' => 'onUpdatePaymentForUser',
        ];
    }

    public function onUpdatePaymentForUser(\Enlight_Event_EventArgs $args) {
        $request = Shopware()->Front()->Request();
        $defaultReturn = $args->getReturn();
        $extraFieldsData = [
            'idealIssuer' => (int)$request->getPost('paynlIssuer')
        ];

        $this->extraFieldsHelper->saveExtraFields($extraFieldsData, $this->session->sUserId);

        return $defaultReturn;
    }

    public function onPostDispatchAccount(\Enlight_Event_EventArgs $args) {
        $request = $args->getRequest();
        $controller = $args->getSubject();
        /** @var Enlight_View $view */
        $view = $controller->View();
        $action = $request->getActionName();
        $issuerId = $this->extraFieldsHelper->getSelectedIssuer();

        if ($action == 'payment') {
            $view->assign('paynlIssuers', $this->issuersProvider->getIssuers());
            $view->assign('paynlSelectedIssuer', $issuerId);
        }

        if ($action == 'index') {
            $bankData = [];
            foreach ($this->issuersProvider->getIssuers() as $bank) {
                if ($bank->id == $issuerId) {
                    $bankData = $bank;
                    break;
                }
            }

            $view->assign('bankData', $bankData);
        }
    }

    public function onPostDispatchCheckout(\Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = $args->getSubject();

        $request = $args->getRequest();
        $controllerName = $request->getControllerName();
        if ($controllerName != 'checkout') {
            return;
        }

        $action = $request->getActionName();

        /** @var Enlight_View $view */
        $view = $controller->View();
        $issuerId = $this->extraFieldsHelper->getSelectedIssuer();

        if ($action == 'confirm' && !empty($issuerId)) {
            $bankData = [];

            $selectedPaymentMethodName =
                $this->session->sOrderVariables['sUserData']['additional']['payment']['description'];
            if ($selectedPaymentMethodName == 'iDEAL') {
                foreach ($this->issuersProvider->getIssuers() as $bank) {
                    if ($bank->id == $issuerId) {
                        $bankData = $bank;
                        break;
                    }
                }

                $view->assign('bankData', $bankData);
            } else {
                $this->extraFieldsHelper->clearSelectedIssuer($this->session->sUserId);
                $view->assign('bankData', $bankData);
            }
        }

        $isCancelled = false;
        if ($action === 'confirm') {
            $isCancelled = (bool)Shopware()->Front()->Request()->get('isCancelled', 0);
        }

        $view->assign('isCancelled', $isCancelled);
        if ($action == 'shippingPayment' && $this->config->banksIsAllowed()) {
            $view->assign('paynlSelectedIssuer', $issuerId);
            $issuers = $this->issuersProvider->getIssuers();
            $view->assign('paynlIssuers', $issuers);
        }
    }
}
