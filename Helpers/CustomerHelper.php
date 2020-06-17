<?php

namespace PaynlPayment\Helpers;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;

class CustomerHelper
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
    }

    /**
     * @param int $customerId
     * @return mixed[]
     */
    public function getCurrentCustomerById(int $customerId)
    {
        $customer = $this->modelManager
            ->getRepository(Customer::class)
            ->findOneBy(['id' => $customerId]);

        return $customer;
    }

    /**
     * @param int $customerId
     * @return mixed[]
     */
    public function getDobAndPhoneByCustomerId(int $customerId): array
    {
        $customer = $this->getCurrentCustomerById($customerId);

        return [
            'dob' => $customer->getBirthday(),
            'phone' => $customer->getDefaultBillingAddress()->getPhone()
        ];
    }
}
