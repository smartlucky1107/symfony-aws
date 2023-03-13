<?php

namespace App\DataTransformer;

use App\Document\Blockchain\BitcoinSvTx;
use App\Entity\Wallet\Deposit;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Model\Blockchain\TxOutput;
use App\Repository\WalletRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BitcoinSvTxTransformer
{
    /** @var TxManager */
    private $txManager;

    /**
     * BitcoinSvTxTransformer constructor.
     * @param TxManager $txManager
     */
    public function __construct(TxManager $txManager)
    {
        $this->txManager = $txManager;
    }

    /**
     * @param Request $request
     * @return BitcoinSvTx
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromRequest(Request $request) : BitcoinSvTx
    {
        $txHash = (string) $request->get('txHash', '');
        if(empty($txHash)) throw new AppException('TX hash not found');

        /** @var BitcoinSvTx $bitcoinSvTx */
        $bitcoinSvTx = $this->txManager->findBitcoinSvTx($txHash);
        if($bitcoinSvTx instanceof BitcoinSvTx) throw new AppException('Bitcoin TX ' . $txHash . ' already exists');

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

        /** @var BitcoinSvTx $bitcoinSvTx */
        $bitcoinSvTx = new BitcoinSvTx($txHash, $txOutputs);

        return $bitcoinSvTx;
    }

    /**
     * @param array $txArray
     * @return BitcoinSvTx
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromArray(array $txArray) : BitcoinSvTx
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

        /** @var BitcoinSvTx $bitcoinSvTx */
        $bitcoinSvTx = $this->txManager->findBitcoinSvTx($txHash);
        if($bitcoinSvTx instanceof BitcoinSvTx){
            if($txOutputs){
                /** @var TxOutput $txOutput */
                foreach ($txOutputs as $txOutput){
                    $exists = false;

                    foreach($bitcoinSvTx->getTxOutputs() as $bitcoinSvTxOutput){
                        /** @var TxOutput $bitcoinSvTxOutput */
                        $bitcoinSvTxOutput = new TxOutput((array) $bitcoinSvTxOutput);
                        if($bitcoinSvTxOutput->getAddress() === $txOutput->getAddress()){
                            $exists = true;
                        }
                    }

                    if(!$exists){
                        dump('updating '.$txHash);
                        $bitcoinSvTx->addTxOutput($txOutput);

                        $bitcoinSvTx->setProcessed(false);
                        $bitcoinSvTx->setSuccess(false);
                    }
                }
            }
        }else{
            /** @var BitcoinSvTx $bitcoinSvTx */
            $bitcoinSvTx = new BitcoinSvTx($txHash, $txOutputs);
        }

        return $bitcoinSvTx;
    }
}
