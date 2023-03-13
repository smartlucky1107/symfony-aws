<?php

namespace App\Manager\Processor;

use App\Document\Blockchain\BitcoinTx;
use App\Manager\Blockchain\TxManager;
use App\Manager\NewDepositManager;
use App\Model\Blockchain\TxOutput;

class BitcoinTxProcessor
{
    /** @var TxManager */
    private $txManager;

    /** @var NewDepositManager */
    private $newDepositManager;

    /**
     * BitcoinTxProcessor constructor.
     * @param TxManager $txManager
     * @param NewDepositManager $newDepositManager
     */
    public function __construct(TxManager $txManager, NewDepositManager $newDepositManager)
    {
        $this->txManager = $txManager;
        $this->newDepositManager = $newDepositManager;
    }

    /**
     * @param BitcoinTx $bitcoinTx
     */
    public function processDeposits(BitcoinTx $bitcoinTx){
        if($bitcoinTx->getTxOutputs() > 0){
            foreach($bitcoinTx->getTxOutputs() as $txOutputItem){
                try{
                    /** @var TxOutput $txOutput */
                    $txOutput = new TxOutput((array) $txOutputItem);
                    if($txOutput->isValid()){
                        $this->newDepositManager->placeBlockchainDeposit($bitcoinTx->getTxHash(), $txOutput->getAddress(), $txOutput->getValue());
                    }
                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }
            }
        }

        $this->txManager->setBitcoinTxSuccess($bitcoinTx);
    }
}