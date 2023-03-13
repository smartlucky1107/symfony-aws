<?php

namespace App\Model;

class TradingTransactionModel
{
    /** @var string */
    public $tradingTransactionId;

    /** @var int */
    public $orderId;

    /**
     * TradingTransactionModel constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['tradingTransactionId'])) $this->setTradingTransactionId($data['tradingTransactionId']);
        if(isset($data['orderId'])) $this->setOrderId($data['orderId']);
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->tradingTransactionId && $this->orderId){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTradingTransactionId(): string
    {
        return $this->tradingTransactionId;
    }

    /**
     * @param string $tradingTransactionId
     */
    public function setTradingTransactionId(string $tradingTransactionId): void
    {
        $this->tradingTransactionId = $tradingTransactionId;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     */
    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }
}