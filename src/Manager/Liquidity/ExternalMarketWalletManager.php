<?php

namespace App\Manager\Liquidity;

use App\Entity\Currency;
use App\Entity\Liquidity\ExternalMarketInterface;
use App\Entity\Liquidity\ExternalMarketWallet;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Repository\Liquidity\ExternalMarketWalletRepository;

class ExternalMarketWalletManager
{
    /** @var ExternalMarketWalletRepository */
    private $externalMarketWalletRepository;

    /**
     * ExternalMarketWalletManager constructor.
     * @param ExternalMarketWalletRepository $externalMarketWalletRepository
     */
    public function __construct(ExternalMarketWalletRepository $externalMarketWalletRepository)
    {
        $this->externalMarketWalletRepository = $externalMarketWalletRepository;
    }

    /**
     * @param Currency $currency
     * @param int $externalMarketId
     * @return ExternalMarketWallet
     * @throws AppException
     */
    public function loadOrException(Currency $currency, int $externalMarketId) : ExternalMarketWallet
    {
        ExternalMarketWallet::verifyExternalMarketId($externalMarketId);

        /** @var ExternalMarketWallet $externalMarketWallet */
        $externalMarketWallet = $this->externalMarketWalletRepository->findOneBy([
            'currency' => $currency->getId(),
            'externalMarket' => $externalMarketId,
        ]);
        if(!($externalMarketWallet instanceof ExternalMarketWallet)) throw new AppException('Market wallet not found');

        return $externalMarketWallet;
    }

    /**
     * @param Currency $currency
     * @param int $externalMarketId
     * @param float $balance
     * @return ExternalMarketWallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function updateBalance(Currency $currency, int $externalMarketId, float $balance) : ExternalMarketWallet
    {
        ExternalMarketWallet::verifyExternalMarketId($externalMarketId);

        /** @var ExternalMarketWallet $externalMarketWallet */
        $externalMarketWallet = $this->externalMarketWalletRepository->findOneBy([
            'currency' => $currency->getId(),
            'externalMarket' => $externalMarketId
        ]);
        if($externalMarketWallet instanceof ExternalMarketWallet){
            $externalMarketWallet->setBalance($balance);
        }else{
            /** @var ExternalMarketWallet $externalMarketWallet */
            $externalMarketWallet = new ExternalMarketWallet($currency, $externalMarketId, $balance);
        }

        return $this->externalMarketWalletRepository->save($externalMarketWallet);
    }
}