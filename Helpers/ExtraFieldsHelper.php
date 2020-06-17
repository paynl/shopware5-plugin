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
     * @var \Zend_Session_Abstract
     */
    private $session;

    /**
     * @var DataPersister
     */
    private $dataPersister;

    /**
     * @var DataLoader
     */
    private $dataLoader;

    public function __construct(
        \Zend_Session_Abstract $session,
        DataPersister $dataPersister,
        DataLoader $dataLoader
    ) {
        $this->session = $session;
        $this->dataPersister = $dataPersister;
        $this->dataLoader = $dataLoader;
    }

    /**
     * @param mixed[] $extraFields
     * @param int $userId
     * @throws \Exception
     */
    public function saveExtraFields(array $extraFields, int $userId): void
    {
        $this->dataPersister->persist([
            self::EXTRA_FIELD_COLUMN => json_encode($extraFields)],
            self::USER_ATTRIBUTES_TABLE,
            $userId
        );
    }

    /**
     * @return mixed[]
     * @throws \Exception
     */
    public function getExtraFields() {
        $userId = $this->session->sUserId;

        if ($userId) {
            $userAttributes = $this->dataLoader->load(self::USER_ATTRIBUTES_TABLE, $userId);

            if (!empty($userAttributes[self::EXTRA_FIELD_COLUMN])) {
                return json_decode($userAttributes[self::EXTRA_FIELD_COLUMN], true);
            }
        }

        return [];
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function getSelectedIssuer(): ?int
    {
        return $this->getExtraFields()[self::FIELD_IDEAL_ISSUER] ?? null;
    }

    /**
     * @param int $userId
     * @throws \Exception
     */
    public function clearSelectedIssuer(int $userId): void
    {
        $extraFieldsData[self::FIELD_IDEAL_ISSUER] = 0;
        $newExtraFieldsData = array_merge($this->getExtraFields(), $extraFieldsData);

        $this->saveExtraFields($newExtraFieldsData, $userId);
    }
}
