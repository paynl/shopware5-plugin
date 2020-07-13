<?php

namespace PaynlPayment\Components;

use PaynlPayment\Models\Banks\Banks;
use Shopware\Components\Model\ModelManager;

class IssuersProvider
{
    /**
     * @var Banks
     */
    private $paymentMethodBanks;

    public function __construct(ModelManager $modelManager)
    {
        $this->paymentMethodBanks = $modelManager->getRepository(Banks::class);
    }

    /**
     * @param int $paymentMethodId
     * @return mixed[]
     */
    public function getIssuers($paymentMethodId = 10)
    {
        return $this->paymentMethodBanks->getBanksDataByPaymentMethodId($paymentMethodId);
    }
}
