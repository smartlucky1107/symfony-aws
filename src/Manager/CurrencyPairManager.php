<?php

namespace App\Manager;

use App\Entity\CurrencyPair;
use App\Exception\AppException;
use App\Repository\CurrencyPairRepository;

class CurrencyPairManager
{
    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var CurrencyPair */
    private $currencyPair;

    /**
     * CurrencyPairManager constructor.
     * @param CurrencyPairRepository $currencyPairRepository
     */
    public function __construct(CurrencyPairRepository $currencyPairRepository)
    {
        $this->currencyPairRepository = $currencyPairRepository;
    }

    /**
     * Load CurrencyPair to the class by $currencyPairId
     *
     * @param int $currencyPairId
     * @return CurrencyPair
     * @throws AppException
     */
    public function load(int $currencyPairId) : CurrencyPair
    {
        $this->currencyPair = $this->currencyPairRepository->find($currencyPairId);
        if(!($this->currencyPair instanceof CurrencyPair)) throw new AppException('error.currency_pair.not_found');

        return $this->currencyPair;
    }

    /**
     * @return CurrencyPair
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disable() : CurrencyPair
    {
        if(!($this->currencyPair instanceof CurrencyPair)) throw new AppException('error.currency_pair.not_loaded');

        $this->currencyPair->setEnabled(false);

        return $this->update($this->currencyPair);
    }

    /**
     * @return CurrencyPair
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enable() : CurrencyPair
    {
        if(!($this->currencyPair instanceof CurrencyPair)) throw new AppException('error.currency_pair.not_loaded');

        $this->currencyPair->setEnabled(true);

        return $this->update($this->currencyPair);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return CurrencyPair
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(CurrencyPair $currencyPair) : CurrencyPair
    {
        $this->currencyPair = $this->currencyPairRepository->save($currencyPair);

        return $this->currencyPair;
    }
}
