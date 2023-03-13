<?php

namespace App\Event;

class WalletTransferInternalTransferEvent extends WalletTransferEvent
{
    public const NAME = 'wallet.on_wallet_transfer_internal_transfer';

    /** @var int */
    protected $internalTransferId;

    /**
     * WalletTransferInternalTransferEvent constructor.
     * @param int $internalTransferId
     * @param string $type
     * @param int $walletId
     * @param string $amount
     */
    public function __construct(int $internalTransferId, string $type, int $walletId, string $amount)
    {
        $this->internalTransferId = $internalTransferId;

        parent::__construct($type, $walletId, $amount);
    }

    /**
     * @return int
     */
    public function getInternalTransferId(): int
    {
        return $this->internalTransferId;
    }
}
