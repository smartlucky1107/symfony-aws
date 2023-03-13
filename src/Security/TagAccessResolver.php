<?php

namespace App\Security;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\Withdrawal;
use App\Exception\AppException;

class TagAccessResolver
{
    /**
     * @param CurrencyPair $currencyPair
     * @return bool
     */
    private function isFiat(CurrencyPair $currencyPair) : bool
    {
        if($currencyPair->getBaseCurrency()->isFiatType() || $currencyPair->getQuotedCurrency()->isFiatType()){
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param Order $order
     * @throws AppException
     */
    public function authTrading(User $user, Order $order) : void
    {
        $tags = $user->getTags();
        $isFiat = $this->isFiat($order->getCurrencyPair());

        $throwException = false;

        if($tags){
            foreach($tags as $tag){
                if($isFiat && $tag === User::TAG_FIAT_TRADE_SUSPENDED){
                    $throwException = true;
                }elseif(!$isFiat && User::TAG_CRYPTO_TRADE_SUSPENDED){
                    $throwException = true;
                }
            }
        }

        if($throwException) throw new AppException('Trading is not allowed');
    }

    /**
     * @param User $user
     * @param Deposit $deposit
     * @throws AppException
     */
    public function authDeposit(User $user, Deposit $deposit) : void
    {
        $tags = $user->getTags();
        $isFiat = $deposit->getWallet()->getCurrency()->isFiatType();

        $throwException = false;

        if($tags){
            foreach($tags as $tag){
                if($isFiat && $tag === User::TAG_FIAT_DEPOSIT_SUSPENDED){
                    $throwException = true;
                }elseif(!$isFiat && User::TAG_CRYPTO_DEPOSIT_SUSPENDED){
                    $throwException = true;
                }
            }
        }

        if($throwException) throw new AppException('Trading is not allowed');
    }

    /**
     * @param User $user
     * @param Wallet $wallet
     * @throws AppException
     */
    public function authWithdrawal(User $user, Wallet $wallet) : void
    {
        $tags = $user->getTags();
        $isFiat = $wallet->getCurrency()->isFiatType();

        $throwException = false;

        if($tags){
            foreach($tags as $tag){
                if($isFiat && $tag === User::TAG_FIAT_WITHDRAWAL_SUSPENDED){
                    $throwException = true;
                }elseif(!$isFiat && User::TAG_CRYPTO_WITHDRAWAL_SUSPENDED){
                    $throwException = true;
                }
            }
        }

        if($throwException) throw new AppException('Trading is not allowed');
    }

    /**
     * @param User $user
     * @param Wallet $wallet
     * @throws AppException
     */
    public function authInternalTransfer(User $user, Wallet $wallet) : void
    {
        $tags = $user->getTags();
        $isFiat = $wallet->getCurrency()->isFiatType();

        $throwException = false;

        if($tags){
            foreach($tags as $tag){
                if($isFiat && $tag === User::TAG_FIAT_INTERNAL_TRANSFER_SUSPENDED){
                    $throwException = true;
                }elseif(!$isFiat && User::TAG_CRYPTO_INTERNAL_TRANSFER_SUSPENDED){
                    $throwException = true;
                }
            }
        }

        if($throwException) throw new AppException('Trading is not allowed');
    }
}
