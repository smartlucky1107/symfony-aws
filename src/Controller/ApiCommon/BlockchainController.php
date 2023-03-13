<?php

namespace App\Controller\ApiCommon;

use App\DataTransformer\BitcoinTxTransformer;
use App\DataTransformer\EthereumTxTransformer;
use App\Document\Blockchain\BitcoinTx;
use App\Document\Blockchain\EthereumTx;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Model\Blockchain\TxOutput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BlockchainController extends AbstractController
{
    /**
     * @param Request $request
     * @param TxManager $txManager
     * @param EthereumTxTransformer $ethereumTxTransformer
     * @return JsonResponse
     * @throws AppException
     */
    public function postBlockchainEthereumTx(Request $request, TxManager $txManager, EthereumTxTransformer $ethereumTxTransformer) : JsonResponse
    {
        /** @var EthereumTx $ethereumTx */
        $ethereumTx = $ethereumTxTransformer->transferFromRequest($request);

        try{
            $ethereumTx = $txManager->save($ethereumTx);
        }catch (\Exception $exception){
            throw new AppException('Cannot save transaction '.$exception->getMessage());
        }

        return new JsonResponse(['ethereumTx' => $ethereumTx->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param TxManager $txManager
     * @param BitcoinTxTransformer $bitcoinTxTransformer
     * @return JsonResponse
     * @throws AppException
     */
    public function postBlockchainBitcoinTx(Request $request, TxManager $txManager, BitcoinTxTransformer $bitcoinTxTransformer) : JsonResponse
    {
        /** @var BitcoinTx $bitcoinTx */
        $bitcoinTx = $bitcoinTxTransformer->transformFromRequest($request);

        try{
            $bitcoinTx = $txManager->save($bitcoinTx);
        }catch (\Exception $exception){
            throw new AppException('Cannot save transaction '.$exception->getMessage());
        }

        return new JsonResponse(['bitcoinTx' => $bitcoinTx->serialize()], JsonResponse::HTTP_CREATED);
    }
}