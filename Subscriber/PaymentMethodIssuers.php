<?php

namespace PaynlPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_View;
use PaynlPayment\Components\Config;
use PaynlPayment\Components\IssuersProvider;

class PaymentMethodIssuers implements SubscriberInterface
{
    private $issuersProvider;
    /**
     * @var Config
     */
    private $config;

    public function __construct(IssuersProvider $issuersProvider, Config $config)
    {
        $this->issuersProvider = $issuersProvider;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
        ];
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

        /** @var \Enlight_Components_Session_Namespace $session */
        $session = Shopware()->Session();

        $selectedIssuer = Shopware()->Front()->Request()->getPost('paynlIssuer');
        if (empty($selectedIssuer)) {
            $selectedIssuer = $session->paynlIssuer;
        }
        if ($action == 'confirm' || $action == 'saveShippingPayment') {
            if (!empty($selectedIssuer)) {
                $session->paynlIssuer = $selectedIssuer;
            }

            if (empty($selectedIssuer)) {
                $session->paynlIssuer = null;
            }
        }

        if ($action == 'shippingPayment' && $this->config->banksIsAllowed()) {
            $issuers = $this->issuersProvider->getIssuers();
            $view->assign('paynlSelectedIssuer', $selectedIssuer);
            $view->assign('paynlIssuers', $issuers);
        }
    }
}
