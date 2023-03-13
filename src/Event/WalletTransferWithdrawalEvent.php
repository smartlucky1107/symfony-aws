<?php

namespace App\Event;

class WalletTransferWithdrawalEvent extends WalletTransferEvent
{
    public const NAME = 'wallet.on_wallet_transfer_withdrawal';

    /** @var int */
    protected $withdrawalId;

    /**
     * WalletTransferWithdrawalEvent constructor.
     * @param int $withdrawalId
     * @param string $type
     * @param int $walletId
     * @param string $amount
     */
    public function __construct(int $withdrawalId, string $type, int $walletId, string $amount)
    {
        $this->withdrawalId = $withdrawalId;

        parent::__construct($type, $walletId, $amount);
    }

    /**
     * @return int
     */
    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }
}