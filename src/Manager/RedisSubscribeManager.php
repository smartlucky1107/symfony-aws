<?php

namespace App\Manager;

use App\Document\InternalTransferRequest;
use App\Document\TradingTransaction;
use App\Document\WalletTransferBatch;
use App\Document\WithdrawalApproveRequest;
use App\Document\WithdrawalRequest;
use App\Manager\Queue\QueueItemManager;
use App\Model\InternalTransferRequestModel;
use App\Model\TradingTransactionModel;
use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WithdrawalApproveRequestModel;
use App\Model\WithdrawalRequestModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RedisSubscribeManager implements RedisSubscribeInterface
{
    /** @var RedisProvider */
    private $redisProvider;

    /** @var QueueItemManager */
    private $queueItemManager;

    /** @var ParameterBagInterface */
    private $parameters;

    private $redisClient;

    /**
     * RedisSubscribeManager constructor.
     * @param RedisProvider $redisProvider
     * @param QueueItemManager $queueItemManager
     * @param ParameterBagInterface $parameters
     */
    public function __construct(RedisProvider $redisProvider, QueueItemManager $queueItemManager, ParameterBagInterface $parameters)
    {
        $this->redisProvider = $redisProvider;
        $this->queueItemManager = $queueItemManager;
        $this->parameters = $parameters;

        $this->redisClient = new \Redis();
        $this->redisClient->connect($this->parameters->get('redis_host'), $this->parameters->get('redis_port'));
    }

    /**
     * Add notification for Redis and Websocket processing
     *
     * @param array $notificationItem
     */
    public function pushNotification(array $notificationItem){
        $this->redisClient->lPush(self::NOTIFICATION_LIST, json_encode($notificationItem));
        $this->redisClient->publish(self::NOTIFICATIONS_SUBSCRIBE_CHANEL, json_encode([]));

//        $this->redisProvider->getRedis()->lPush(self::NOTIFICATION_LIST, json_encode($notificationItem));
//        $this->redisProvider->getRedis()->publish(self::NOTIFICATIONS_SUBSCRIBE_CHANEL, json_encode([]));
    }

    /**
     * @param int $withdrawalId
     * @throws \Exception
     */
    public function pushWithdrawalRequest(int $withdrawalId){
        // save backup to MONGO
        /** @var WithdrawalRequest $withdrawalRequest */
        $withdrawalRequest = new WithdrawalRequest($withdrawalId);
        $withdrawalRequest = $this->queueItemManager->save($withdrawalRequest);

        // process in redis
        $withdrawalRequestModel = new WithdrawalRequestModel();
        $withdrawalRequestModel->setWithdrawalRequestId($withdrawalRequest->getId());
        $withdrawalRequestModel->setWithdrawalId($withdrawalId);

        $this->redisClient->lPush(self::WITHDRAWAL_REQUEST_LIST, json_encode($withdrawalRequestModel));

        $withdrawalRequestModel = null;
        unset($withdrawalRequestModel);

        $withdrawalRequest = null;
        unset($withdrawalRequest);
    }

    /**
     * @param int $withdrawalId
     * @throws \Exception
     */
    public function pushWithdrawalApproveRequest(int $withdrawalId){
        // save backup to MONGO
        /** @var WithdrawalApproveRequest $withdrawalApproveRequest */
        $withdrawalApproveRequest = new WithdrawalApproveRequest($withdrawalId);
        $withdrawalApproveRequest = $this->queueItemManager->save($withdrawalApproveRequest);

        // process in redis
        $withdrawalApproveRequestModel = new WithdrawalApproveRequestModel();
        $withdrawalApproveRequestModel->setWithdrawalApproveRequestId($withdrawalApproveRequest->getId());
        $withdrawalApproveRequestModel->setWithdrawalId($withdrawalId);

        $this->redisClient->lPush(self::WITHDRAWAL_APPROVE_REQUEST_LIST, json_encode($withdrawalApproveRequestModel));

        $withdrawalApproveRequestModel = null;
        unset($withdrawalApproveRequestModel);

        $withdrawalApproveRequest = null;
        unset($withdrawalApproveRequest);
    }

    /**
     * @param int $internalTransferId
     * @throws \Exception
     */
    public function pushInternalTransferRequest(int $internalTransferId){
        // save backup to MONGO
        /** @var InternalTransferRequest $internalTransferRequest */
        $internalTransferRequest = new InternalTransferRequest($internalTransferId);
        $internalTransferRequest = $this->queueItemManager->save($internalTransferRequest);

        // process in redis
        $internalTransferRequestModel = new InternalTransferRequestModel();
        $internalTransferRequestModel->setInternalTransferRequestId($internalTransferRequest->getId());
        $internalTransferRequestModel->setInternalTransferId($internalTransferId);

        $this->redisClient->lPush(self::INTERNAL_TRANSFER_REQUEST_LIST, json_encode($internalTransferRequestModel));

        $internalTransferRequestModel = null;
        unset($internalTransferRequestModel);

        $internalTransferRequest = null;
        unset($internalTransferRequest);
    }

    /**
     * Add order for Redis and Websocket processing
     *
     * @param int $orderId
     * @param bool $isInstantExecution
     * @throws \Exception
     */
    public function pushOrder(int $orderId, bool $isInstantExecution = false){
        // save backup to MONGO
        /** @var TradingTransaction $tradingTransaction */
        $tradingTransaction = new TradingTransaction($orderId);
        $tradingTransaction = $this->queueItemManager->save($tradingTransaction);

        // process in redis
        $tradingTransactionModel = new TradingTransactionModel();
        $tradingTransactionModel->setTradingTransactionId($tradingTransaction->getId());
        $tradingTransactionModel->setOrderId($orderId);

        if($isInstantExecution){
            $this->redisClient->rPush(self::TRADING_LIST, json_encode($tradingTransactionModel));

            //$this->redisProvider->getRedis()->rPush(self::TRADING_LIST, json_encode($tradingTransactionModel));
        }else{
            $this->redisClient->lPush(self::TRADING_LIST, json_encode($tradingTransactionModel));

            //$this->redisProvider->getRedis()->lPush(self::TRADING_LIST, json_encode($tradingTransactionModel));
        }

        $tradingTransactionModel = null;
        unset($tradingTransactionModel);

        $tradingTransaction = null;
        unset($tradingTransaction);


        //$this->redisClient->publish(self::TRADING_SUBSCRIBE_CHANEL, json_encode([]));

        //$this->redisProvider->getRedis()->publish(self::TRADING_SUBSCRIBE_CHANEL, json_encode([]));
    }

    /**
     * @param WalletTransferBatchModel $walletTransferBatchModel
     * @throws \Exception
     */
    public function pushWalletTransferBatch(WalletTransferBatchModel $walletTransferBatchModel){
        // save backup to MONGO
        /** @var WalletTransferBatch $walletTransferBatch */
        $walletTransferBatch = new WalletTransferBatch($walletTransferBatchModel);
        $walletTransferBatch = $this->queueItemManager->save($walletTransferBatch);

        // process in redis
        $walletTransferBatchModel->setWalletTransferBatchId($walletTransferBatch->getId());

        $this->redisClient->lPush(self::WALLET_TRANSFER_BATCH_LIST, json_encode($walletTransferBatchModel));

        $walletTransferBatchModel = null;
        unset($walletTransferBatchModel);

        $walletTransferBatch = null;
        unset($walletTransferBatch);
    }
}
