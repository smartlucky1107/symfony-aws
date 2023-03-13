<?php

namespace App\EventListener;

use App\Event\WalletTransferDepositEvent;
use App\Event\WalletTransferEvent;
use App\Event\WalletTransferOrderEvent;
use App\Event\WalletTransferWithdrawalEvent;
use App\Event\WalletTransferInternalTransferEvent;
use App\Manager\RedisSubscribeManager;
use App\Model\WalletTransfer\WalletTransferBatchModel;

class WalletTransferListener
{
    /** @var RedisSubscribeManager */
    private $redisSubscribeManager;

    /**
     * WalletTransferListener constructor.
     * @param RedisSubscribeManager $redisSubscribeManager
     */
    public function __construct(RedisSubscribeManager $redisSubscribeManager)
    {
        $this->redisSubscribeManager = $redisSubscribeManager;
    }

    /**
     * @param WalletTransferEvent $event
     * @throws \Exception
     */
    public function onWalletTransfer(WalletTransferEvent $event)
    {
        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->push($event->getType(), $event->getWalletId(), $event->getAmount());
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        $walletTransferBatchModel = null;
        unset($walletTransferBatchModel);

        $event = null;
        unset($event);
    }

    /**
     * @param WalletTransferDepositEvent $event
     * @throws \Exception
     */
    public function onWalletTransferDeposit(WalletTransferDepositEvent $event)
    {
        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setDepositId($event->getDepositId());
        $walletTransferBatchModel->push($event->getType(), $event->getWalletId(), $event->getAmount());
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        $walletTransferBatchModel = null;
        unset($walletTransferBatchModel);

        $event = null;
        unset($event);
    }

    /**
     * @param WalletTransferOrderEvent $event
     * @throws \Exception
     */
    public function onWalletTransferOrder(WalletTransferOrderEvent $event){
        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setOrderId($event->getOrderId());
        $walletTransferBatchModel->push($event->getType(), $event->getWalletId(), $event->getAmount());
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        $walletTransferBatchModel = null;
        unset($walletTransferBatchModel);

        $event = null;
        unset($event);
    }

    /**
     * @param WalletTransferWithdrawalEvent $event
     * @throws \Exception
     */
    public function onWalletTransferWithdrawal(WalletTransferWithdrawalEvent $event)
    {
        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setWithdrawalId($event->getWithdrawalId());
        $walletTransferBatchModel->push($event->getType(), $event->getWalletId(), $event->getAmount());
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        $walletTransferBatchModel = null;
        unset($walletTransferBatchModel);

        $event = null;
        unset($event);
    }

    /**
     * @param WalletTransferInternalTransferEvent $event
     * @throws \Exception
     */
    public function onWalletTransferInternalTransfer(WalletTransferInternalTransferEvent $event)
    {
        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setInternalTransferId($event->getInternalTransferId());
        $walletTransferBatchModel->push($event->getType(), $event->getWalletId(), $event->getAmount());
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        $walletTransferBatchModel = null;
        unset($walletTransferBatchModel);

        $event = null;
        unset($event);
    }
}
