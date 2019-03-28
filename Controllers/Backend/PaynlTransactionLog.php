<?php

use PaynlPayment\Models\Transaction;
use PaynlPayment\Models\TransactionLog;

class Shopware_Controllers_Backend_PaynlTransactionLog extends Enlight_Controller_Action implements \Shopware\Components\CSRFWhitelistAware
{
    public function preDispatch()
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');
    }

    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    public function indexAction()
    {
        /** @var Transaction\Repository $transactionRepository */
        $transactionRepository = $this->getModelManager()->getRepository(Transaction\Transaction::class);

        $transactionLogRepository = $this->getModelManager()->getRepository(TransactionLog\TransactionLog::class);


        $transactionId = $this->request->getParam('id');
        $paynlPaymentId = $this->request->getParam('paynlPaymentId');
        if(!empty($transactionId)) {
            $transaction = $transactionRepository->findOneBy(['id' => $transactionId]);
        } elseif(!empty($paynlPaymentId)){
            $transaction = $transactionRepository->findOneBy(['paynlPaymentId' => $paynlPaymentId]);
        }
        /** @var Transaction\Transaction $transaction */


        /** @var TransactionLog\TransactionLog[] $statusLog */
        $statusLog = $transactionLogRepository->findBy(['transaction' => $transaction]);

        $viewVars = [];
        $viewVars['transactionId'] = $transaction->getTransactionId();
        $viewVars['statusChanges'] = [];
        foreach ($statusLog as $log) {
            $statusChange = [
                'createdAt' => $log->getCreatedAt()->format('d-m-Y H:i:s'),
                'statusBefore' => $log->getStatusBefore()?$log->getStatusBefore()->getName():'',
                'statusAfter' => $log->getStatusAfter()->getName(),
                'products' => []
            ];
            $details = $log->getDetails();
            foreach ($details as $detail) {
                $product = [
                    'id' => $detail->getArticleDetail()->getId(),
                    'name' => $detail->getArticleDetail()->getArticle()->getName(),
                    'stockBefore' => $detail->getStockBefore(),
                    'stockAfter' => $detail->getStockAfter()
                ];
                array_push($statusChange['products'], $product);
            }
            array_push($viewVars['statusChanges'], $statusChange);
        }
//var_dump($viewVars);
        $this->view->assign($viewVars);

    }
}