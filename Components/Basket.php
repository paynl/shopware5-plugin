<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 19-3-19
 * Time: 17:33
 */

namespace PaynlPayment\Components;


use PaynlPayment\Models\Transaction\Transaction;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Voucher;

class Basket
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Voucher\Repository
     */
    private $voucherRepository;

    /**
     * @var \sBasket
     */
    private $basketModule;

    /**
     * @var \sOrder
     */
    private $orderModule;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->voucherRepository = $modelManager->getRepository(Voucher\Voucher::class);
        $this->basketModule = Shopware()->Modules()->Basket();
        $this->orderModule = Shopware()->Modules()->Order();
    }

    public function restoreBasket(Transaction $transaction)
    {
        $order = $transaction->getOrder();
        if (!empty($order)) {
            $orderComment = $order->getInternalComment();

            $orderDetails = $order->getDetails();
            if (!empty($orderDetails)) {
                $this->basketModule->clearBasket();

                /** @var Detail $orderDetail */
                foreach($orderDetails as $orderDetail){

                    if($orderDetail->getMode() == 2){
                        /** @var Voucher\Voucher $voucher */
                        $voucher = $this->voucherRepository->findOneBy(['id' => $orderDetail->getArticleId()]);

                        $this->removeOrderDetail($orderDetail->getId());
                        $orderComment .= PHP_EOL.PHP_EOL."Removed voucher ({$voucher->getVoucherCode()}) from the order and added to the new basket";
                        $this->basketModule->sAddVoucher($voucher->getVoucherCode());
                    } else {
                        $this->basketModule->sAddArticle(
                            $orderDetail->getArticleNumber(),
                            $orderDetail->getQuantity()
                        );
                    }
                }
                $order->setInternalComment($orderComment);
                $this->modelManager->persist($order);
                $this->modelManager->flush();
            }

        }

        $this->basketModule->sRefreshBasket();
    }

    public function removeOrderDetail($orderDetailId)
    {
        $result = null;

        try {
            $db = shopware()->container()->get('db');

            $q = $db->prepare('
                DELETE FROM 
                s_order_details 
                WHERE id=?
            ');

            $result = $q->execute([
                $orderDetailId,
            ]);
        }
        catch (Exception $ex) {

        }

        return $result;
    }
}