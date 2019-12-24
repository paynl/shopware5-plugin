<?php

namespace PaynlPayment\Controllers\Backend;

use Monolog\Logger;
use Paynl\Paymentmethods;
use PaynlPayment\Components\Config;
use Shopware_Controllers_Backend_Application;
use Symfony\Component\HttpFoundation\Response;

class PaynlApiTest extends Shopware_Controllers_Backend_Application
{
  /**
   * @var Logger
   */
  private $logger;

  /**
   * @var Config
   */
  private $config;

  public function __construct(Logger $logger, Config $config)
  {
    $this->logger = $logger;
    $this->config = $config;

    parent::__construct();
  }

  public function testAction()
  {
    try {
      $this->config->loginSDK();
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