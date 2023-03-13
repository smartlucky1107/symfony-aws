<?php

namespace App\Manager\Charting;

use App\Document\OHLC;
use App\Entity\OrderBook\Trade;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class OHLCManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * OHLCManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function dmClear(){
        $this->dm->clear();
    }

    /**
     * Load OHLC document by $time
     *
     * @param $symbol
     * @param int $time
     * @param string $period
     * @return OHLC
     */
    public function loadByTimePeriod(string $symbol, int $time, string $period)
    {
        /** @var OHLC $OHLC */
        $OHLC = $this->dm->getRepository(OHLC::class)->findOneBy([
            'symbol' => $symbol,
            'time' => $time,
            'period' => $period
        ]);

        return $OHLC;
    }

    /**
     * Load OHLC results for passed parameters for charting
     *
     * @param string $symbol
     * @param string $period
     * @param int $from
     * @param int $to
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadForCharting(string $symbol, string $period, int $from, int $to){
        $qb = $this->dm->createQueryBuilder(OHLC::class);
        $qb->field('time')->range($from, $to);
        $qb->field('period')->equals($period);
        $qb->field('symbol')->equals($symbol);
        $qb->sort('time', 1);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $symbol
     * @param string $period
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadFirstCandle(string $symbol, string $period){
        $qb = $this->dm->createQueryBuilder(OHLC::class);
        $qb->field('period')->equals($period);
        $qb->field('symbol')->equals($symbol);
        $qb->sort('time', 1);
        $qb->limit(1);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param string $symbol
     * @param string $period
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadLastCandle(string $symbol, string $period){
        $qb = $this->dm->createQueryBuilder(OHLC::class);
        $qb->field('period')->equals($period);
        $qb->field('symbol')->equals($symbol);
        $qb->sort('time', -1);
        $qb->limit(1);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * Convert $period and $dateTime to time in integer
     *
     * @param string $period
     * @param \DateTime $dateTime
     * @return int
     * @throws \Exception
     */
    public function periodToTime(string $period, \DateTime $dateTime) : int
    {
        // recreate datetime object
        $datetimeString = $dateTime->format('Y-m-d H:i');
        $dt = new \DateTime($datetimeString);
        $hour = (int) $dt->format('H');
        $minute = (int) $dt->format('i');

        switch ($period) {
            case OHLC::PERIOD_1M:
                // no modifications
                break;
            case OHLC::PERIOD_2M:
                $minute = $minute - ($minute % 2);
                $dt->setTime($hour, $minute, 0);
                break;
            case OHLC::PERIOD_5M:
                $minute = $minute - ($minute % 5);
                $dt->setTime($hour, $minute, 0);
                break;
            case OHLC::PERIOD_15M:
                $minute = $minute - ($minute % 15);
                $dt->setTime($hour, $minute, 0);
                break;
            case OHLC::PERIOD_30M:
                $minute = $minute - ($minute % 30);
                $dt->setTime($hour, $minute, 0);
                break;
            case OHLC::PERIOD_1H:
                $dt->setTime($hour, 0, 0);
                break;
            case OHLC::PERIOD_2H:
                $hour = $hour - ($hour % 2);
                $dt->setTime($hour, 0, 0);
                break;
            case OHLC::PERIOD_4H:
                $hour = $hour - ($hour % 4);
                $dt->setTime($hour, 0, 0);
                break;
            case OHLC::PERIOD_12H:
                $hour = $hour - ($hour % 12);
                $dt->setTime($hour, 0, 0);
                break;
            case OHLC::PERIOD_1D:
                $dt->setTime(0, 0, 0);
                break;
        }
        $dt->modify('+2 hours');

        return strtotime($dt->format('Y-m-d H:i'));
    }

//    /**
//     * @param Trade $trade
//     * @return bool
//     * @throws \Exception
//     */
//    public function processTrade(Trade $trade){
//        foreach(OHLC::PERIODS as $periodKey => $periodValue){
//            $time = $this->periodToTime($periodKey, $trade->getCreatedAt());
//
//            /** @var OHLC $ohlc */
//            $ohlc = $this->loadByTimePeriod($trade->getOrderSell()->getCurrencyPair()->pairShortName(), $time, $periodKey);
//            if($ohlc instanceof  OHLC){
//                $ohlc->addTrade($trade->getPrice(), $trade->getAmount());
//            }else{
//                $ohlc = new OHLC($trade->getOrderSell()->getCurrencyPair()->pairShortName(), $periodKey, $time, $trade->getAmount(), $trade->getPrice());
//            }
//            $this->save($ohlc);
//        }
//
//        return true;
//    }

    /**
     * @param OHLC $OHLC
     * @return OHLC
     */
    public function save(OHLC $OHLC){

        $this->dm->persist($OHLC);
        $this->dm->flush();

        return $OHLC;
    }
}
