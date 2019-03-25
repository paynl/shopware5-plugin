<?php

namespace PaynlPayment\Models\TransactionLog;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Article\Detail as ArticleDetail;

/**
 * Class Detail
 * @package PaynlPayment\Models\TransactionLog
 *
 * @ORM\Table(name="paynl_transaction_log_detail")
 * @ORM\Entity(repositoryClass="PaynlPayment\Models\TransactionLog\DetailRepository")
 */
class Detail
{
    /**
     * @var int
     * @ORM\Column(name="id", nullable=false, type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var TransactionLog
     *
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="PaynlPayment\Models\TransactionLog\TransactionLog")
     */
    private $transactionLog;

    /**
     * @var ArticleDetail
     *
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Article\Detail")
     */
    private $articleDetail;
    /**
     * @var int
     *
     * @ORM\Column(name="stock_before", type="integer", nullable=false)
     */
    private $stockBefore;
    /**
     * @var int
     *
     * @ORM\Column(name="stock_after", type="integer", nullable=false)
     */
    private $stockAfter;

    /**
     * @return TransactionLog
     */
    public function getTransactionLog()
    {
        return $this->transactionLog;
    }

    /**
     * @param TransactionLog $transactionLog
     */
    public function setTransactionLog($transactionLog)
    {
        $this->transactionLog = $transactionLog;
    }

    /**
     * @return ArticleDetail
     */
    public function getArticleDetail()
    {
        return $this->articleDetail;
    }

    /**
     * @param ArticleDetail $articleDetail
     */
    public function setArticleDetail($articleDetail)
    {
        $this->articleDetail = $articleDetail;
    }

    /**
     * @return int
     */
    public function getStockBefore()
    {
        return $this->stockBefore;
    }

    /**
     * @param int $stockBefore
     */
    public function setStockBefore($stockBefore)
    {
        $this->stockBefore = $stockBefore;
    }

    /**
     * @return int
     */
    public function getStockAfter()
    {
        return $this->stockAfter;
    }

    /**
     * @param int $stockAfter
     */
    public function setStockAfter($stockAfter)
    {
        $this->stockAfter = $stockAfter;
    }

    
}