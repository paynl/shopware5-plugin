<?php

namespace PaynlPayment\Models\Transaction;

use Shopware\Components\Model\ModelRepository;
use DateTime;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;

class Repository extends ModelRepository
{
    /**
     * Initialize a new transaction
     *
     * @param Customer $customer
     * @param int $paymentId
     * @param Payment $payment
     * @param string $signature
     * @param float $amount
     * @param string $currency
     * @return Transaction
     */
    public function createNew(Customer $customer, int $paymentId, Payment $payment, string $signature, float $amount, string $currency){
        $now = new DateTime();
        $transaction = new Transaction();

        $transaction->setCreatedAt($now);
        $transaction->setUpdatedAt($now);
        $transaction->setCustomer($customer);
        $transaction->setPaynlPaymentId($paymentId);
        $transaction->setPayment($payment);
        $transaction->setSignature($signature);
        $transaction->setAmount($amount);
        $transaction->setCurrency($currency);

        $this->save($transaction);

        return $transaction;
    }

    public function save(Transaction $transaction){
        $now = new DateTime();

        $transaction->setUpdatedAt($now);

        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }
}