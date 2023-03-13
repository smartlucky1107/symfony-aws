<?php

namespace App\DataTransformer;

use App\Document\Blockchain\BitcoinCashTx;
use App\Entity\Wallet\Deposit;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Model\Blockchain\TxOutput;
use App\Repository\WalletRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BitcoinCashTxTransformer
{
    /** @var TxManager */
    private $txManager;

    /**
     * BitcoinCashTxTransformer constructor.
     * @param TxManager $txManager
     */
    public function __construct(TxManager $txManager)
    {
        $this->txManager = $txManager;
    }

    /**
     * @param Request $request
     * @return BitcoinCashTx
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromRequest(Request $request) : BitcoinCashTx
    {
        $txHash = (string) $request->get('txHash', '');
        if(empty($txHash)) throw new AppException('TX hash not found');

        /** @var BitcoinCashTx $bitcoinCashTx */
        $bitcoinCashTx = $this->txManager->findBitcoinCashTx($txHash);
        if($bitcoinCashTx instanceof BitcoinCashTx) throw new AppException('Bitcoin TX ' . $txHash . ' already exists');

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

        /** @var BitcoinCashTx $bitcoinCashTx */
        $bitcoinCashTx = new BitcoinCashTx($txHash, $txOutputs);

        return $bitcoinCashTx;
    }

    /**
     * @param array $txArray
     * @return BitcoinCashTx
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromArray(array $txArray) : BitcoinCashTx
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

        /** @var BitcoinCashTx $bitcoinCashTx */
        $bitcoinCashTx = $this->txManager->findBitcoinCashTx($txHash);
        if($bitcoinCashTx instanceof BitcoinCashTx){
            if($txOutputs){
                /** @var TxOutput $txOutput */
                foreach ($txOutputs as $txOutput){
                    $exists = false;

                    foreach($bitcoinCashTx->getTxOutputs() as $bitcoinCashTxOutput){
                        /** @var TxOutput $bitcoinCashTxOutput */
                        $bitcoinCashTxOutput = new TxOutput((array) $bitcoinCashTxOutput);
                        if($bitcoinCashTxOutput->getAddress() === $txOutput->getAddress()){
                            $exists = true;
                        }
                    }

                    if(!$exists){
                        dump('updating '.$txHash);
                        $bitcoinCashTx->addTxOutput($txOutput);

                        $bitcoinCashTx->setProcessed(false);
                        $bitcoinCashTx->setSuccess(false);
                    }
                }
            }
        }else{
            /** @var BitcoinCashTx $bitcoinCashTx */
            $bitcoinCashTx = new BitcoinCashTx($txHash, $txOutputs);
        }

        return $bitcoinCashTx;
    }
}
