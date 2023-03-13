<?php

namespace App\Event\WalletBalance;

use App\Entity\OrderBook\Order;
use App\Entity\Wallet\Wallet;
use Symfony\Component\EventDispatcher\Event;

class WalletBalanceBeforeOrderEvent extends Event
{
    public const NAME = 'wallet.balance_before_order';

    /** @var Wallet */
    protected $wallet;

    /** @var string */
    protected $balance;

    /** @var Order */
    protected $order;

    /**
     * WalletBalanceBeforeOrderEvent constructor.
     * @param Wallet $wallet
     * @param string $balance
     * @param Order $order
     */
    public function __construct(Wallet $wallet, string $balance, Order $order)
    {
        $this->wallet = $wallet;
        $this->balance = $balance;
        $this->order = $order;
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
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }
}