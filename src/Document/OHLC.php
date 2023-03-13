<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
/**
 * @MongoDB\Document
 */
class OHLC
{
    const PERIOD_1M     = '1';
    const PERIOD_2M     = '2';
    const PERIOD_5M     = '5';
    const PERIOD_15M    = '15';
    const PERIOD_30M    = '30';
    const PERIOD_1H     = '60';
    const PERIOD_2H     = '120';
    const PERIOD_4H     = '240';
    const PERIOD_12H    = '720';
    const PERIOD_1D     = 'D';

    const PERIODS = [
        self::PERIOD_1M     => self::PERIOD_1M,
        self::PERIOD_2M     => self::PERIOD_2M,
        self::PERIOD_5M     => self::PERIOD_5M,
        self::PERIOD_15M    => self::PERIOD_15M,
        self::PERIOD_30M    => self::PERIOD_30M,
        self::PERIOD_1H     => self::PERIOD_1H,
        self::PERIOD_2H     => self::PERIOD_2H,
        self::PERIOD_4H     => self::PERIOD_4H,
        self::PERIOD_12H    => self::PERIOD_12H,
        self::PERIOD_1D     => self::PERIOD_1D,
    ];

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $symbol;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $period;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $time;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $close;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $open;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $high;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $low;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $volume;

    /**
     * OHLC constructor.
     * @param $symbol
     * @param $period
     * @param $time
     * @param $volume
     * @param $price
     */
    public function __construct($symbol, $period, $time, $volume, $price)
    {
        $this->symbol = $symbol;
        $this->period = $period;
        $this->time = $time;
        $this->volume = $volume;

        $this->close = $price;
        $this->open = $price;
        $this->high = $price;
        $this->low = $price;
    }

    /**
     * Add trade to the OHLC
     *
     * @param float $price
     * @param float $amount
     */
    public function addTrade(float $price, float $amount){
        if($price > $this->high){
            $this->high = $price;
        }

        if($price < $this->low){
            $this->low = $price;
        }

        $this->close = $price;
        $this->volume = (float) $this->volume + (float) $amount;
    }

    /**
     * @return mixed
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @param mixed $symbol
     */
    public function setSymbol($symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param mixed $period
     */
    public function setPeriod($period): void
    {
        $this->period = $period;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time): void
    {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getClose()
    {
        return $this->close;
    }

    /**
     * @param mixed $close
     */
    public function setClose($close): void
    {
        $this->close = $close;
    }

    /**
     * @return mixed
     */
    public function getOpen()
    {
        return $this->open;
    }

    /**
     * @param mixed $open
     */
    public function setOpen($open): void
    {
        $this->open = $open;
    }

    /**
     * @return mixed
     */
    public function getHigh()
    {
        return $this->high;
    }

    /**
     * @param mixed $high
     */
    public function setHigh($high): void
    {
        $this->high = $high;
    }

    /**
     * @return mixed
     */
    public function getLow()
    {
        return $this->low;
    }

    /**
     * @param mixed $low
     */
    public function setLow($low): void
    {
        $this->low = $low;
    }

    /**
     * @return mixed
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @param mixed $volume
     */
    public function setVolume($volume): void
    {
        $this->volume = $volume;
    }
}