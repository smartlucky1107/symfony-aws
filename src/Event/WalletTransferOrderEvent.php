<?php

namespace App\Event;

class WalletTransferOrderEvent extends WalletTransferEvent
{
    public const NAME = 'wallet.on_wallet_transfer_order';

    /** @var int */
    protected $orderId;

    /**
     * WalletTransferOrderEvent constructor.
     * @param int $orderId
     * @param string $type
     * @param int $walletId
     * @param string $amount
     */
    public function __construct(int $orderId, string $type, int $walletId, string $amount)
    {
        $this->orderId = $orderId;

        parent::__construct($type, $walletId, $amount);
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }
}