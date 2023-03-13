<?php

namespace App\DataTransformer;

use App\Document\Blockchain\EthereumTx;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use Symfony\Component\HttpFoundation\Request;

class EthereumTxTransformer
{
    /** @var TxManager */
    private $txManager;

    /**
     * EthereumTxTransformer constructor.
     * @param TxManager $txManager
     */
    public function __construct(TxManager $txManager)
    {
        $this->txManager = $txManager;
    }

    /**
     * @param Request $request
     * @return EthereumTx
     * @throws AppException
     */
    public function transferFromRequest(Request $request) : EthereumTx
    {
        $txHash = (string) $request->get('txHash', '');
        if(empty($txHash)) throw new AppException('TX hash not found');

        /** @var EthereumTx $ethereumTx */
        $ethereumTx = $this->txManager->findEthereumTx($txHash);
        if($ethereumTx instanceof EthereumTx) throw new AppException('Ethereum TX ' . $txHash . ' already exists');

        $address = (string) $request->get('address', '');
        $value = (string) $request->get('value', '');

        $smartContractAddress = $request->get('smartContractAddress');

        /** @var EthereumTx $ethereumTx */
        $ethereumTx = new EthereumTx($txHash, $address, $value, $smartContractAddress);

        return $ethereumTx;
    }

    /**
     * @param array $txArray
     * @return EthereumTx
     * @throws AppException
     */
    public function transformFromArray(array $txArray) : EthereumTx
    {
        $txHash = '';
        if(isset($txArray['txHash'])) $txHash = (string) $txArray['txHash'];
        if(empty($txHash)) throw new AppException('TX hash not found');

        /** @var EthereumTx $ethereumTx */
        $ethereumTx = $this->txManager->findEthereumTx($txHash);
        if($ethereumTx instanceof EthereumTx) throw new AppException('Ethereum TX ' . $txHash . ' already exists');

        $address = '';
        if(isset($txArray['address'])) $address = (string) $txArray['address'];

        $value = '';
        if(isset($txArray['value'])) $value = (string) $txArray['value'];

        $smartContractAddress = null;
        if(isset($txArray['smartContractAddress'])) $smartContractAddress = $txArray['smartContractAddress'];

        /** @var EthereumTx $ethereumTx */
        $ethereumTx = new EthereumTx($txHash, $address, $value, $smartContractAddress);

        return $ethereumTx;
    }
}
