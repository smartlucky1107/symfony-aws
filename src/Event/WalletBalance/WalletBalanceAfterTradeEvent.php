<?php

namespace App\Event\WalletBalance;

use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Wallet;
use Symfony\Component\EventDispatcher\Event;

class WalletBalanceAfterTradeEvent extends Event
{
    public const NAME = 'wallet.balance_after_trade';

    /** @var Wallet */
    protected $wallet;

    /** @var string */
    protected $balance;

    /** @var Trade */
    protected $trade;

    /**
     * WalletBalanceAfterTradeEvent constructor.
     * @param Wallet $wallet
     * @param string $balance
     * @param Trade $trade
     */
    public function __construct(Wallet $wallet, string $balance, Trade $trade)
    {
        $this->wallet = $wallet;
        $this->balance = $balance;
        $this->trade = $trade;
    }

    /**
     * @return Wallet
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * @return string
     */
    public function getBalance(): string
    {
        return $this->balance;
    }

    /**
     * @return Trade
     */
    public function getTrade(): Trade
    {
        return $this->trade;
    }
}