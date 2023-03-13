<?php

namespace App\Resolver;

use App\Document\OHLC;
use App\Entity\CurrencyPair;
use App\Manager\Charting\OHLCManager;
use App\Model\PriceInterface;

class GrowthResolver
{
    /** @var OHLCManager */
    private $ohlcManager;

    /** @var \DateTime */
    private $dateFrom;

    /** @var \DateTime */
    private $dateTo;

    /**
     * GrowthResolver constructor.
     * @param OHLCManager $ohlcManager
     * @throws \Exception
     */
    public function __construct(OHLCManager $ohlcManager)
    {
        $this->ohlcManager = $ohlcManager;

        $this->init();
    }

    /**
     * @throws \Exception
     */
    public function init(){
        $this->dateFrom = new \DateTime('now');
        //$this->dateFrom->setTime((int) $this->dateFrom->format('H'), 0 , 0);
        $this->dateFrom->modify('-24 hours');

        $this->dateTo = new \DateTime('now');
    }

    /**
     * Resolve percentage rate of the $currencyPair in last 24h
     *
     * @param CurrencyPair $currencyPair
     * @return float
     * @throws \Exception
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function resolveGrowth(CurrencyPair $currencyPair) : float
    {
        $open = null;
        $close = null;
//
//        $lastOhlc = $this->ohlcManager->loadLastCandle($currencyPair->pairShortName(), OHLC::PERIOD_1D);
//        if($lastOhlc){
//            /** @var OHLC $ohlc */
//            foreach($lastOhlc as $ohlc){
//                $open = $ohlc->getOpen();
//                $close = $ohlc->getClose();
//
//                break;
//            }
//        }
//
//        if($open > 0 && $close > 0){
//            return round((($close - $open) / $open) * 100, 2);
//        }
//
//        return 0;

        $timeFrom = strtotime($this->dateFrom->format('Y-m-d H:i'));
        $timeTo = strtotime($this->dateTo->format('Y-m-d H:i'));

        $ohlcList = $this->ohlcManager->loadForCharting($currencyPair->pairShortName(), OHLC::PERIOD_1H, $timeFrom, $timeTo);
        if(count($ohlcList) > 0){
            $i = 1;
            /** @var OHLC $ohlc */
            foreach($ohlcList as $ohlc){
                if($i === 1){
                    $open = $ohlc->getOpen();
                }
                if($i === count($ohlcList)){
                    $close = $ohlc->getClose();
                }
                $i++;
            }
        }

        if($open > 0 && $close > 0){
            return round((($close - $open) / $open) * 100, 2);
        }

        return 0;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return array
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function resolve1hPoints(CurrencyPair $currencyPair) : array
    {
        $points = [];

        $timeFrom = strtotime($this->dateFrom->format('Y-m-d H:i'));
        $timeTo = strtotime($this->dateTo->format('Y-m-d H:i'));

        $ohlcList = $this->ohlcManager->loadForCharting($currencyPair->pairShortName(), OHLC::PERIOD_1H, $timeFrom, $timeTo);
        if(count($ohlcList) > 0){
            /** @var OHLC $ohlc */
            foreach($ohlcList as $ohlc){
                $points[] = $ohlc->getClose();

                // TODO enable that when charts will be downloaded from KRAKEN - now it's all from Bitbay
//                if($currencyPair->isTetherBalancer()){
//                    $points[] = $currencyPair->toTradePrecision(bcmul($ohlc->getClose(), $currencyPair->getTetherBalancerAsk(), PriceInterface::BC_SCALE));
//                }elseif($currencyPair->isEuroBalancer()){
//                    $points[] = $currencyPair->toTradePrecision(bcmul($ohlc->getClose(), $currencyPair->getEuroBalancerAsk(), PriceInterface::BC_SCALE));
//                }else{
//                    $points[] = $ohlc->getClose();
//                }
            }
        }

        return $points;
    }
}
