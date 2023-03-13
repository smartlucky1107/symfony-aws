<?php

namespace App\Manager\Processor;

use App\Document\Blockchain\BitcoinSvTx;
use App\Manager\Blockchain\TxManager;
use App\Manager\NewDepositManager;
use App\Model\Blockchain\TxOutput;

class BitcoinSvTxProcessor
{
    /** @var TxManager */
    private $txManager;

    /** @var NewDepositManager */
    private $newDepositManager;

    /**
     * BitcoinSvTxProcessor constructor.
     * @param TxManager $txManager
     * @param NewDepositManager $newDepositManager
     */
    public function __construct(TxManager $txManager, NewDepositManager $newDepositManager)
    {
        $this->txManager = $txManager;
        $this->newDepositManager = $newDepositManager;
    }

    /**
     * @param BitcoinSvTx $bitcoinSvTx
     */
    public function processDeposits(BitcoinSvTx $bitcoinSvTx){
        if($bitcoinSvTx->getTxOutputs() > 0){
            foreach($bitcoinSvTx->getTxOutputs() as $txOutputItem){
                try{
                    /** @var TxOutput $txOutput */
                    $txOutput = new TxOutput((array) $txOutputItem);
                    if($txOutput->isValid()){
                        $this->newDepositManager->placeBlockchainDeposit($bitcoinSvTx->getTxHash(), $txOutput->getAddress(), $txOutput->getValue());
                    }
                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }
            }
        }

        $this->txManager->setBitcoinSvTxSuccess($bitcoinSvTx);
    }
}
