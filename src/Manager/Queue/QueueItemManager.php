<?php

namespace App\Manager\Queue;

use App\Document\TradingTransaction;
use App\Document\WalletTransferBatch;
use App\Document\WithdrawalRequest;
use App\Document\WithdrawalApproveRequest;
use App\Document\InternalTransferRequest;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class QueueItemManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * QueueItemManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function dmClear(){
        $this->dm->clear();
    }

    /**
     * Load not processed trading transactions for Redis processing
     *
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findNotProcessedTradingTransactions(){
        $qb = $this->dm->createQueryBuilder(TradingTransaction::class);
        $qb->field('processed')->notEqual(true);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $id
     * @return bool
     */
    public function setTradingTransactionProcessed(string $id) : bool
    {
        /** @var TradingTransaction $tradingTransaction */
        $tradingTransaction = $this->dm->find(TradingTransaction::class, $id);
        if($tradingTransaction instanceof TradingTransaction){
            $tradingTransaction->setProcessed(true);
            $this->save($tradingTransaction);

            $tradingTransaction = null;
            unset($tradingTransaction);

            return true;
        }
        unset($tradingTransaction);

        return false;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findNotProcessedWithdrawalRequests(){
        $qb = $this->dm->createQueryBuilder(WithdrawalRequest::class);
        $qb->field('processed')->notEqual(true);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $id
     * @return WithdrawalRequest|null
     */
    public function setWithdrawalRequestProcessed(string $id) : ?WithdrawalRequest
    {
        /** @var WithdrawalRequest $withdrawalRequest */
        $withdrawalRequest = $this->dm->find(WithdrawalRequest::class, $id);
        if($withdrawalRequest instanceof WithdrawalRequest){
            $withdrawalRequest->setProcessed(true);
            return $this->save($withdrawalRequest);
        }

        return null;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findNotProcessedWithdrawalApproveRequests(){
        $qb = $this->dm->createQueryBuilder(WithdrawalApproveRequest::class);
        $qb->field('processed')->notEqual(true);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $id
     * @return WithdrawalApproveRequest|null
     */
    public function setWithdrawalApproveRequestProcessed(string $id) : ?WithdrawalApproveRequest
    {
        /** @var WithdrawalApproveRequest $withdrawalApproveRequest */
        $withdrawalApproveRequest = $this->dm->find(WithdrawalApproveRequest::class, $id);
        if($withdrawalApproveRequest instanceof WithdrawalApproveRequest){
            $withdrawalApproveRequest->setProcessed(true);
            return $this->save($withdrawalApproveRequest);
        }

        return null;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findNotProcessedInternalTransferRequests(){
        $qb = $this->dm->createQueryBuilder(InternalTransferRequest::class);
        $qb->field('processed')->notEqual(true);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $id
     * @return InternalTransferRequest|null
     */
    public function setInternalTransferRequestProcessed(string $id) : ?InternalTransferRequest
    {
        /** @var InternalTransferRequest $internalTransferRequest */
        $internalTransferRequest = $this->dm->find(InternalTransferRequest::class, $id);
        if($internalTransferRequest instanceof InternalTransferRequest){
            $internalTransferRequest->setProcessed(true);
            return $this->save($internalTransferRequest);
        }

        return null;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findNotProcessedWalletTransferBatches(){
        $qb = $this->dm->createQueryBuilder(WalletTransferBatch::class);
        $qb->field('processed')->notEqual(true);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $id
     * @return WalletTransferBatch|null
     */
    public function setWalletTransferBatchProcessed(string $id) : ?WalletTransferBatch
    {
        /** @var WalletTransferBatch $walletTransferBatch */
        $walletTransferBatch = $this->dm->find(WalletTransferBatch::class, $id);
        if($walletTransferBatch instanceof WalletTransferBatch){
            $walletTransferBatch->setProcessed(true);
            return $this->save($walletTransferBatch);
        }

        return null;
    }

    /**
     * @param WalletTransferBatch $walletTransferBatch
     * @return bool
     */
    public function setWalletTransferBatchSuccess(WalletTransferBatch $walletTransferBatch) : bool
    {
        $walletTransferBatch->setSuccess(true);
        $this->save($walletTransferBatch);

        $walletTransferBatch = null;
        unset($walletTransferBatch);

        return true;
    }

    /**
     * @param $object
     * @return mixed
     */
    public function save($object){

        $this->dm->persist($object);
        $this->dm->flush();

        return $object;
    }
}
