<?php

namespace App\Manager\Processor;

use App\Document\Blockchain\BitcoinCashTx;
use App\Manager\Blockchain\TxManager;
use App\Manager\NewDepositManager;
use App\Model\Blockchain\TxOutput;

class BitcoinCashTxProcessor
{
    /** @var TxManager */
    private $txManager;

    /** @var NewDepositManager */
    private $newDepositManager;

    /**
     * BitcoinCashTxProcessor constructor.
     * @param TxManager $txManager
     * @param NewDepositManager $newDepositManager
     */
    public function __construct(TxManager $txManager, NewDepositManager $newDepositManager)
    {
        $this->txManager = $txManager;
        $this->newDepositManager = $newDepositManager;
    }

    /**
     * @param BitcoinCashTx $bitcoinCashTx
     */
    public function processDeposits(BitcoinCashTx $bitcoinCashTx){
        if($bitcoinCashTx->getTxOutputs() > 0){
            foreach($bitcoinCashTx->getTxOutputs() as $txOutputItem){
                try{
                    /** @var TxOutput $txOutput */
                    $txOutput = new TxOutput((array) $txOutputItem);
                    if($txOutput->isValid()){
                        $this->newDepositManager->placeBlockchainDeposit($bitcoinCashTx->getTxHash(), $txOutput->getAddress(), $txOutput->getValue());
                    }
                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }
            }
        }

        $this->txManager->setBitcoinCashTxSuccess($bitcoinCashTx);
    }
}
