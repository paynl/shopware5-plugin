<?php

namespace PaynlPayment\Models\Banks;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Transaction
 * @package PaynlPayment\Models
 * @ORM\Entity(repositoryClass="PaynlPayment\Models\Banks\Repository")
 * @ORM\Table(name="s_plugin_paynlpayment_payment_method_banks")
 */
class Banks
{
    /**
     * @var int
     * @ORM\Column(name="id", nullable=false, type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="payment_method_id", nullable=false)
     */
    private $paymentMethodId;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", name="banks", nullable=true)
     */
    private $banks = [];

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created_at", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="updated_at", nullable=false)
     */
    private $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPaymentMethodId()
    {
        return $this->paymentMethodId;
    }

    /**
     * @param int $paymentMethodId
     */
    public function setPaymentMethodId($paymentMethodId)
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * @return array
     */
    public function getBanks()
    {
        return $this->banks;
    }

    /**
     * @param array $banks
     */
    public function setBanks($banks)
    {
        $this->banks = $banks;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }
}
