<?php

namespace App\Model\OrderBook;

use App\Entity\OrderBook\Order;
use App\Model\PriceInterface;

class OrderModel
{
    /** @var string */
    public $currencyPairShortName;

    /** @var int */
    public $type;

    /** @var string */
    public $amount;

    /** @var string */
    public $limitPrice;

    /** @var string */
    public $total;

    /** @var int */
    private $precision;

    /** @var int */
    private $precisionQuoted;

    /** @var int */
    private $precisionPair;

    /**
     * OrderModel constructor.
     * @param array $orderArray
     */
    public function __construct(array $orderArray)
    {
        $this->currencyPairShortName = $orderArray['currencyPair']['baseCurrency']['shortName'] . '-' . $orderArray['currencyPair']['quotedCurrency']['shortName'];
        $this->type = $orderArray['type'];
        if(isset($orderArray['limitPrice'])){
            $this->amount = $this->toPrecision(bcsub($orderArray['amount'], $orderArray['amountFilled'], PriceInterface::BC_SCALE), $orderArray['currencyPair']['baseCurrency']['roundPrecision']);
            $this->limitPrice = $orderArray['limitPrice'] ? $this->toPrecision($orderArray['limitPrice'], $orderArray['currencyPair']['quotedCurrency']['roundPrecision']) : $this->toPrecision(0, $orderArray['currencyPair']['quotedCurrency']['roundPrecision']);
        }elseif(isset($orderArray['liquidityRate'])){
            $this->amount = $this->toPrecision($orderArray['liquidityAmount'], $orderArray['currencyPair']['baseCurrency']['roundPrecision']);
            $this->limitPrice = $orderArray['liquidityRate'] ? $this->toPrecision($orderArray['liquidityRate'], $orderArray['currencyPair']['quotedCurrency']['roundPrecision']) : $this->toPrecision(0, $orderArray['currencyPair']['quotedCurrency']['roundPrecision']);
        }

        $this->precision = $orderArray['currencyPair']['baseCurrency']['roundPrecision'];
        $this->precisionQuoted = $orderArray['currencyPair']['quotedCurrency']['roundPrecision'];
        $this->precisionPair = $orderArray['currencyPair']['roundPrecision'];

        $this->refreshTotal();
    }

//    /**
//     * OrderModel constructor.
//     * @param Order $order
//     */
//    public function ___construct(Order $order)
//    {
//        $this->currencyPairShortName = $order->getCurrencyPair()->pairShortName();
//        $this->type = $order->getType();
//        $this->amount = $order->toPrecision($order->freeAmount());
//        $this->limitPrice = $order->getLimitPrice() ? $order->toPrecisionQuoted($order->getLimitPrice()) : $order->toPrecisionQuoted(0);
//
//        $this->precision = $order->getCurrencyPair()->getBaseCurrency()->getRoundPrecision();
//        $this->precisionQuoted = $order->getCurrencyPair()->getQuotedCurrency()->getRoundPrecision();
//
//        $this->refreshTotal();
//    }

    /**
     * @param string $value
     * @param int $precision
     * @return string
     */
    public function toPrecision(string $value, int $precision){
        return bcadd($value, 0, $precision);
    }

    /**
     * Verify if passed $orderModel is equal to current class
     *
     * @param OrderModel $orderModel
     * @return bool
     */
    public function isEqual(OrderModel $orderModel){
        if($this->currencyPairShortName === $orderModel->currencyPairShortName){
            if($this->type === $orderModel->type){
                if($this->limitPrice === $orderModel->limitPrice){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Refresh total amount
     */
    private function refreshTotal() : void
    {
        $this->total = bcmul($this->amount, $this->limitPrice, $this->precisionQuoted);
    }

    /**
     * Add amount to current order model and return the sum amount
     *
     * @param string $amount
     */
    public function addAmount(string $amount) : void
    {
        //$this->amount = $this->amount + $amount;

        $newAmount = bcadd($this->amount, $amount, $this->precision);
        $this->amount = $newAmount;

        $this->refreshTotal();
    }

    /**
     * @return string
     */
    public function getCurrencyPairShortName(): string
    {
        return $this->currencyPairShortName;
    }

    /**
     * @param string $currencyPairShortName
     */
    public function setCurrencyPairShortName(string $currencyPairShortName): void
    {
        $this->currencyPairShortName = $currencyPairShortName;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getLimitPrice(): string
    {
        return $this->limitPrice;
    }

    /**
     * @param string $limitPrice
     */
    public function setLimitPrice(string $limitPrice): void
    {
        $this->limitPrice = $limitPrice;
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        return $this->total;
    }

    /**
     * @param string $total
     */
    public function setTotal(string $total): void
    {
        $this->total = $total;
    }
}
