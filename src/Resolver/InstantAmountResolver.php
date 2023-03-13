<?php

namespace App\Resolver;

use App\Entity\CurrencyPair;
use App\Entity\Liquidity\ExternalOrder;
use App\Model\PriceInterface;
use App\Repository\Liquidity\ExternalOrderRepository;

class InstantAmountResolver
{
    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /**
     * InstantAmountResolver constructor.
     * @param ExternalOrderRepository $externalOrderRepository
     */
    public function __construct(ExternalOrderRepository $externalOrderRepository)
    {
        $this->externalOrderRepository = $externalOrderRepository;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param string $amount
     * @return string|null
     */
    public function resolveBuy(CurrencyPair $currencyPair, string $amount) : ?string
    {
        $limitPrice = null;

        if($currencyPair->isExternalLiquidityEnabled()){
            $externalLimitPrice = $this->externalOrderRepository->findLiquidityAmount($currencyPair, ExternalOrder::TYPE_BUY, $amount);

            $comp = bccomp($externalLimitPrice, $limitPrice, PriceInterface::BC_SCALE);
            if($comp === 1){
                $limitPrice = $externalLimitPrice;
            }

            if(is_null($limitPrice)) $limitPrice = $externalLimitPrice;
        }

        return $limitPrice;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param string $amount
     * @return string|null
     */
    public function resolveSell(CurrencyPair $currencyPair, string $amount) : ?string
    {
        $limitPrice = null;

        if($currencyPair->isExternalLiquidityEnabled()){
            $externalLimitPrice = $this->externalOrderRepository->findLiquidityAmount($currencyPair, ExternalOrder::TYPE_SELL, $amount);

            $comp = bccomp($externalLimitPrice, $limitPrice, PriceInterface::BC_SCALE);
            if($comp === 1){
                $limitPrice = $externalLimitPrice;
            }

            if(is_null($limitPrice)) $limitPrice = $externalLimitPrice;
        }

        return $limitPrice;
    }
}
