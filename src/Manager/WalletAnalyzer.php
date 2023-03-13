<?php

namespace App\Manager;

use App\Document\Transfer;
use App\Document\WalletBalance;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Model\Analysis\WalletAnalysis;
use App\Model\PriceInterface;

class WalletAnalyzer
{
    /** @var TransferManager */
    private $transferManager;

    /** @var WalletBalanceManager */
    private $walletBalanceManager;

    /**
     * WalletAnalyzer constructor.
     * @param TransferManager $transferManager
     * @param WalletBalanceManager $walletBalanceManager
     */
    public function __construct(TransferManager $transferManager, WalletBalanceManager $walletBalanceManager)
    {
        $this->transferManager = $transferManager;
        $this->walletBalanceManager = $walletBalanceManager;
    }

    /**
     * @param Wallet $wallet
     * @return WalletAnalysis
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function analyzeTransfers(Wallet $wallet) : WalletAnalysis
    {
        /** @var WalletAnalysis $walletAnalysis */
        $walletAnalysis = new WalletAnalysis($wallet->getAmount(), $wallet->getAmountPending());

        $balance = 0;
        $balanceBlocked = 0;

        $transfers = $this->transferManager->loadByWalletId($wallet->getId());
        if(count($transfers) > 0){
            /** @var Transfer $transfer */
            foreach($transfers as $transfer){
                switch ($transfer->getType()) {
                    case Transfer::TYPE_DEPOSIT:
                        $balance = bcadd($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_WITHDRAWAL:
                        $balance = bcsub($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_TRANSFER_TO:
                        $balance = bcadd($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_TRANSFER_FROM:
                        $balance = bcsub($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_BLOCK:
                        $balanceBlocked = bcadd($balanceBlocked, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_RELEASE:
                        $balanceBlocked = bcsub($balanceBlocked, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_FEE_TRANSFER_TO:
                        $balance = bcadd($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_FEE_TRANSFER_FROM:
                        $balance = bcsub($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_INTERNAL_TRANSFER_TO:
                        $balance = bcadd($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    case Transfer::TYPE_INTERNAL_TRANSFER_FROM:
                        $balance = bcsub($balance, $transfer->getAmount(), PriceInterface::BC_SCALE);
                        break;
                    default:
                        throw new AppException('type not allowed: ' . $transfer->getType());
                }
            }
        }

        $walletAnalysis->setOutputBalance($balance);
        $walletAnalysis->setOutputBalanceBlocked($balanceBlocked);

        return $walletAnalysis->analyze();
    }

    /**
     * @param Wallet $wallet
     * @return bool
     */
    public function analyzeBalances(Wallet $wallet) : bool
    {
//        $walletBalances = $this->walletBalanceManager->findForWallet($wallet->getId());
//        if(count($walletBalances) > 0){
//            /** @var WalletBalance $walletBalance */
//            foreach ($walletBalances as $walletBalance){
//                dump($walletBalance);
//            }
//        }
//        exit;
//
//        return true;
    }
}
