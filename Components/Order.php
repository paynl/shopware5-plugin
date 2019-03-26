<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 20-3-19
 * Time: 11:48
 */

namespace PaynlPayment\Components;


use PaynlPayment\Models\Transaction\Transaction;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article;
use Shopware\Models\Order\Detail;

class Order
{

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var \Shopware\Models\Order\Repository
     */
    private $orderRepository;


    private $articleDetailRepository;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->orderRepository = $modelManager->getRepository(\Shopware\Models\Order\Order::class);
        $this->articleDetailRepository = $modelManager->getRepository(Article\Detail::class);
    }

    public function unCancelOrder(Transaction $transaction)
    {
        if (!$transaction->isRestocked()) return; // no need to un-cancel
        $order = $transaction->getOrder();
        if (!empty($order)) {
            $orderComment = $order->getInternalComment();
            $orderDetails = $order->getDetails();
            if (!empty($orderDetails)) {
                /** @var Detail $orderDetail */
                foreach ($orderDetails as $orderDetail) {
                    $this->subtractOrderDetail($orderDetail);
                }
                $transaction->setIsRestocked(false);
                $this->modelManager->persist($transaction);
                $orderComment .= "The order has been uncanceled\n";
                $order->setInternalComment($orderComment);
                $this->modelManager->persist($order);

                $this->modelManager->flush();
            }
        }
    }

    public function restockOrder(Transaction $transaction)
    {
        if ($transaction->isRestocked()) return; // already restocked
        $order = $transaction->getOrder();
        if (!empty($order)) {
            $orderComment = $order->getInternalComment();
            $orderDetails = $order->getDetails();
            if (!empty($orderDetails)) {
                /** @var Detail $orderDetail */
                foreach ($orderDetails as $orderDetail) {
                    $this->restockOrderDetail($orderDetail);
                }
                $transaction->setIsRestocked(true);
                $this->modelManager->persist($transaction);
                $orderComment .= "The order has been restocked\n";
                $order->setInternalComment($orderComment);
                $this->modelManager->persist($order);

                $this->modelManager->flush();
            }
        }
    }

    private function subtractOrderDetail(Detail $orderDetail)
    {
        $quantity = $orderDetail->getQuantity();
        /** @var Article\Detail $article */
        $article = $this->articleDetailRepository->findOneBy(['number' => $orderDetail->getArticleNumber()]);
        if (!empty($article)) {
            $article->setInStock($article->getInStock() - $quantity);
            $this->modelManager->persist($article);
        }
        return $orderDetail;
    }

    private function restockOrderDetail(Detail $orderDetail)
    {
        $quantity = $orderDetail->getQuantity();
        /** @var Article\Detail $article */
        $article = $this->articleDetailRepository->findOneBy(['number' => $orderDetail->getArticleNumber()]);
        if (!empty($article)) {
            $article->setInStock($article->getInStock() + $quantity);
            $this->modelManager->persist($article);
        }
        return $orderDetail;
    }

    public function getStock(\Shopware\Models\Order\Order $order)
    {
        $result = [];

        $orderDetails = $order->getDetails();
        if (empty($orderDetails)) return $result;

        /** @var Detail $orderDetail */
        foreach ($orderDetails as $orderDetail) {
            /** @var Article\Detail $articleDetail */
            $articleDetail = $this->articleDetailRepository->findOneBy(['number' => $orderDetail->getArticleNumber()]);

            $stock = $articleDetail->getInStock();
            $result[] = [
                'articleDetail' => $articleDetail,
                'quantity'=> $orderDetail->getQuantity(),
                'stock' => $stock
            ];
        }
        return $result;
    }

}