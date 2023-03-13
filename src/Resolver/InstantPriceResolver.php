<?php

namespace App\Resolver;

use App\Entity\CurrencyPair;
use App\Entity\Liquidity\ExternalOrder;
use App\Entity\OrderBook\Order;
use App\Model\PriceInterface;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Repository\OrderBook\OrderRepository;

class InstantPriceResolver
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /**
     * InstantPriceResolver constructor.
     * @param OrderRepository $orderRepository
     * @param ExternalOrderRepository $externalOrderRepository
     */
    public function __construct(OrderRepository $orderRepository, ExternalOrderRepository $externalOrderRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->externalOrderRepository = $externalOrderRepository;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param string $amount
     * @return string|null
     */
    public function resolveBuy(CurrencyPair $currencyPair, string $amount) : ?string
    {
        $limitPrice = $this->orderRepository->findLiquidityLimitPrice($currencyPair,Order::TYPE_BUY, $amount);

        if($currencyPair->isExternalLiquidityEnabled()){
            $externalLimitPrice = $this->externalOrderRepository->findLiquidityLimitPrice($currencyPair, ExternalOrder::TYPE_BUY, $amount);

            $comp = bccomp($externalLimitPrice, $limitPrice, PriceInterface::BC_SCALE);
            if($comp === 1){
                $limitPrice = $externalLimitPrice;
            }

            if(is_null($limitPrice)) $limitPrice = $externalLimitPrice;
        }

//        if($currencyPair->isTetherBalancer()){
//            $limitPrice = bcmul($limitPrice, $currencyPair->getTetherBalancerBid(), PriceInterface::BC_SCALE);
//        }elseif($currencyPair->isEuroBalancer()){
//            $limitPrice = bcmul($limitPrice, $currencyPair->getEuroBalancerBid(), PriceInterface::BC_SCALE);
//        }

        return $limitPrice;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param string $amount
     * @return string|null
     */
    public function resolveSell(CurrencyPair $currencyPair, string $amount) : ?string
    {
        $limitPrice = $this->orderRepository->findLiquidityLimitPrice($currencyPair,Order::TYPE_SELL, $amount);

        if($currencyPair->isExternalLiquidityEnabled()){
            $externalLimitPrice = $this->externalOrderRepository->findLiquidityLimitPrice($currencyPair, ExternalOrder::TYPE_SELL, $amount);


            $comp = bccomp($externalLimitPrice, $limitPrice, PriceInterface::BC_SCALE);
            if($comp === -1){
                $limitPrice = $externalLimitPrice;
            }

            if(is_null($limitPrice)) $limitPrice = $externalLimitPrice;
        }

//        if($currencyPair->isTetherBalancer()){
//            $limitPrice = bcmul($limitPrice, $currencyPair->getTetherBalancerAsk(), PriceInterface::BC_SCALE);
//        }elseif($currencyPair->isEuroBalancer()){
//            $limitPrice = bcmul($limitPrice, $currencyPair->getEuroBalancerAsk(), PriceInterface::BC_SCALE);
//        }

        return $limitPrice;
    }
}
