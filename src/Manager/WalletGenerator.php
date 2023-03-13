<?php

namespace App\Manager;

use App\Entity\Currency;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Repository\CurrencyRepository;
use App\Repository\UserRepository;

class WalletGenerator
{
    /** @var CurrencyRepository */
    private $currencyRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var WalletManager */
    private $walletManager;

    /** @var AddressManager */
    private $addressManager;

    /**
     * @param CurrencyRepository $currencyRepository
     * @param UserRepository $userRepository
     * @param WalletManager $walletManager
     * @param AddressManager $addressManager
     */
    public function __construct(CurrencyRepository $currencyRepository, UserRepository $userRepository, WalletManager $walletManager, AddressManager $addressManager)
    {
        $this->currencyRepository = $currencyRepository;
        $this->userRepository = $userRepository;
        $this->walletManager = $walletManager;
        $this->addressManager = $addressManager;
    }

    /**
     * Generate wallets for User and for all Currencies
     *
     * @param User $user
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateForUser(User $user) : void
    {
        $currencies = $this->currencyRepository->findAll();
        if($currencies){
            /** @var Currency $currency */
            foreach($currencies as $currency){
                if(!$this->walletManager->getWalletRepository()->walletExists($user, $currency)){
                    /** @var Wallet $wallet */
                    $wallet = $this->walletManager->generateWallet($user, $currency);
                    if($wallet->isBtcWallet() || $wallet->isEthWallet()){
                        $this->addressManager->generate($wallet);
                    }
                }
            }
        }

        $currencies = null;
        unset($currencies);
    }

    /**
     * Generate wallets for Currency and for all Users
     *
     * @param Currency $currency
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateForCurrency(Currency $currency) : void
    {
        $users = $this->userRepository->findAll();
        if($users){
            /** @var User $user */
            foreach($users as $user){
                if(!$this->walletManager->getWalletRepository()->walletExists($user, $currency)){
                    /** @var Wallet $wallet */
                    $wallet = $this->walletManager->generateWallet($user, $currency);
                }
            }
        }
    }
}
