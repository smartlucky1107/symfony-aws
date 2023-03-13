<?php

namespace App\Manager;

use App\Document\WalletTransferBatch;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\OrderBook\TradeRepository;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class WalletTransferManager
{
    /** @var DocumentManager */
    private $dm;

    /** @var TradeRepository */
    private $tradeRepository;

    /**
     * WalletTransferManager constructor.
     * @param DocumentManager $dm
     * @param TradeRepository $tradeRepository
     */
    public function __construct(DocumentManager $dm, TradeRepository $tradeRepository)
    {
        $this->dm = $dm;
        $this->tradeRepository = $tradeRepository;
    }

    /**
     * @return array
     */
    public function findNotProcessed() : array
    {
        $walletTransfers = $this->dm->getRepository(WalletTransferBatch::class)->findBy([
            'processed' => false
        ]);

        return $walletTransfers;
    }

    /**
     * @param int $tradeId
     * @return WalletTransferBatch|null
     */
    public function findForTrade(int $tradeId) : ?WalletTransferBatch
    {
        /** @var WalletTransferBatch $walletTransferBatch */
        $walletTransferBatch = $this->dm->getRepository(WalletTransferBatch::class)->findOneBy([
            'tradeId' => $tradeId
        ]);

        return $walletTransferBatch;
    }

    /**
     * @param int $orderId
     * @return array
     */
    public function findForOrder(int $orderId) : array
    {
        $walletTransferBatches = [];

        $trades = $this->tradeRepository->findForOrderId($orderId);
        if($trades){
            /** @var Trade $trade */
            foreach($trades as $trade){
                /** @var WalletTransferBatch $walletTransferBatch */
                $walletTransferBatch = $this->findForTrade($trade->getId());
                if($walletTransferBatch instanceof WalletTransferBatch){
                    $walletTransferBatches[] = $walletTransferBatch;
                }
            }
        }

        return $walletTransferBatches;
    }
}