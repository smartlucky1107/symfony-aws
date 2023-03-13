<?php

namespace App\Manager;

use App\Document\WalletBalance;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class WalletBalanceManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * WalletBalanceManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param int $walletId
     * @param int $limit
     * @return array|null
     */
    public function findForWallet(int $walletId, $limit = 100) : ?array
    {
        $walletBalances = $this->dm->getRepository(WalletBalance::class)->findBy([
            'walletId' => $walletId
        ], ['id' => 'desc'], $limit);

        return $walletBalances;
    }

    /**
     * @param int $tradeId
     * @param int $walletId
     * @return WalletBalance|null
     */
    public function findForTrade(int $tradeId, int $walletId) : ?WalletBalance
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->dm->getRepository(WalletBalance::class)->findOneBy([
            'tradeId' => $tradeId,
            'walletId' => $walletId
        ]);
        if(!($walletBalance instanceof WalletBalance)) return null;

        return $walletBalance;
    }

    /**
     * @param int $orderId
     * @param int $walletId
     * @return WalletBalance|null
     */
    public function findForOrder(int $orderId, int $walletId) : ?WalletBalance
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->dm->getRepository(WalletBalance::class)->findOneBy([
            'orderId' => $orderId,
            'walletId' => $walletId
        ]);
        if(!($walletBalance instanceof WalletBalance)) return null;

        return $walletBalance;
    }

    /**
     * @param int $depositId
     * @param int $walletId
     * @return WalletBalance|null
     */
    public function findForDeposit(int $depositId, int $walletId) : ?WalletBalance
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->dm->getRepository(WalletBalance::class)->findOneBy([
            'depositId' => $depositId,
            'walletId' => $walletId
        ]);
        if(!($walletBalance instanceof WalletBalance)) return null;

        return $walletBalance;
    }

    /**
     * @param int $withdrawalId
     * @param int $walletId
     * @return WalletBalance|null
     */
    public function findForWithdrawal(int $withdrawalId, int $walletId) : ?WalletBalance
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->dm->getRepository(WalletBalance::class)->findOneBy([
            'withdrawalId' => $withdrawalId,
            'walletId' => $walletId
        ]);
        if(!($walletBalance instanceof WalletBalance)) return null;

        return $walletBalance;
    }

    /**
     * @param WalletBalance $walletBalance
     * @return WalletBalance
     */
    public function saveBalance(WalletBalance $walletBalance) : WalletBalance
    {
        $this->dm->persist($walletBalance);
        $this->dm->flush();

        return $walletBalance;
    }
}
