<?php

namespace App\Model\OrderBook;

class OrderBookModel
{
    /** @var array  Array of Order object */
    public $bidOrders = [];

    /** @var array  Array of Order object */
    public $offerOrders = [];

    /**
     * @return bool
     */
    public function isBidAllowed(){
        return true;

        if(count($this->bidOrders) > 50){
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isOfferAllowed(){
        return true;

        if(count($this->offerOrders) > 50){
            return false;
        }

        return true;
    }

    /**
     * @param OrderModel $orderModel
     */
    public function addBid(OrderModel $orderModel)
    {
        $isEqual = false;

        /** @var OrderModel $bidOrder */
        foreach($this->bidOrders as $bidOrder){
            if($bidOrder->isEqual($orderModel)){
                $bidOrder->addAmount($orderModel->amount);

                $isEqual = true;
                break;
            }
        }

        if(!$isEqual){
            $this->bidOrders[] = $orderModel;
        }
    }

    /**
     * @param OrderModel $orderModel
     */
    public function addOffer(OrderModel $orderModel)
    {
        $isEqual = false;

        /** @var OrderModel $offerOrder */
        foreach($this->offerOrders as $offerOrder){
            if($offerOrder->isEqual($orderModel)){
                $offerOrder->addAmount($orderModel->amount);

                $isEqual = true;
                break;
            }
        }

        if(!$isEqual){
            $this->offerOrders[] = $orderModel;
        }
    }
}