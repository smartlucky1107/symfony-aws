<?php

namespace App\Manager\Processor;

use App\Document\Blockchain\EthereumTx;
use App\Manager\Blockchain\TxManager;
use App\Manager\NewDepositManager;
use App\Exception\AppException;

class EthereumTxProcessor
{
    /** @var TxManager */
    private $txManager;

    /** @var NewDepositManager */
    private $newDepositManager;

    /**
     * EthereumTxProcessor constructor.
     * @param TxManager $txManager
     * @param NewDepositManager $newDepositManager
     */
    public function __construct(TxManager $txManager, NewDepositManager $newDepositManager)
    {
        $this->txManager = $txManager;
        $this->newDepositManager = $newDepositManager;
    }

    /**
     * @param EthereumTx $ethereumTx
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processDeposits(EthereumTx $ethereumTx){
        $this->newDepositManager->placeBlockchainDeposit($ethereumTx->getTxHash(), $ethereumTx->getAddress(), $ethereumTx->getValue());

        $this->txManager->setEthereumTxSuccess($ethereumTx);
    }
}
