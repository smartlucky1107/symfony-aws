<?php

namespace App\Resolver;

use App\Document\OHLC;
use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Manager\Charting\OHLCManager;
use App\Model\PriceInterface;

class PriceResolver
{
    /** @var OHLCManager */
    private $ohlcManager;

    /**
     * PriceResolver constructor.
     * @param OHLCManager $ohlcManager
     */
    public function __construct(OHLCManager $ohlcManager)
    {
        $this->ohlcManager = $ohlcManager;
    }

    /**
     * Resolve $currencyPair
     *
     * @param CurrencyPair $currencyPair
     * @return float
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function resolve(CurrencyPair $currencyPair) : float
    {
        $lastOhlc = $this->ohlcManager->loadLastCandle($currencyPair->pairShortName(), OHLC::PERIOD_1H);
        if($lastOhlc){
            /** @var OHLC $ohlc */
            foreach($lastOhlc as $ohlc){
                return $ohlc->getClose();

                // TODO enable that when charts will be downloaded from KRAKEN - now it's all from Bitbay
//                if($currencyPair->isTetherBalancer()){
//                    return $currencyPair->toTradePrecision(bcmul($ohlc->getClose(), $currencyPair->getTetherBalancerAsk(), PriceInterface::BC_SCALE));
//                }elseif($currencyPair->isEuroBalancer()){
//                    return $currencyPair->toTradePrecision(bcmul($ohlc->getClose(), $currencyPair->getEuroBalancerAsk(), PriceInterface::BC_SCALE));
//                }else{
//                    return $ohlc->getClose();
//                }
            }
        }

        return 0;
    }
}
