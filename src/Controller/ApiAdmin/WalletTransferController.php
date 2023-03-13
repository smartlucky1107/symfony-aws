<?php

namespace App\Controller\ApiAdmin;

use App\Document\WalletTransferBatch;
use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Wallet;
use App\Manager\WalletTransferManager;
use App\Repository\OrderBook\TradeRepository;
use App\Resolver\FeeWalletResolver;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WalletTransferController extends FOSRestController
{
    /**
     * @Rest\Get("/wallet-transfers/not-processed", options={"expose"=true})
     *
     * @param WalletTransferManager $walletTransferManager
     * @return View
     */
    public function getWalletTransfersNotProcessed(WalletTransferManager $walletTransferManager) : View
    {
        // TODO dodać ACL

        $results = [];

        $walletTransfers = $walletTransferManager->findNotProcessed();
        if($walletTransfers){
            /** @var WalletTransferBatch $walletTransferBatch */
            foreach($walletTransfers as $walletTransferBatch){
                $results[] = $walletTransferBatch->serialize();
            }
        }

        return $this->view(['walletTransfers' => $results], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/wallet-transfers/by-trade/{tradeId}", options={"expose"=true})
     *
     * @param int $tradeId
     * @param WalletTransferManager $walletTransferManager
     * @param TradeRepository $tradeRepository
     * @param FeeWalletResolver $feeWalletResolver
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getWalletTransferByTrade(int $tradeId, WalletTransferManager $walletTransferManager, TradeRepository $tradeRepository, FeeWalletResolver $feeWalletResolver) : View
    {
        // TODO dodać ACL

        $result = null;

        /** @var WalletTransferBatch $walletTransferBatch */
        $walletTransferBatch = $walletTransferManager->findForTrade($tradeId);
        if($walletTransferBatch instanceof WalletTransferBatch){
            $result = $walletTransferBatch->serialize();

            if($walletTransferBatch->getTradeId()){
                /** @var Trade $trade */
                $trade = $tradeRepository->find($walletTransferBatch->getTradeId());
                if($trade instanceof Trade){
                    $result['trade'] = $trade->serialize(true);

                    /** @var Wallet $feeWallet */
                    $feeWallet = $feeWalletResolver->resolveWallet($trade, true);
                    if($feeWallet instanceof Wallet){
                        $result['feeWalletOffer'] = $feeWallet->serialize();
                    }

                    /** @var Wallet $feeWallet */
                    $feeWallet = $feeWalletResolver->resolveWallet($trade);
                    if($feeWallet instanceof Wallet){
                        $result['feeWalletBid'] = $feeWallet->serialize();
                    }
                }
            }
        }

        return $this->view(['walletTransfer' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/wallet-transfers/by-order/{orderId}", options={"expose"=true})
     *
     * @param int $orderId
     * @param WalletTransferManager $walletTransferManager
     * @param TradeRepository $tradeRepository
     * @param FeeWalletResolver $feeWalletResolver
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getWalletTransfersByOrder(int $orderId, WalletTransferManager $walletTransferManager, TradeRepository $tradeRepository, FeeWalletResolver $feeWalletResolver) : View
    {
        // TODO dodać ACL

        $serialized = [];

        $walletTransfers = $walletTransferManager->findForOrder($orderId);
        if(count($walletTransfers) > 0){
            /** @var WalletTransferBatch $walletTransferBatch */
            foreach ($walletTransfers as $walletTransferBatch){
                $serializedItem = $walletTransferBatch->serialize();

                if($walletTransferBatch->getTradeId()){
                    /** @var Trade $trade */
                    $trade = $tradeRepository->find($walletTransferBatch->getTradeId());
                    if($trade instanceof Trade){
                        $serializedItem['trade'] = $trade->serialize(true);

                        /** @var Wallet $feeWallet */
                        $feeWallet = $feeWalletResolver->resolveWallet($trade, true);
                        if($feeWallet instanceof Wallet){
                            $serializedItem['feeWalletOffer'] = $feeWallet->serialize();
                        }

                        /** @var Wallet $feeWallet */
                        $feeWallet = $feeWalletResolver->resolveWallet($trade);
                        if($feeWallet instanceof Wallet){
                            $serializedItem['feeWalletBid'] = $feeWallet->serialize();
                        }
                    }
                }

                $serialized[] = $serializedItem;
            }
        }

        return $this->view(['walletTransfers' => $serialized], JsonResponse::HTTP_OK);
    }
}
