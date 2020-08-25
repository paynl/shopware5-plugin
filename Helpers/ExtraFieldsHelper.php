<?php

namespace PaynlPayment\Helpers;

use Shopware\Bundle\AttributeBundle\Service\DataLoader;
use Shopware\Bundle\AttributeBundle\Service\DataPersister;

class ExtraFieldsHelper
{
    const USER_ATTRIBUTES_TABLE = 's_user_attributes';
    const EXTRA_FIELD_COLUMN = 'paynl_extra_fields';

    const FIELD_IDEAL_ISSUER = 'idealIssuer';

    /**
     * @var DataPersister
     */
    private $dataPersister;

    /**
     * @var DataLoader
     */
    private $dataLoader;

    public function __construct(
        DataPersister $dataPersister,
        DataLoader $dataLoader
    ) {
        $this->dataPersister = $dataPersister;
        $this->dataLoader = $dataLoader;
    }

    /**
     * @param mixed[] $extraFields
     * @param int $userId
     * @throws \Exception
     */
    public function saveExtraFields(array $extraFields, int $userId)
    {
        $this->dataPersister->persist(
            [self::EXTRA_FIELD_COLUMN => json_encode($extraFields)],
            self::USER_ATTRIBUTES_TABLE,
            $userId
        );
    }

    /**
     * @param int $userId
     * @return mixed[]
     * @throws \Exception
     */
    public function getExtraFields(int $userId)
    {
        if ($userId) {
            $userAttributes = $this->dataLoader->load(self::USER_ATTRIBUTES_TABLE, $userId);

            if (!empty($userAttributes[self::EXTRA_FIELD_COLUMN])) {
                return json_decode($userAttributes[self::EXTRA_FIELD_COLUMN], true);
            }
        }

        return [];
    }

    /**
     * @param int $userId
     * @return int
     * @throws \Exception
     */
    public function getSelectedIssuer(int $userId)
    {
        return $this->getExtraFields($userId)[self::FIELD_IDEAL_ISSUER] ?: 0;
    }

    /**
     * @param int $userId
     * @throws \Exception
     */
    public function clearSelectedIssuer(int $userId)
    {
        $extraFieldsData[self::FIELD_IDEAL_ISSUER] = 0;
        $newExtraFieldsData = array_merge($this->getExtraFields($userId), $extraFieldsData);

        $this->saveExtraFields($newExtraFieldsData, $userId);
    }
}
