<?php

namespace App\Manager;

use App\Entity\Currency;
use App\Exception\AppException;
use App\Repository\CurrencyRepository;

class CurrencyManager
{
    /** @var CurrencyRepository */
    private $currencyRepository;

    /** @var WalletGenerator */
    private $walletGenerator;

    /** @var Currency */
    private $currency;

    /**
     * CurrencyManager constructor.
     * @param CurrencyRepository $currencyRepository
     * @param WalletGenerator $walletGenerator
     */
    public function __construct(CurrencyRepository $currencyRepository, WalletGenerator $walletGenerator)
    {
        $this->currencyRepository = $currencyRepository;
        $this->walletGenerator = $walletGenerator;
    }

    /**
     * Load Currency to the class by $currencyId
     *
     * @param int $currencyId
     * @return Currency
     * @throws AppException
     */
    public function load(int $currencyId) : Currency
    {
        $this->currency = $this->currencyRepository->find($currencyId);
        if(!($this->currency instanceof Currency)) throw new AppException('error.currency.not_found');

        return $this->currency;
    }

    /**
     * @return Currency
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disable() : Currency
    {
        if(!($this->currency instanceof Currency)) throw new AppException('error.currency.not_loaded');

        $this->currency->setEnabled(false);

        return $this->update($this->currency);
    }

    /**
     * @return Currency
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enable() : Currency
    {
        if(!($this->currency instanceof Currency)) throw new AppException('error.currency.not_loaded');

        $this->currency->setEnabled(true);

        return $this->update($this->currency);
    }

    /**
     * @param Currency $currency
     * @return Currency
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(Currency $currency) : Currency
    {
        $this->currency = $this->update($currency);
        $this->walletGenerator->generateForCurrency($this->currency);

        return $this->currency;
    }

    /**
     * @param Currency $currency
     * @return Currency
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(Currency $currency) : Currency
    {
        $this->currency = $this->currencyRepository->save($currency);

        return $this->currency;
    }

    /**
     * @return Currency
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateFee(string $fee) : Currency
    {
        if(!($this->currency instanceof Currency)) throw new AppException('error.currency.not_loaded');

        if(!is_numeric($fee)) throw new AppException('error.currency.invalid_amount');

        $this->currency->setFee($fee);

        return $this->update($this->currency);
    }
}
