<?php

namespace PaynlPayment\Models\Transaction;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment\Payment;

/**
 * Class Transaction
 * @package PaynlPayment\Models
 * @ORM\Entity(repositoryClass="PaynlPayment\Models\Transaction\Repository")
 * @ORM\Table(name="paynl_transactions")
 */
class Transaction
{
    const STATUS_PENDING = 17;
    const STATUS_CANCEL = 35;
    const STATUS_PAID = 12;
    const STATUS_NEEDS_REVIEW = 21;
    const STATUS_REFUND = 20;
    const STATUS_AUTHORIZED = 18;

    /**
     * @var int
     * @ORM\Column(name="id", nullable=false, type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Customer\Customer")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="paynl_payment_id", nullable=false)
     */
    private $paynlPaymentId;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Order")
     * @ORM\JoinColumn(nullable=true)
     */
    private $order;

    /**
     * @var Payment
     *
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Payment\Payment")
     * @ORM\JoinColumn(nullable=false)
     */
    private $payment;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true, name="transaction_id", type="string", length=20)
     */
    private $transactionId;
    /**
     * @var string
     *
     * @ORM\Column(length=70, name="signature", type="string", nullable=false)
     */
    private $signature;
    /**
     * @var float
     *
     * @ORM\Column(type="float", name="amount")
     */
    private $amount;
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="currency", length=3, nullable=false)
     */
    private $currency;

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
     * @var array
     *
     * @ORM\Column(type="json_array", name="exceptions", nullable=true)
     */
    private $exceptions = [];

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Status")
     * @ORM\JoinColumn(nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="s_comment", nullable=true)
     */
    private $sComment;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="s_dispatch", nullable=true)
     */
    private $sDispatch;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;

    }

    /**
     * @return string
     */
    public function getSComment()
    {
        return $this->sComment;
    }

    /**
     * @param string $sComment
     */
    public function setSComment($sComment)
    {
        $this->sComment = $sComment;
    }

    /**
     * @return string
     */
    public function getSDispatch()
    {
        return $this->sDispatch;
    }

    /**
     * @param string $sDispatch
     */
    public function setSDispatch($sDispatch)
    {
        $this->sDispatch = $sDispatch;
    }


    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return int
     */
    public function getPaynlPaymentId()
    {
        return $this->paynlPaymentId;
    }

    /**
     * @param int $paynlPaymentId
     */
    public function setPaynlPaymentId($paynlPaymentId)
    {
        $this->paynlPaymentId = $paynlPaymentId;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     */
    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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

    /**
     * @return array
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * @param $exception
     */
    public function addException($exception)
    {
        array_push($this->exceptions, $exception);
    }

    /**
     * @param array $exceptions
     */
    public function setExceptions($exceptions)
    {
        $this->exceptions = $exceptions;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $statusId
     */
    public function setStatusById($statusId){
        $status = \Shopware()->Models()->getRepository(Status::class)->find($statusId);
        $this->setStatus($status);
    }
    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }


}