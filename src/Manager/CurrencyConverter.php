<?php

namespace App\Manager;

use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Exception\AppException;
use App\Repository\CurrencyPairRepository;
use App\Repository\CurrencyRepository;
use App\Resolver\PriceResolver;

class CurrencyConverter
{
    /** @var PriceResolver */
    private $priceResolver;

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var CurrencyRepository */
    private $currencyRepository;

    /**
     * CurrencyConverter constructor.
     * @param PriceResolver $priceResolver
     * @param CurrencyPairRepository $currencyPairRepository
     * @param CurrencyRepository $currencyRepository
     */
    public function __construct(PriceResolver $priceResolver, CurrencyPairRepository $currencyPairRepository, CurrencyRepository $currencyRepository)
    {
        $this->priceResolver = $priceResolver;
        $this->currencyPairRepository = $currencyPairRepository;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @param string $amount
     * @return string
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function convertPLNtoBTC(string $amount) : string
    {
        /** @var Currency $currencyPLN */
        $currencyPLN = $this->currencyRepository->findOneBy(['shortName' => 'PLN']);
        if(!($currencyPLN instanceof Currency)) throw new AppException('PLN currency not found');

        $currencyBTC = $this->currencyRepository->findOneBy(['shortName' => 'BTC']);
        if(!($currencyBTC instanceof Currency)) throw new AppException('BTC currency not found');

        return $this->convert($amount, $currencyPLN, $currencyBTC);
    }

    /**
     * @param string $amount
     * @return string
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function convertBTCtoPLN(string $amount) : string
    {
        /** @var Currency $currencyPLN */
        $currencyPLN = $this->currencyRepository->findOneBy(['shortName' => 'PLN']);
        if(!($currencyPLN instanceof Currency)) throw new AppException('PLN currency not found');

        $currencyBTC = $this->currencyRepository->findOneBy(['shortName' => 'BTC']);
        if(!($currencyBTC instanceof Currency)) throw new AppException('BTC currency not found');

        return $this->convert($amount, $currencyBTC, $currencyPLN);
    }

    /**
     * @param string $amount
     * @param Currency $currencyFrom
     * @return string
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function convertToBTC(string $amount, Currency $currencyFrom) : string
    {
        $currencyBTC = $this->currencyRepository->findOneBy(['shortName' => 'BTC']);
        if(!($currencyBTC instanceof Currency)) throw new AppException('BTC currency not found');

        return $this->convert($amount, $currencyFrom, $currencyBTC);
    }

    /**
     * @param string $amount
     * @param Currency $currencyFrom
     * @param Currency $currencyTo
     * @return string
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function convert(string $amount, Currency $currencyFrom, Currency $currencyTo) : string
    {
        $baseShortName = strtoupper($currencyFrom->getShortName());
        $quotedShortName = strtoupper($currencyTo->getShortName());

        if($baseShortName === $quotedShortName){
            return bcadd($amount, 0, $currencyTo->getRoundPrecision());
        }

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findByShortName($baseShortName . '-' . $quotedShortName);
        if($currencyPair instanceof CurrencyPair){
            $rate = $this->priceResolver->resolve($currencyPair);

            return bcmul($amount, $rate, $currencyTo->getRoundPrecision());
        }

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findByShortName($quotedShortName . '-' . $baseShortName);
        if($currencyPair instanceof CurrencyPair){
            $rate = $this->priceResolver->resolve($currencyPair);

            return bcdiv($amount, $rate, $currencyTo->getRoundPrecision());
        }

        throw new AppException('Currency pair not found');
    }
}