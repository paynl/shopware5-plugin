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
//
        /** @var Enlight_View $view */
        $view = $controller->View();
//
        /** @var \Enlight_Components_Session_Namespace $session */
        $session = Shopware()->Session();
//
        /** @var \Enlight_Controller_Request_Request $request */
        $request = $args->getRequest();
//
        $paymentName = $session['sOrderVariables']['sPayment']['name'];
        if (!is_null($paymentName)) {
            $paymentMethodID = $this->issuersProvider->getPaymentMethodIdByName($paymentName);
            if (!empty($paymentMethodID)) {
                $issuers = $this->issuersProvider->getIssuers($paymentMethodID);
            } else {
                $issuers = [];
            }
            $view->assign('issuers', $issuers);

            $issuerFromRequest = $request->getPost('issuer');
            $selectedIssuer = is_null($issuerFromRequest) ? $session->issuer : $issuerFromRequest;

            $view->assign('selectedIssuer', $selectedIssuer);
            if (!empty($selectedIssuer)) {
                $session->issuer = $selectedIssuer;
            }

            if ($selectedIssuer == 0) {
                $session->issuer = null;
            }
        }
    }
}
