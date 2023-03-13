<?php

namespace App\Resolver;

use App\Entity\Currency;
use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Repository\WalletRepository;

class FeeWalletResolver
{
    const FEE_WALLET_IDS = [
        'BTC' => 1,
        'PLN' => 2,
        'USD' => 3
    ];

    /** @var WalletRepository */
    private $walletRepository;

    /**
     * FeeWalletResolver constructor.
     * @param WalletRepository $walletRepository
     */
    public function __construct(WalletRepository $walletRepository)
    {
        $this->walletRepository = $walletRepository;
    }

    /**
     * @param Currency $currency
     * @return Wallet
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function resolveByCurrency(Currency $currency) : Wallet
    {
        /** @var Wallet $wallet */
        $wallet = $this->walletRepository->getFeeWallet($currency);
        if(!($wallet instanceof Wallet)) throw new AppException('Fee wallet not found');

        return $wallet;
    }

    /**
     * Resolve fee wallet bt the passed $trade
     *
     * @param Trade $trade
     * @param bool $quoted
     * @return Wallet
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function resolveWallet(Trade $trade, $quoted = false) : Wallet
    {
        //$baseCurrencyShortName = $trade->getOrderSell()->getCurrencyPair()->getBaseCurrency()->getShortName();
        //$walletId = self::FEE_WALLET_IDS[$baseCurrencyShortName];
        //$wallet = $this->walletRepository->find($walletId);

        if($quoted){
            /** @var Wallet $wallet */
            $wallet = $this->walletRepository->getFeeWallet($trade->getOrderSell()->getCurrencyPair()->getQuotedCurrency());
        }else{
            /** @var Wallet $wallet */
            $wallet = $this->walletRepository->getFeeWallet($trade->getOrderSell()->getCurrencyPair()->getBaseCurrency());
        }

        if(!($wallet instanceof Wallet)) throw new AppException('Fee wallet not found');

        return $wallet;
    }
}
