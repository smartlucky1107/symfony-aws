<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

class WalletTransferEvent extends Event
{
    public const NAME = 'wallet.on_wallet_transfer';

    /** @var string */
    protected $type;

    /** @var int */
    protected $walletId;

    /** @var string */
    protected $amount;

    /**
     * WalletTransferEvent constructor.
     * @param string $type
     * @param int $walletId
     * @param string $amount
     */
    public function __construct(string $type, int $walletId, string $amount)
    {
        $this->type = $type;
        $this->walletId = $walletId;
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getWalletId(): int
    {
        return $this->walletId;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }
}