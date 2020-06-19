<?php

namespace PaynlPayment\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @param $pluginDirectory
     * @param \Enlight_Template_Manager $templateManager
     */
    public function __construct($pluginDirectory, \Enlight_Template_Manager $templateManager)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onLoadFrontendCheckout',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Index' => 'onLoadBackendIndex',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onPostDispatchOrder',
            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles'
        ];
    }

    public function onPreDispatch()
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }

    /**
     * Handles the Enlight_Controller_Action_PostDispatchSecure_Backend_Index event.
     * Extends the backend icon set with the pay.nl icon
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onLoadBackendIndex(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Enlight_View_Default $view */
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->pluginDirectory . '/Resources/views/');
        $view->extendsTemplate('backend/paynl_transactions/menu_icon.tpl');
    }

  /**
   * @param \Enlight_Event_EventArgs $args
   */
    public function onPostDispatchOrder(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');
        $request = $controller->Request();
        $view = $controller->View();

        $view->addTemplateDir(__DIR__ . '/Resources/views/');
        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/order/view/list.js');
        }
    }

    public function onLoadFrontendCheckout(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_View_Default $view */
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->pluginDirectory . '/Resources/');
        $view->extendsTemplate('frontend/css/change_payment.css');
    }

    public function addJsFiles(\Enlight_Event_EventArgs $args) {
        $jsFiles = [
            sprintf('%s/../%s', rtrim(__DIR__, '/'), 'Resources/views/frontend/_public/src/js/jquery.register.js'),
            sprintf('%s/../%s', rtrim(__DIR__, '/'), 'Resources/views/frontend/_public/src/js/jquery.shipping-payment.js'),
        ];

        return new ArrayCollection($jsFiles);
    }
}
