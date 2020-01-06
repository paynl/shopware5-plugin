<?php

use Monolog\Logger;
use Paynl\Paymentmethods;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_PaynlApiTest extends Enlight_Controller_Action
{
  /**
   * @var Logger
   */
  private $logger;

  public function testAction()
  {
    $paynlConfig = $this->get('paynl_payment.config');
    $this->logger = $this->container->get('pluginlogger');

    try {
      $paynlConfig->loginSDK();
      $methods = Paymentmethods::getList();

      if (is_array($methods) && count($methods) > 0) {
        $this->View()->assign('response', 'Successfully Connected to PAY.!');
        $this->logger->addInfo('PAY.: Test API connection success. Found ' . count($methods) . ' payment methods');
      } else {
        $this->logger->addError('PAY.: Test API connection success. But no payment methods installed in the PAY. backoffice.');
        $this->View()->assign('response', 'Connected to PAY., but no payment methods configured in the PAY. backoffice.');
      }
    } catch (\Exception $exception) {
      $this->logger->addError('PAY.: ' . $exception->getMessage());
      $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
      $this->View()->assign('response', 'Could not connect to PAY.');
    }
  }
}