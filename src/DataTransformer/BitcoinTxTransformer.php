<?php

namespace App\DataTransformer;

use App\Document\Blockchain\BitcoinTx;
use App\Entity\Wallet\Deposit;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Model\Blockchain\TxOutput;
use App\Repository\WalletRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BitcoinTxTransformer
{
    /** @var TxManager */
    private $txManager;

    /**
     * BitcoinTxTransformer constructor.
     * @param TxManager $txManager
     */
    public function __construct(TxManager $txManager)
    {
        $this->txManager = $txManager;
    }

    /**
     * @param Request $request
     * @return BitcoinTx
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromRequest(Request $request) : BitcoinTx
    {
        $txHash = (string) $request->get('txHash', '');
        if(empty($txHash)) throw new AppException('TX hash not found');

        /** @var BitcoinTx $bitcoinTx */
        $bitcoinTx = $this->txManager->findBitcoinTx($txHash);
        if($bitcoinTx instanceof BitcoinTx) throw new AppException('Bitcoin TX ' . $txHash . ' already exists');

        $addresses = $request->get('addresses', []);
        if(!(is_array($addresses) && count($addresses) > 0)) throw new AppException('Addresses not found');

        $txOutputs = [];
        foreach ($addresses as $address){
            /** @var TxOutput $txOutput */
            $txOutput = new TxOutput((array) $address);
            if($txOutput->isValid()){
                $txOutputs[] = $txOutput;
            }
        }

        /** @var BitcoinTx $bitcoinTx */
        $bitcoinTx = new BitcoinTx($txHash, $txOutputs);

        return $bitcoinTx;
    }

    /**
     * @param array $txArray
     * @return BitcoinTx
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromArray(array $txArray) : BitcoinTx
    {
        $txHash = '';
        if(isset($txArray['txHash'])) $txHash = (string) $txArray['txHash'];
        if(empty($txHash)) throw new AppException('TX hash not found');

        $addresses = [];
        if(isset($txArray['addresses'])) $addresses = $txArray['addresses'];
        if(!(is_array($addresses) && count($addresses) > 0)) throw new AppException('Addresses not found');

        $txOutputs = [];
        foreach ($addresses as $address){
            /** @var TxOutput $txOutput */
            $txOutput = new TxOutput((array) $address);
            if($txOutput->isValid()){
                $txOutputs[] = $txOutput;
            }
        }

        /** @var BitcoinTx $bitcoinTx */
        $bitcoinTx = $this->txManager->findBitcoinTx($txHash);
        if($bitcoinTx instanceof BitcoinTx){
            if($txOutputs){
                /** @var TxOutput $txOutput */
                foreach ($txOutputs as $txOutput){
                    $exists = false;

                    foreach($bitcoinTx->getTxOutputs() as $bitcoinTxOutput){
                        /** @var TxOutput $bitcoinTxOutput */
                        $bitcoinTxOutput = new TxOutput((array) $bitcoinTxOutput);
                        if($bitcoinTxOutput->getAddress() === $txOutput->getAddress()){
                            $exists = true;
                        }
                    }

                    if(!$exists){
                        dump('updating '.$txHash);
                        $bitcoinTx->addTxOutput($txOutput);

                        $bitcoinTx->setProcessed(false);
                        $bitcoinTx->setSuccess(false);
                    }
                }
            }
        }else{
            /** @var BitcoinTx $bitcoinTx */
            $bitcoinTx = new BitcoinTx($txHash, $txOutputs);
        }

        return $bitcoinTx;
    }
}