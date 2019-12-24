<?php

class Shopware_Controllers_Backend_PaynlTransactions extends Shopware_Controllers_Backend_Application
{
    protected $model = PaynlPayment\Models\Transaction\Transaction::class;
    protected $alias = 's_plugin_paynl_transactions';

    protected $filterFields = ['paynlPaymentId', 'transactionId', 'amount', 'customer.firstname', 'customer.lastname', 's_order.number'];

    protected function getListQuery()
    {
        $builder = parent::getListQuery();

        $builder->leftJoin('s_plugin_paynl_transactions.status', 'status')
                ->leftJoin('s_plugin_paynl_transactions.customer', 'customer')
                ->leftJoin('s_plugin_paynl_transactions.order', 's_order');
        $builder->addSelect([
            'status',
            'customer',
            's_order'
        ]);

        return $builder;
    }

}