<?php

namespace App\Document;

use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WalletTransfer\WalletTransferItem;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class WalletTransferBatch
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $createdAtTime;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="bool")
     */
    protected $processed;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="bool")
     */
    protected $success;

####
# Custom item fields

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $tradeId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $orderId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $depositId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $withdrawalId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $internalTransferId;

    /**
     * @var string|null
     *
     * @MongoDB\Field(type="string")
     */
    protected $checkoutOrderId;

    /**
     * @var array|null
     *
     * @MongoDB\Field(type="collection")
     */
    protected $walletTransfers;

    /**
     * @var array|null
     *
     * @MongoDB\Field(type="collection")
     */
    protected $walletTransfersProcessed;

    /**
     * WalletTransferBatch constructor.
     * @param WalletTransferBatchModel|null $model
     * @throws \Exception
     */
    public function __construct(WalletTransferBatchModel $model = null)
    {
        $this->tradeId = $model->getTradeId();
        $this->orderId = $model->getOrderId();
        $this->depositId = $model->getDepositId();
        $this->withdrawalId = $model->getWithdrawalId();
        $this->internalTransferId = $model->getInternalTransferId();
        $this->checkoutOrderId = $model->getCheckoutOrderId();

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
        $this->setProcessed(false);
        $this->setSuccess(false);

        if($model instanceof WalletTransferBatchModel){
            if($model->getWalletTransfers()){
                /** @var WalletTransferItem $walletTransferItem */
                foreach ($model->getWalletTransfers() as $walletTransferItem){
                    $this->addWalletTransfer($walletTransferItem);
                }
            }

            if($model->getWalletTransfersProcessed()){
                /** @var WalletTransferItem $walletTransferItem */
                foreach ($model->getWalletTransfersProcessed() as $walletTransferItem){
                    $this->addWalletTransfersProcessed($walletTransferItem);
                }
            }
        }
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'id'            => $this->id,
            'createdAt'     => date('Y-m-d H:i:s', $this->createdAtTime),
            'processed'     => $this->processed,
            'success'       => $this->success,
            'tradeId'       => $this->tradeId,
            'orderId'       => $this->orderId,
            'depositId'     => $this->depositId,
            'withdrawalId'  => $this->withdrawalId,
            'internalTransferId'    => $this->internalTransferId,
            'checkoutOrderId'       => $this->checkoutOrderId,
            'walletTransfers'           => $this->walletTransfers,
            'walletTransfersProcessed'  => $this->walletTransfersProcessed,
        ];
    }

    /**
     * @param WalletTransferBatchModel $model
     */
    public function importProcessed(WalletTransferBatchModel $model){
        if($model->getWalletTransfersProcessed()){
            /** @var WalletTransferItem $walletTransferItem */
            foreach ($model->getWalletTransfersProcessed() as $walletTransferItem){
                $this->addWalletTransfersProcessed($walletTransferItem);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCreatedAtTime()
    {
        return $this->createdAtTime;
    }

    /**
     * @param mixed $createdAtTime
     */
    public function setCreatedAtTime($createdAtTime): void
    {
        $this->createdAtTime = $createdAtTime;
    }

    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * @param bool $processed
     */
    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return int|null
     */
    public function getTradeId(): ?int
    {
        return $this->tradeId;
    }

    /**
     * @param int|null $tradeId
     */
    public function setTradeId(?int $tradeId): void
    {
        $this->tradeId = $tradeId;
    }

    /**
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    /**
     * @param int|null $orderId
     */
    public function setOrderId(?int $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int|null
     */
    public function getDepositId(): ?int
    {
        return $this->depositId;
    }

    /**
     * @param int|null $depositId
     */
    public function setDepositId(?int $depositId): void
    {
        $this->depositId = $depositId;
    }

    /**
     * @return int|null
     */
    public function getWithdrawalId(): ?int
    {
        return $this->withdrawalId;
    }

    /**
     * @param int|null $withdrawalId
     */
    public function setWithdrawalId(?int $withdrawalId): void
    {
        $this->withdrawalId = $withdrawalId;
    }

    /**
     * @return int|null
     */
    public function getInternalTransferId(): ?int
    {
        return $this->internalTransferId;
    }

    /**
     * @param int|null $internalTransferId
     */
    public function setInternalTransferId(?int $internalTransferId): void
    {
        $this->internalTransferId = $internalTransferId;
    }

    /**
     * @return string|null
     */
    public function getCheckoutOrderId(): ?string
    {
        return $this->checkoutOrderId;
    }

    /**
     * @param string|null $checkoutOrderId
     */
    public function setCheckoutOrderId(?string $checkoutOrderId): void
    {
        $this->checkoutOrderId = $checkoutOrderId;
    }

    /**
     * @return array|null
     */
    public function getWalletTransfers(): ?array
    {
        return $this->walletTransfers;
    }

    /**
     * @return array|null
     */
    public function getWalletTransfersProcessed(): ?array
    {
        return $this->walletTransfersProcessed;
    }

    /**
     * @param WalletTransferItem $walletTransferItem
     */
    public function addWalletTransfer(WalletTransferItem $walletTransferItem): void
    {
        $this->walletTransfers[] = $walletTransferItem;
    }

    /**
     * @param WalletTransferItem $walletTransferItem
     */
    public function addWalletTransfersProcessed(WalletTransferItem $walletTransferItem): void
    {
        $this->walletTransfersProcessed[] = $walletTransferItem;
    }
}
