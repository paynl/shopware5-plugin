<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 20-3-19
 * Time: 15:09
 */

namespace PaynlPayment\Subscriber;


use Aws\Chime\ChimeClient;
use Enlight\Event\SubscriberInterface;
use PaynlPayment\Components\Config;
use Shopware\Models\Order;
use PaynlPayment\Models\Transaction;
use Shopware\Components\Model\ModelManager;

class OrderMailRegistration implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Transaction\Repository
     */
    private $transactionRepository;

    /**
     * @var Order\Repository
     */
    private $orderRepository;


    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;

        $this->transactionRepository = $modelManager->getRepository(Transaction\Transaction::class);
        $this->orderRepository = $modelManager->getRepository(Order\Order::class);
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send' => 'onSendOrderMail',
        ];
    }

    public function onSendOrderMail(\Enlight_Event_EventArgs $args)
    {
        /** @var Config $config */
        $config = \Shopware()->Container()->get('paynl_payment.config');

        $variables = $args->get('variables');
        $paymentId = (isset($variables['sBookingID']) ? $variables['sBookingID'] : null);
        /** @var Transaction\Transaction $transaction */
        $transaction = $this->transactionRepository->findOneBy([
            'paynlPaymentId' => $paymentId
        ]);
        /** @var Order\Order $order */
        $order = $this->orderRepository->findOneBy([
            'transactionId' => $paymentId
        ]);

        if (!empty($transaction) && !empty($order)) {
            $paymentName = $order->getPayment()->getName();
            $payment_status_id = $transaction->getStatus()?$transaction->getStatus()->getId():$order->getPaymentStatus()->getId();
            if (substr($paymentName, 0, 5) == 'paynl') {

                if ($payment_status_id == Transaction\Transaction::STATUS_PENDING && $config->placeOrderOnStart() == true) {
                    $transaction->setOrderMailVariables($variables);
                    $transaction->setIsOrderMailSent(false);
                    $this->transactionRepository->save($transaction);
                    return false;
                } else {
                    $transaction->setIsOrderMailSent(true);
                    $this->transactionRepository->save($transaction);
                }
            }
        }
    }
}