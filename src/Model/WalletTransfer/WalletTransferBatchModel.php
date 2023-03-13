<?php

namespace App\Model\WalletTransfer;

use App\Document\WalletTransferBatch;

class WalletTransferBatchModel
{
    /** @var string */
    public $walletTransferBatchId;

    /** @var int|null */
    public $tradeId;

    /** @var int|null */
    public $orderId;

    /** @var int|null */
    public $depositId;

    /** @var int|null */
    public $withdrawalId;

    /** @var int|null */
    public $internalTransferId;

    /** @var string|null */
    public $checkoutOrderId;

    /** @var array */
    public $walletTransfers = [];

    /** @var array */
    public $walletTransfersProcessed = [];

    /**
     * WalletTransferBatchModel constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['walletTransferBatchId'])) $this->setWalletTransferBatchId($data['walletTransferBatchId']);
        if(isset($data['tradeId'])) $this->setTradeId($data['tradeId']);
        if(isset($data['orderId'])) $this->setOrderId($data['orderId']);
        if(isset($data['depositId'])) $this->setDepositId($data['depositId']);
        if(isset($data['withdrawalId'])) $this->setWithdrawalId($data['withdrawalId']);
        if(isset($data['internalTransferId'])) $this->setInternalTransferId($data['internalTransferId']);
        if(isset($data['checkoutOrderId'])) $this->setCheckoutOrderId($data['checkoutOrderId']);

        if(isset($data['walletTransfers'])) {
            foreach ($data['walletTransfers'] as $walletTransferRow){
                if(isset($walletTransferRow->type) && isset($walletTransferRow->walletId) && isset($walletTransferRow->amount)){
                    $this->push($walletTransferRow->type, $walletTransferRow->walletId, $walletTransferRow->amount);
                }
            }
        }

        if(isset($data['walletTransfersProcessed'])) {
            foreach ($data['walletTransfersProcessed'] as $walletTransferRow){
                if(isset($walletTransferRow->type) && isset($walletTransferRow->walletId) && isset($walletTransferRow->amount)){
                    $this->pushProcessed($walletTransferRow->type, $walletTransferRow->walletId, $walletTransferRow->amount);
                }
            }
        }
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->walletTransferBatchId && $this->walletTransfers){
            return true;
        }

        return false;
    }

    /**
     * @param WalletTransferBatch $walletTransferBatch
     */
    public function importFromDocument(WalletTransferBatch $walletTransferBatch)
    {
        $this->tradeId = $walletTransferBatch->getTradeId();
        $this->orderId = $walletTransferBatch->getOrderId();
        $this->depositId = $walletTransferBatch->getDepositId();
        $this->withdrawalId = $walletTransferBatch->getWithdrawalId();
        $this->internalTransferId = $walletTransferBatch->getInternalTransferId();
        $this->checkoutOrderId = $walletTransferBatch->getCheckoutOrderId();

        $this->walletTransferBatchId = $walletTransferBatch->getId();

        if($walletTransferBatch->getWalletTransfers()){
            foreach($walletTransferBatch->getWalletTransfers() as $array){
                if(isset($array['type']) && isset($array['walletId']) && isset($array['amount'])){
                    $this->push($array['type'], $array['walletId'], $array['amount']);
                }
            }
        }

        if($walletTransferBatch->getWalletTransfersProcessed()){
            foreach($walletTransferBatch->getWalletTransfersProcessed() as $array){
                if(isset($array['type']) && isset($array['walletId']) && isset($array['amount'])){
                    $this->pushProcessed($array['type'], $array['walletId'], $array['amount']);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param int $walletId
     * @param string $amount
     * @return WalletTransferItem
     */
    public function push(string $type, int $walletId, string $amount): WalletTransferItem
    {
        /** @var WalletTransferItem $walletTransferItem */
        $walletTransferItem = new WalletTransferItem($type, $walletId, $amount);
        return $this->addWalletTransfer($walletTransferItem);
    }

    /**
     * @param string $type
     * @param int $walletId
     * @param string $amount
     * @return WalletTransferItem
     */
    public function pushProcessed(string $type, int $walletId, string $amount): WalletTransferItem
    {
        /** @var WalletTransferItem $walletTransferItem */
        $walletTransferItem = new WalletTransferItem($type, $walletId, $amount);
        return $this->addWalletTransferProcessed($walletTransferItem);
    }

    /**
     * @return array
     */
    public function getWalletTransfers(): array
    {
        return $this->walletTransfers;
    }

    /**
     * @param WalletTransferItem $walletTransferItem
     * @return WalletTransferItem
     */
    public function addWalletTransfer(WalletTransferItem $walletTransferItem): WalletTransferItem
    {
        $this->walletTransfers[] = $walletTransferItem;
        return $walletTransferItem;
    }

    /**
     * @return array
     */
    public function getWalletTransfersProcessed(): array
    {
        return $this->walletTransfersProcessed;
    }

    /**
     * @param WalletTransferItem $walletTransferItem
     * @return WalletTransferItem
     */
    public function addWalletTransferProcessed(WalletTransferItem $walletTransferItem): WalletTransferItem
    {
        $this->walletTransfersProcessed[] = $walletTransferItem;
        return $walletTransferItem;
    }

    /**
     * @return string
     */
    public function getWalletTransferBatchId(): string
    {
        return $this->walletTransferBatchId;
    }

    /**
     * @param string $walletTransferBatchId
     */
    public function setWalletTransferBatchId(string $walletTransferBatchId): void
    {
        $this->walletTransferBatchId = $walletTransferBatchId;
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
}
