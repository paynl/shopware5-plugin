<?php

namespace PaynlPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_View;
use PaynlPayment\Components\Config;
use PaynlPayment\Components\IssuersProvider;
use PaynlPayment\Helpers\CustomerHelper;
use Zend_Session_Abstract;
use PaynlPayment\Helpers\ExtraFieldsHelper;

class PaymentMethodIssuers implements SubscriberInterface
{
    /**
     * @var Zend_Session_Abstract
     */
    private $session;
    /**
     * @var Config
     */
    private $config;

    private $issuersProvider;
    /**
     * @var CustomerHelper
     */
    private $customerHelper;
    /**
     * @var ExtraFieldsHelper
     */
    private $extraFieldsHelper;

    public function __construct(
        Zend_Session_Abstract $session,
        Config $config,
        IssuersProvider $issuersProvider,
        CustomerHelper $customerHelper,
        ExtraFieldsHelper $extraFieldsHelper
    )
    {
        $this->session = $session;
        $this->config = $config;
        $this->issuersProvider = $issuersProvider;
        $this->customerHelper = $customerHelper;
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

    public function onUpdatePaymentForUser(\Enlight_Event_EventArgs $args)
    {
        $defaultReturn = $args->getReturn();
        /** @var \Enlight_Controller_Request_Request $request */
        $request = Shopware()->Front()->Request();

        // We are assign the extra fields as chosen iDeal bank to the customer by storing it to database as attribute
        $this->storeExtraFields($request);

        // Store date of birth and phone number to DB
        $this->storeDobAndPhone($request);

        return $defaultReturn;
    }

    public function onPostDispatchAccount(\Enlight_Event_EventArgs $args)
    {
        if (empty($this->session->sUserId)) {
            return;
        }

        $request = $args->getRequest();
        $controller = $args->getSubject();

        /** @var Enlight_View $view */
        $view = $controller->View();
        $action = $request->getActionName();

        // Check if we can show date of birth and phone number fields for some payment methods
        $this->renderDobAndPhoneFields($view);

        if ($action == 'payment' && $this->config->banksIsAllowed()) {
            // If `Show banks` attribute in plugin manager is allowed we show the list of banks for iDeal
            $this->renderBanks($view);
        }

        if ($action == 'index') {
            $selectedBank = $this->extraFieldsHelper->getSelectedIssuer($this->session->sUserId);
            // Pass the data of chosen bank
            $view->assign('bankData', $this->getSelectedBankData($selectedBank));
        }
    }

    public function onPostDispatchCheckout(\Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = $args->getSubject();

        $request = $args->getRequest();
        $controllerName = $request->getControllerName();
        if ($controllerName != 'checkout' || empty($this->session->sUserId)) {
            return;
        }

        $action = $request->getActionName();

        /** @var Enlight_View $view */
        $view = $controller->View();

        if ($action == 'confirm') {
            // Pass the selected bank data to frontend
            $this->renderSelectedBank($view);
            // Show message if payment was cancelled or denied
            $this->isPaymentCancelledShowMessage($view);
        }

        if ($action == 'shippingPayment') {
            $this->onChangePaymentMethodActionCheckout($view);
        }
    }

    /**
     * @param Enlight_View $view
     */
    private function renderBanks(Enlight_View $view): void
    {
        $selectedBank = $this->extraFieldsHelper->getSelectedIssuer($this->session->sUserId);
        $view->assign('paynlIssuers', $this->issuersProvider->getIssuers());
        $view->assign('paynlSelectedIssuer', $selectedBank);
    }

    /**
     * @param Enlight_View $view
     * @throws \Exception
     */
    private function renderSelectedBank(Enlight_View $view): void
    {
        $userId = $this->session->sUserId;
        $selectedBank = $this->extraFieldsHelper->getSelectedIssuer($userId);
        if (!empty($selectedBank)) {
            $selectedPaymentMethodName =
                $this->session->sOrderVariables['sUserData']['additional']['payment']['description'];

            if ($selectedPaymentMethodName == 'iDEAL') {
                $view->assign('bankData', $this->getSelectedBankData($selectedBank));
            } else {
                $this->extraFieldsHelper->clearSelectedIssuer($userId);
                $view->assign('bankData', []);
            }
        }
    }

    /**
     * @param Enlight_View $view
     * @throws \Exception
     */
    private function onChangePaymentMethodActionCheckout(Enlight_View $view): void
    {
        // Check if we can show date of birth and phone number fields for some payment methods
        $this->renderDobAndPhoneFields($view);
        if ($this->config->banksIsAllowed()) {
            // If `Show banks` attribute in plugin manager is allowed we show the list of banks for iDeal
            $this->renderBanks($view);
        }
    }

    /**
     * @param \Enlight_Controller_Request_Request $request
     * @throws \Exception
     */
    private function storeExtraFields(\Enlight_Controller_Request_Request $request): void
    {
        $extraFieldsData = [
            'idealIssuer' => (int)$request->getPost('paynlIssuer')
        ];

        $this->extraFieldsHelper->saveExtraFields($extraFieldsData, $this->session->sUserId);
    }

    /**
     * @param \Enlight_Controller_Request_Request $request
     */
    private function storeDobAndPhone(\Enlight_Controller_Request_Request $request): void
    {
        $payment = $request->getPost('payment');
        if (!empty($request->getPost('register')) && isset($request->getPost('register')['payment'])) {
            $payment = $request->getPost('register')['payment'];
        }
        $phone = $request->getPost('phone');
        $dob = $request->getPost('dob');

        $userId = $this->session->sUserId;

        if (isset($phone[$payment]) && !empty($phone[$payment])) {
            $this->customerHelper->storePhone($userId, $phone[$payment]);
        }

        if (isset($dob[$payment]) && !empty($dob[$payment])) {
            $this->customerHelper->storeUserBirthday($userId, $dob[$payment]);
        }
    }

    /**
     * @param Enlight_View $view
     */
    private function isPaymentCancelledShowMessage(Enlight_View $view): void
    {
        $isCancelled = (bool)Shopware()->Front()->Request()->get('isCancelled', 0);

        $view->assign('isCancelled', $isCancelled);
    }

    /**
     * @param Enlight_View $view
     */
    private function renderDobAndPhoneFields(Enlight_View $view): void
    {
        $customerDobAndPhone = $this->customerHelper->getDobAndPhoneByCustomerId($this->session->sUserId);
        if (!isset($customerDobAndPhone['dob']) || empty($customerDobAndPhone['dob'])) {
            $view->assign('showDobField', true);
        }
        if (!isset($customerDobAndPhone['phone']) || empty($customerDobAndPhone['phone'])) {
            $view->assign('showPhoneField', true);
        }
    }

    /**
     * @param int $selectedBank
     * @return mixed[]
     */
    private function getSelectedBankData(int $selectedBank): array
    {
        $bankData = [];
        $banks = $this->issuersProvider->getIssuers();
        if (!empty($banks)) {
            foreach ($this->issuersProvider->getIssuers() as $bank) {
                if ((int)$bank['id'] === $selectedBank) {
                    $bankData = $bank;
                    break;
                }
            }
        }

        return $bankData;
    }
}
