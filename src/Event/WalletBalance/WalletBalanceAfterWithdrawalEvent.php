<?php

namespace App\Event\WalletBalance;

use App\Entity\Wallet\Withdrawal;
use App\Entity\Wallet\Wallet;
use Symfony\Component\EventDispatcher\Event;

class WalletBalanceAfterWithdrawalEvent extends Event
{
    public const NAME = 'wallet.balance_after_withdrawal';

    /** @var Wallet */
    protected $wallet;

    /** @var string */
    protected $balance;

    /** @var Withdrawal */
    protected $withdrawal;

    /**
     * WalletBalanceAfterWithdrawalEvent constructor.
     * @param Wallet $wallet
     * @param string $balance
     * @param Withdrawal $withdrawal
     */
    public function __construct(Wallet $wallet, string $balance, Withdrawal $withdrawal)
    {
        $this->wallet = $wallet;
        $this->balance = $balance;
        $this->withdrawal = $withdrawal;
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
     * @return Withdrawal
     */
    public function getWithdrawal(): Withdrawal
    {
        return $this->withdrawal;
    }
}