<?php

namespace PaynlPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_View;
use PaynlPayment\Components\IssuersProvider;

class PaymentMethodIssuers implements SubscriberInterface
{
    private $issuersProvider;

    public function __construct(IssuersProvider $issuersProvider)
    {
        $this->issuersProvider = $issuersProvider;
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
        $action = $request->getActionName();

        if (!($controllerName == 'checkout' && $action == 'shippingPayment')) {
            return;
        }

        /** @var Enlight_View $view */
        $view = $controller->View();

        /** @var \Enlight_Components_Session_Namespace $session */
        $session = Shopware()->Session();

        $selectedIssuer = Shopware()->Front()->Request()->getPost('paynlIssuer') ?: $session->paynlIssuer;

        if (!empty($selectedIssuer)) {
            $session->paynlIssuer = $selectedIssuer;
        }
        $view->assign('paynlSelectedIssuer', $selectedIssuer);

        if ($selectedIssuer == 0) {
            $session->paynlIssuer = null;
        }

        $issuers = $this->issuersProvider->getIssuers();
        $view->assign('paynlIssuers', $issuers);
    }
}
