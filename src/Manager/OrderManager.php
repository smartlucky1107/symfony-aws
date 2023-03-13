<?php

namespace App\Manager;

use App\Document\NotificationInterface;
use App\Entity\Liquidity\ExternalOrder;
use App\Entity\OrderBook\Order;
use App\Event\WalletBalance\WalletBalanceBeforeOrderEvent;
use App\Exception\AppException;
use App\Manager\Liquidity\LiquidityManager;
use App\Model\PriceInterface;
use App\Repository\OrderBook\OrderRepository;
use App\Security\SystemTagAccessResolver;
use App\Security\TagAccessResolver;
use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\Price;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderManager
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var RedisSubscribeManager  */
    private $redisSubscribeManager;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NewOrderManager */
    private $newOrderManager;

    /** @var TagAccessResolver */
    private $tagAccessResolver;

    /** @var SystemTagAccessResolver*/
    private $systemTagAccessResolver;

    /** @var LiquidityManager */
    private $liquidityManager;

    /**
     * OrderManager constructor.
     * @param OrderRepository $orderRepository
     * @param RedisSubscribeManager $redisSubscribeManager
     * @param NotificationManager $notificationManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param NewOrderManager $newOrderManager
     * @param TagAccessResolver $tagAccessResolver
     * @param SystemTagAccessResolver $systemTagAccessResolver
     * @param LiquidityManager $liquidityManager
     */
    public function __construct(OrderRepository $orderRepository, RedisSubscribeManager $redisSubscribeManager, NotificationManager $notificationManager, EventDispatcherInterface $eventDispatcher, NewOrderManager $newOrderManager, TagAccessResolver $tagAccessResolver, SystemTagAccessResolver $systemTagAccessResolver, LiquidityManager $liquidityManager)
    {
        $this->orderRepository = $orderRepository;
        $this->redisSubscribeManager = $redisSubscribeManager;
        $this->notificationManager = $notificationManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->newOrderManager = $newOrderManager;
        $this->tagAccessResolver = $tagAccessResolver;
        $this->systemTagAccessResolver = $systemTagAccessResolver;
        $this->liquidityManager = $liquidityManager;
    }

    /**
     * Load Order to the class by $orderId
     *
     * @param int $orderId
     * @return Order
     * @throws AppException
     */
    public function load(int $orderId) : Order
    {
        /** @var Order $order */
        $order = $this->orderRepository->find($orderId);
        if(!($order instanceof Order)) throw new AppException('error.order.not_found');

        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Order $order) : Order
    {
        $order = $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws \Exception
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function placeOrder(Order $order) : Order
    {
        $this->systemTagAccessResolver->authTrading();

        // resolve user tag access
        $this->tagAccessResolver->authTrading($order->getUser(), $order);

        $order = $this->newOrderManager->preVerify($order);

        $instantLimitPrice = null;
        if($order->isInstantExecution()){
            if($order->getCurrencyPair()->isExternalLiquidityEnabled()){
                $instantLimitPrice = $this->liquidityManager->findInstantLimitPrice($order);
                // Sprawdzanie balancu czy na koncie external liquidity np. bitbay jest taka ilość którą on chce kupić/sprzedać
                $this->liquidityManager->verifyExternalMarketBalance($order, $instantLimitPrice);
            }else{
                throw new AppException('Order cannot be placed. Try again.');

//                $instantLimitPrice = $this->newOrderManager->findInstantLimitPrice($order);
            }
            $this->newOrderManager->verifyPlaceOrderInstant($order, $instantLimitPrice);
        }else{
            throw new AppException('Limit order is not allowed');
//            $this->newOrderManager->verifyPlaceOrder($order);
        }

        // place new order - save to DB
        $order = $this->save($order);

        // save wallet balance
        $this->eventDispatcher->dispatch(WalletBalanceBeforeOrderEvent::NAME, new WalletBalanceBeforeOrderEvent($order->getBaseCurrencyWallet(), $order->getBaseCurrencyWallet()->getAmount(), $order));
        $this->eventDispatcher->dispatch(WalletBalanceBeforeOrderEvent::NAME, new WalletBalanceBeforeOrderEvent($order->getQuotedCurrencyWallet(), $order->getQuotedCurrencyWallet()->getAmount(), $order));

        $this->pushForTrading($order);

        $this->notificationManager->create($order->getUser(), NotificationInterface::TYPE_ORDER_CREATED, $order);

        return $order;
    }

    /**
     * @param Order $order
     * @param string $amount
     * @return Order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function releaseBlockedAmount(Order $order, string $amount) : Order
    {
        $newAmountBlocked = bcsub($order->getAmountBlocked(), $amount, PriceInterface::BC_SCALE);
        $order->setAmountBlocked($newAmountBlocked);

        $order = $this->orderRepository->save($order);

        return $order;
    }

    /**
     * Send notification for websocket clients about new pending order - refresh public order book
     *
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function pushForTrading(Order $order) : bool
    {
        if($order->isInstantExecution()){
            // add for trading processor
            $this->redisSubscribeManager->pushOrder($order->getId(), true);
        }else{
            // add for trading processor
            $this->redisSubscribeManager->pushOrder($order->getId());
        }

        return true;
    }
}
