<?php

namespace PaynlPayment\Helpers;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;

class CustomerHelper
{
    const USER_TABLE = 's_user';
    const UT_FIELD_BIRTHDAY = 'birthday';

    const USER_ADDRESSES_TABLE = 's_user_addresses';
    const UAT_FIELD_PHONE = 'phone';

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
     * @param int $userId
     * @return Customer|object
     */
    public function getCurrentUserById(int $userId): Customer
    {
        return $this->modelManager
            ->getRepository(Customer::class)
            ->find($userId);
    }

    /**
     * @param int $userId
     * @return mixed[]
     */
    public function getDobAndPhoneByCustomerId(int $userId): array
    {
        $customer = $this->getCurrentUserById($userId);

        return [
            'dob' => $customer->getBirthday(),
            'phone' => $customer->getDefaultBillingAddress()->getPhone()
        ];
    }

    public function storePhone(int $userId, string $phone)
    {
        $this->modelManager->getConnection()->executeQuery(
            join(' ', [
                sprintf('UPDATE %s', self::USER_ADDRESSES_TABLE),
                'SET',
                sprintf('%s = :%s', self::UAT_FIELD_PHONE, self::UAT_FIELD_PHONE),
                'WHERE user_id = :user_id',
            ]),
            ['user_id' => $userId, self::UAT_FIELD_PHONE => $phone]
        );
    }

    public function storeUserBirthday(int $userId, string $birthday)
    {
        $this->modelManager->getConnection()->executeQuery(
            join(' ', [
                sprintf('UPDATE %s', self::USER_TABLE),
                'SET',
                sprintf('%s = :%s', self::UT_FIELD_BIRTHDAY, self::UT_FIELD_BIRTHDAY),
                'WHERE id = :id',
            ]),
            ['id' => $userId, self::UT_FIELD_BIRTHDAY => $birthday]
        );
    }
}
