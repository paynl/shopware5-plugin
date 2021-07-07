<?php
namespace PaynlPayment\Helpers;

use Shopware\Components\Logger;

class PaynlPaymentLoggerHelper
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->logger, $name) ){
            // Monolog 1: addDebug(), addInfo()...
            $this->logger->$name(...$arguments);
        } else {
            // Monolog 2: debug(), info()...
            $name = str_replace('add', '', $name);
            $this->logger->$name(...$arguments);
        }
    }
}
