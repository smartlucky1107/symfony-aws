<?php

namespace App\Controller\ApiCommon;

use App\Entity\OrderBook\Trade;
use App\Manager\WalletManager;
use App\Repository\OrderBook\TradeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exception\AppException;

class TradeController extends AbstractController
{
    /**
     * @param int $tradeId
     * @param TradeRepository $tradeRepository
     * @param WalletManager $walletManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function putTradeRevert(int $tradeId, TradeRepository $tradeRepository, WalletManager $walletManager) : JsonResponse
    {
        /** @var Trade $trade */
        $trade = $tradeRepository->findOrException($tradeId);
        $walletManager->revertTheTrade($trade);

        return new JsonResponse(['reverted' => true], Response::HTTP_OK);
    }
}
