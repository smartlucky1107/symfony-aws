<?php

namespace App\EventListener;

use App\Event\OrderReleaseAmountEvent;
use App\Manager\OrderManager;

class OrderUpdateListener
{
    /** @var OrderManager */
    private $orderManager;

    /**
     * OrderUpdateListener constructor.
     * @param OrderManager $orderManager
     */
    public function __construct(OrderManager $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    /**
     * @param OrderReleaseAmountEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onOrderRelease(OrderReleaseAmountEvent $event)
    {
        $this->orderManager->releaseBlockedAmount($event->getOrder(), $event->getAmount());
    }
}