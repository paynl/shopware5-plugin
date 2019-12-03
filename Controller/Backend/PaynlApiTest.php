<?php

namespace PaynlPayment\Controller\Backend;

use Monolog\Logger;
use Shopware\Components\HttpClient\HttpClientInterface;
use Shopware\Components\HttpClient\RequestException;
use Shopware_Controllers_Backend_Application;
use Symfony\Component\HttpFoundation\Response;

class PaynlApiTest extends Shopware_Controllers_Backend_Application
{
    /**
     * This URL might as well be configurable and therefore read from the
     * database, it's written out here for demonstration purposes.
     */
    private const EXTERNAL_API_BASE_URL = 'rest-api.pay.nl';

    /**
     * @var HttpClientInterface
     */
    private $client;

    protected $model = 'test';

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(HttpClientInterface $client, Logger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;

        parent::__construct();
    }

    public function testAction()
    {
        try {
            $response = $this->client->get(self::EXTERNAL_API_BASE_URL);

            if ((int) $response->getStatusCode() === Response::HTTP_OK) {
                $this->View()->assign('response', 'Success!');
            } else {
                $this->View()->assign('response', 'Oh no! Something went wrong :(');
            }
        } catch (RequestException $exception) {
            $this->logger->addError($exception->getMessage());

            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->View()->assign('response', $exception->getMessage());
        }
    }
}