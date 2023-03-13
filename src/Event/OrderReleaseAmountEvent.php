<?php

namespace App\Event;

use App\Entity\OrderBook\Order;
use Symfony\Component\EventDispatcher\Event;

class OrderReleaseAmountEvent extends Event
{
    public const NAME = 'order.on_order_release_amount';

    /** @var Order */
    protected $order;

    /** @var string */
    protected $amount;

    /**
     * OrderReleaseAmountEvent constructor.
     * @param Order $order
     * @param string $amount
     */
    public function __construct(Order $order, string $amount)
    {
        $this->order = $order;
        $this->amount = $amount;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }
}