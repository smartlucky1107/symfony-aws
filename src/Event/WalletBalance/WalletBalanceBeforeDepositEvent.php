<?php

namespace App\Event\WalletBalance;

use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Wallet;
use Symfony\Component\EventDispatcher\Event;

class WalletBalanceBeforeDepositEvent extends Event
{
    public const NAME = 'wallet.balance_before_deposit';

    /** @var Wallet */
    protected $wallet;

    /** @var string */
    protected $balance;

    /** @var Deposit */
    protected $deposit;

    /**
     * WalletBalanceBeforeDepositEvent constructor.
     * @param Wallet $wallet
     * @param string $balance
     * @param Deposit $deposit
     */
    public function __construct(Wallet $wallet, string $balance, Deposit $deposit)
    {
        $this->wallet = $wallet;
        $this->balance = $balance;
        $this->deposit = $deposit;
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
     * @return Deposit
     */
    public function getDeposit(): Deposit
    {
        return $this->deposit;
    }
}