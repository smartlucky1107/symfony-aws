<?php

namespace App\EventListener;

use App\Document\WalletBalance;
use App\Event\WalletBalance\WalletBalanceAfterDepositEvent;
use App\Event\WalletBalance\WalletBalanceAfterOrderEvent;
use App\Event\WalletBalance\WalletBalanceAfterTradeEvent;
use App\Event\WalletBalance\WalletBalanceAfterWithdrawalEvent;
use App\Event\WalletBalance\WalletBalanceBeforeDepositEvent;
use App\Event\WalletBalance\WalletBalanceBeforeOrderEvent;
use App\Event\WalletBalance\WalletBalanceBeforeTradeEvent;
use App\Event\WalletBalance\WalletBalanceBeforeWithdrawalEvent;
use App\Manager\WalletBalanceManager;

class WalletSaveBalanceListener
{
    /** @var WalletBalanceManager */
    private $walletBalanceManager;

    /**
     * WalletSaveBalanceListener constructor.
     * @param WalletBalanceManager $walletBalanceManager
     */
    public function __construct(WalletBalanceManager $walletBalanceManager)
    {
        $this->walletBalanceManager = $walletBalanceManager;
    }

    /**
     * @param WalletBalanceBeforeTradeEvent $event
     * @throws \Exception
     */
    public function onBalanceBeforeTrade(WalletBalanceBeforeTradeEvent $event)
    {
        $walletBalance = new WalletBalance($event->getWallet()->getId(), $event->getBalance());
        $walletBalance->setTradeId($event->getTrade()->getId());

        $this->walletBalanceManager->saveBalance($walletBalance);
    }

    /**
     * @param WalletBalanceAfterTradeEvent $event
     */
    public function onBalanceAfterTrade(WalletBalanceAfterTradeEvent $event)
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->walletBalanceManager->findForTrade($event->getTrade()->getId(), $event->getWallet()->getId());
        if($walletBalance instanceof WalletBalance){
            $walletBalance->setBalanceAfter($event->getBalance());

            $this->walletBalanceManager->saveBalance($walletBalance);
        }
    }

    /**
     * @param WalletBalanceBeforeOrderEvent $event
     * @throws \Exception
     */
    public function onBalanceBeforeOrder(WalletBalanceBeforeOrderEvent $event)
    {
        $walletBalance = new WalletBalance($event->getWallet()->getId(), $event->getBalance());
        $walletBalance->setOrderId($event->getOrder()->getId());

        $this->walletBalanceManager->saveBalance($walletBalance);
    }

    /**
     * @param WalletBalanceAfterOrderEvent $event
     */
    public function onBalanceAfterOrder(WalletBalanceAfterOrderEvent $event)
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->walletBalanceManager->findForOrder($event->getOrder()->getId(), $event->getWallet()->getId());
        if($walletBalance instanceof WalletBalance){
            $walletBalance->setBalanceAfter($event->getBalance());

            $this->walletBalanceManager->saveBalance($walletBalance);
        }
    }

    /**
     * @param WalletBalanceBeforeDepositEvent $event
     * @throws \Exception
     */
    public function onBalanceBeforeDeposit(WalletBalanceBeforeDepositEvent $event)
    {
        $walletBalance = new WalletBalance($event->getWallet()->getId(), $event->getBalance());
        $walletBalance->setDepositId($event->getDeposit()->getId());

        $this->walletBalanceManager->saveBalance($walletBalance);
    }

    /**
     * @param WalletBalanceAfterDepositEvent $event
     */
    public function onBalanceAfterDeposit(WalletBalanceAfterDepositEvent $event)
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->walletBalanceManager->findForDeposit($event->getDeposit()->getId(), $event->getWallet()->getId());
        if($walletBalance instanceof WalletBalance){
            if(!$walletBalance->getBalanceAfter()){
                $walletBalance->setBalanceAfter($event->getBalance());

                $this->walletBalanceManager->saveBalance($walletBalance);
            }
        }
    }

    /**
     * @param WalletBalanceBeforeWithdrawalEvent $event
     * @throws \Exception
     */
    public function onBalanceBeforeWithdrawal(WalletBalanceBeforeWithdrawalEvent $event)
    {
        $walletBalance = new WalletBalance($event->getWallet()->getId(), $event->getBalance());
        $walletBalance->setWithdrawalId($event->getWithdrawal()->getId());

        $this->walletBalanceManager->saveBalance($walletBalance);
    }

    /**
     * @param WalletBalanceAfterWithdrawalEvent $event
     */
    public function onBalanceAfterWithdrawal(WalletBalanceAfterWithdrawalEvent $event)
    {
        /** @var WalletBalance $walletBalance */
        $walletBalance = $this->walletBalanceManager->findForWithdrawal($event->getWithdrawal()->getId(), $event->getWallet()->getId());
        if($walletBalance instanceof WalletBalance){
            $walletBalance->setBalanceAfter($event->getBalance());

            $this->walletBalanceManager->saveBalance($walletBalance);
        }
    }
}
