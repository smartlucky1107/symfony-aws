<?php

namespace App\Event;

class WalletTransferDepositEvent extends WalletTransferEvent
{
    public const NAME = 'wallet.on_wallet_transfer_deposit';

    /** @var int */
    protected $depositId;

    /**
     * WalletTransferDepositEvent constructor.
     * @param int $depositId
     * @param string $type
     * @param int $walletId
     * @param string $amount
     */
    public function __construct(int $depositId, string $type, int $walletId, string $amount)
    {
        $this->depositId = $depositId;

        parent::__construct($type, $walletId, $amount);
    }

    /**
     * @return int
     */
    public function getDepositId(): int
    {
        return $this->depositId;
    }
}