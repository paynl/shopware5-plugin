<?php

namespace PaynlPayment\Models\Banks;

use Shopware\Components\Model\ModelRepository;
use DateTime;

class Repository extends ModelRepository
{
    /**
     * Initialize a new transaction
     *
     * @param int $paymentMethodId
     * @param array $paymentMethodBanks
     * @return Banks
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function upsert(int $paymentMethodId, array $paymentMethodBanks)
    {
        $banks = $this->findOneBy(['paymentMethodId' => $paymentMethodId]);
        if (empty($banks)) {
            $banks = new Banks();
            $banks->setCreatedAt(new DateTime());
            $banks->setPaymentMethodId($paymentMethodId);
        }

        $banks->setBanks($paymentMethodBanks);
        $this->persist($banks);

        return $banks;
    }

    /**
     * @param Banks $banks
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persist(Banks $banks)
    {
        $banks->setUpdatedAt(new DateTime());

        $this->getEntityManager()->persist($banks);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int $paymentMethodId
     * @return array
     */
    public function getBanksDataByPaymentMethodId(int $paymentMethodId)
    {
        return $this->findOneBy(['paymentMethodId' => $paymentMethodId])->getBanks();
    }
}
