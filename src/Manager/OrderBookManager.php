<?php

namespace App\Manager;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Model\OrderBook\OrderBookModel;
use App\Model\OrderBook\OrderModel;
use App\Model\PriceInterface;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\OrderBook\TradeRepository;

class OrderBookManager
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var TradeRepository */
    private $tradeRepository;

    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /**
     * OrderBookManager constructor.
     * @param OrderRepository $orderRepository
     * @param TradeRepository $tradeRepository
     * @param ExternalOrderRepository $externalOrderRepository
     */
    public function __construct(OrderRepository $orderRepository, TradeRepository $tradeRepository, ExternalOrderRepository $externalOrderRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->tradeRepository = $tradeRepository;
        $this->externalOrderRepository = $externalOrderRepository;
    }

    /**
     * Generate and return order book
     *
     * @param CurrencyPair $currencyPair
     * @return OrderBookModel
     */
    public function generateOrderBook(CurrencyPair $currencyPair) : OrderBookModel
    {
        return $this->generateCombinedOrderbook($currencyPair);

        $orderBook = new OrderBookModel();

        $offersArray = $this->orderRepository->findOffersArray($currencyPair);
        $bidsArray = $this->orderRepository->findBidsArray($currencyPair);

        if($offersArray){
            foreach($offersArray as $offerOrder){
                if($orderBook->isOfferAllowed()){
                    /** @var OrderModel $orderModel */
                    $orderModel = new OrderModel($offerOrder);
                    $orderBook->addOffer($orderModel);
                }
            }
        }

        if($bidsArray){
            foreach($bidsArray as $bidOrder){
                if($orderBook->isBidAllowed()){
                    /** @var OrderModel $orderModel */
                    $orderModel = new OrderModel($bidOrder);
                    $orderBook->addBid($orderModel);
                }
            }
        }

        return $orderBook;

//        $offers = $this->orderRepository->findOffers($currencyPair);
//        $bids = $this->orderRepository->findBids($currencyPair);
//
//        if($offers){
//            /** @var Order $offerOrder */
//            foreach($offers as $offerOrder){
//                if($orderBook->isOfferAllowed()){
//                    /** @var OrderModel $orderModel */
//                    $orderModel = new OrderModel($offerOrder);
//                    $orderBook->addOffer($orderModel);
//                }
//            }
//        }
//
//        if($bids){
//            /** @var Order $bidOrder */
//            foreach($bids as $bidOrder){
//                if($orderBook->isBidAllowed()){
//                    /** @var OrderModel $orderModel */
//                    $orderModel = new OrderModel($bidOrder);
//                    $orderBook->addBid($orderModel);
//                }
//            }
//        }
//
//        return $orderBook;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return OrderBookModel
     */
    public function generateCombinedOrderbook(CurrencyPair $currencyPair) : OrderBookModel
    {
        $orderBook = new OrderBookModel();

        if($currencyPair->isExternalLiquidityEnabled()){
            $offersArray = $this->externalOrderRepository->findOffersArray($currencyPair, true, 17);
            $bidsArray = $this->externalOrderRepository->findBidsArray($currencyPair, true, 17);

            if($offersArray){
                foreach($offersArray as $offerOrder){
                    if($orderBook->isOfferAllowed()){
                        /** @var OrderModel $orderModel */
                        $orderModel = new OrderModel($offerOrder);
                        $orderBook->addOffer($orderModel);
                    }
                }
            }

            if($bidsArray){
                foreach($bidsArray as $bidOrder){
                    if($orderBook->isBidAllowed()){
                        /** @var OrderModel $orderModel */
                        $orderModel = new OrderModel($bidOrder);
                        $orderBook->addBid($orderModel);
                    }
                }
            }
        }

        $offersArray = $this->orderRepository->findOffersArray($currencyPair);
        $bidsArray = $this->orderRepository->findBidsArray($currencyPair);

        if($offersArray){
            foreach($offersArray as $offerOrder){
                if($orderBook->isOfferAllowed()){
                    /** @var OrderModel $orderModel */
                    $orderModel = new OrderModel($offerOrder);
                    $orderBook->addOffer($orderModel);
                }
            }
        }

        if($bidsArray){
            foreach($bidsArray as $bidOrder){
                if($orderBook->isBidAllowed()){
                    /** @var OrderModel $orderModel */
                    $orderModel = new OrderModel($bidOrder);
                    $orderBook->addBid($orderModel);
                }
            }
        }

        if($currencyPair->isExternalLiquidityEnabled()){
            if(is_array($orderBook->bidOrders) && count($orderBook->bidOrders) > 0){
                usort($orderBook->bidOrders, function($a, $b) {return bccomp($a->limitPrice, $b->limitPrice, PriceInterface::BC_SCALE);});
                $orderBook->bidOrders = array_reverse($orderBook->bidOrders);
            }

            if(is_array($orderBook->offerOrders) && count($orderBook->offerOrders) > 0){
                usort($orderBook->offerOrders, function($a, $b) {return bccomp($a->limitPrice, $b->limitPrice, PriceInterface::BC_SCALE);});
            }
        }

        return $orderBook;
    }

    public function generateExternalOrderbook(CurrencyPair $currencyPair) : OrderBookModel
    {
        $orderBook = new OrderBookModel();

        $offersArray = $this->externalOrderRepository->findOffersArray($currencyPair);
        $bidsArray = $this->externalOrderRepository->findBidsArray($currencyPair);

        if($offersArray){
            foreach($offersArray as $offerOrder){
                if($orderBook->isOfferAllowed()){
                    /** @var OrderModel $orderModel */
                    $orderModel = new OrderModel($offerOrder);
                    $orderBook->addOffer($orderModel);
                }
            }
        }

        if($bidsArray){
            foreach($bidsArray as $bidOrder){
                if($orderBook->isBidAllowed()){
                    /** @var OrderModel $orderModel */
                    $orderModel = new OrderModel($bidOrder);
                    $orderBook->addBid($orderModel);
                }
            }
        }

        return $orderBook;
    }
}
