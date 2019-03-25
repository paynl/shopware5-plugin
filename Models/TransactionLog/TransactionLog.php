<?php
namespace PaynlPayment\Models\TransactionLog;

use Doctrine\ORM\Mapping as ORM;
use PaynlPayment\Models\Transaction\Transaction;
use Shopware\Models\Order\Status;

/**
 * Class TransactionLog
 * @package PaynlPayment\Models\TransactionLog
 *
 * @ORM\Table(name="paynl_transaction_log")
 * @ORM\Entity(repositoryClass="PaynlPayment\Models\TransactionLog\Repository")
 */
class TransactionLog
{
    /**
     * @var int
     * @ORM\Column(name="id", nullable=false, type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Transaction
     * @ORM\ManyToOne(targetEntity="PaynlPayment\Models\Transaction\Transaction")
     * @ORM\JoinColumn(nullable=false)
     */
    private $transaction;
    /**
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Status")
     * @ORM\JoinColumn(nullable=true)
     */
    private $statusBefore;
    /**
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Status")
     * @ORM\JoinColumn(nullable=false)
     */
    private $statusAfter;
    /**
     * @ORM\Column(type="datetime", name="created_at", nullable=false)
     */
    private $createdAt;


    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param Transaction $transaction
     */
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return mixed
     */
    public function getStatusBefore()
    {
        return $this->statusBefore;
    }

    /**
     * @param mixed $statusBefore
     */
    public function setStatusBefore($statusBefore)
    {
        $this->statusBefore = $statusBefore;
    }
    /**
     * @param int $statusId
     */
    public function setStatusBeforeById($statusId)
    {
        $status = \Shopware()->Models()->getRepository(Status::class)->find($statusId);
        $this->setStatusBefore($status);
    }

    /**
     * @return mixed
     */
    public function getStatusAfter()
    {
        return $this->statusAfter;
    }

    /**
     * @param int $statusId
     */
    public function setStatusAfterById($statusId)
    {
        $status = \Shopware()->Models()->getRepository(Status::class)->find($statusId);
        $this->setStatusAfter($status);
    }

    /**
     * @param mixed $statusAfter
     */
    public function setStatusAfter($statusAfter)
    {
        $this->statusAfter = $statusAfter;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}