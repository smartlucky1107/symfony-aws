<?php

namespace App\Manager;

use App\Document\NotificationInterface;
use App\Entity\OrderBook\Order;
use App\Event\OrderReleaseAmountEvent;
use App\Event\UserSetRecentOrderAtEvent;
use App\Event\WalletTransferOrderEvent;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\OrderBook\OrderRepository;
use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\Price;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NewOrderManager
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var RedisSubscribeManager  */
    private $redisSubscribeManager;

    /**
     * NewOrderManager constructor.
     * @param OrderRepository $orderRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param NotificationManager $notificationManager
     * @param RedisSubscribeManager $redisSubscribeManager
     */
    public function __construct(OrderRepository $orderRepository, EventDispatcherInterface $eventDispatcher, NotificationManager $notificationManager, RedisSubscribeManager $redisSubscribeManager)
    {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->notificationManager = $notificationManager;
        $this->redisSubscribeManager = $redisSubscribeManager;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws AppException
     * @throws \Exception
     */
    public function preVerify(Order $order) : Order
    {
        // MOVED TO OrderTransformer
//        if($order->getLimitPrice()){
//            if($order->getCurrencyPair()->getMinLimitPrice()){
//                $comp = bccomp($order->getLimitPrice(), $order->getCurrencyPair()->getMinLimitPrice(), PriceInterface::BC_SCALE);
//                if($comp === -1) throw new AppException('Limit price did not reached the minimum');
//            }
//            if($order->getCurrencyPair()->getMaxLimitPrice()){
//                $comp = bccomp($order->getLimitPrice(), $order->getCurrencyPair()->getMaxLimitPrice(), PriceInterface::BC_SCALE);
//                if($comp === 1)  throw new AppException('Limit price did not reached the maximum');
//            }
//        }

//        // hedge blocker
//        /** @var Order $hedgeOrder */
//        $hedgeOrder = $this->orderRepository->findHedgeOrder($order);
//        if($hedgeOrder instanceof Order) throw new AppException('Hedge positions not allowed');

//        // block flush orders
//        if(!$order->getUser()->isNewOrderAllowed()) throw new AppException('error.user.limit_order_not_allowed');
//        $this->eventDispatcher->dispatch(UserSetRecentOrderAtEvent::NAME, new UserSetRecentOrderAtEvent($order->getUser(), $order->getCreatedAt()));

        if($order->isInstantExecution()){
            //throw new AppException('error.user.market_order_not_allowed');

            if(!$order->getUser()->isMarketOrderAllowed()) throw new AppException('error.user.market_order_not_allowed');
        }

        return $order;
    }

    /**
     * @param Order $order
     * @return string|null
     */
    public function findInstantLimitPrice(Order $order) : ?string
    {
        if($order->isBid()){
            return $this->orderRepository->findLiquidityLimitPrice($order->getCurrencyPair(),Order::TYPE_SELL, $order->getAmount());
        }elseif($order->isOffer()){
            return $this->orderRepository->findLiquidityLimitPrice($order->getCurrencyPair(),Order::TYPE_BUY, $order->getAmount());
        }

        return null;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws AppException
     */
    public function verifyPlaceOrder(Order $order) : Order
    {
        if($order->isBid()){
            if(!$order->getQuotedCurrencyWallet()->isTransferAllowed($order->quotedCurrencyTotal())){
                throw new AppException('error.wallet.insufficient_funds');
            }
        }elseif($order->isOffer()){
            if(!$order->getBaseCurrencyWallet()->isTransferAllowed($order->baseCurrencyTotal())){
                throw new AppException('error.wallet.insufficient_funds');
            }
        }

        return $order;
    }

    /**
     * @param Order $order
     * @param string|null $instantLimitPrice
     * @return Order
     * @throws AppException
     */
    public function verifyPlaceOrderInstant(Order $order, string $instantLimitPrice = null) : Order
    {
        if($instantLimitPrice){
            if($order->isBid()){
                if(!$order->getQuotedCurrencyWallet()->isTransferAllowed($order->quotedCurrencyTotalCalculate($instantLimitPrice))){
                    throw new AppException('error.wallet.insufficient_funds');
                }
            }elseif($order->isOffer()){
                if(!$order->getBaseCurrencyWallet()->isTransferAllowed($order->baseCurrencyTotal())){
                    throw new AppException('error.wallet.insufficient_funds');
                }
            }
        }else{
            throw new AppException('Liquidity does not allow to create instant order');
        }

        return $order;
    }

    /**
     * @param string|null $instantLimitPrice
     * @param Order $order
     * @return Order
     * @throws \Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function blockOrderWallets(Order $order, string $instantLimitPrice = null) : Order
    {
        // Do not block external liquidity orders
        if($order->isExternalLiquidityOrder()) return $order;

        // Block rest of them
        if($order->isInstantExecution()) {
            if($order->isBid()){
                $blockAmount = $order->quotedCurrencyTotalCalculate($instantLimitPrice);
                $order->setAmountBlocked($blockAmount);

                $this->eventDispatcher->dispatch(
                    WalletTransferOrderEvent::NAME,
                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_BLOCK, $order->getQuotedCurrencyWallet()->getId(), $blockAmount)
                );
            }elseif($order->isOffer()){
                $blockAmount = $order->baseCurrencyTotal();
                $order->setAmountBlocked($blockAmount);

                $this->eventDispatcher->dispatch(
                    WalletTransferOrderEvent::NAME,
                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_BLOCK, $order->getBaseCurrencyWallet()->getId(), $blockAmount)
                );
            }

            $order = $this->orderRepository->save($order);
        }else {
            if($order->isBid()){
                $blockAmount = $order->quotedCurrencyTotal();
                $order->setAmountBlocked($blockAmount);

                $this->eventDispatcher->dispatch(
                    WalletTransferOrderEvent::NAME,
                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_BLOCK, $order->getQuotedCurrencyWallet()->getId(), $blockAmount)
                );
            }elseif($order->isOffer()){
                $blockAmount = $order->baseCurrencyTotal();
                $order->setAmountBlocked($blockAmount);

                $this->eventDispatcher->dispatch(
                    WalletTransferOrderEvent::NAME,
                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_BLOCK, $order->getBaseCurrencyWallet()->getId(), $blockAmount)
                );
            }

            $order = $this->orderRepository->save($order);
        }

        return $order;
    }

//    /**
//     * @param Order $order
//     * @return Order
//     */
//    public function releaseOrderWallets(Order $order) : Order
//    {
//        // Do not release external liquidity orders
//        if($order->isExternalLiquidityOrder()) return $order;
//
//        if(is_numeric($order->getAmountBlocked())){
//            if($order->isBid()){
//                $this->eventDispatcher->dispatch(
//                    WalletTransferOrderEvent::NAME,
//                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_RELEASE, $order->getQuotedCurrencyWallet()->getId(), $order->getAmountBlocked())
//                );
//            }elseif($order->isOffer()){
//                $this->eventDispatcher->dispatch(
//                    WalletTransferOrderEvent::NAME,
//                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_RELEASE, $order->getBaseCurrencyWallet()->getId(), $order->getAmountBlocked())
//                );
//            }
//
//            $this->eventDispatcher->dispatch(OrderReleaseAmountEvent::NAME, new OrderReleaseAmountEvent($order, $order->getAmountBlocked()));
//        }else{
//            if($order->isBid()){
//                $this->eventDispatcher->dispatch(
//                    WalletTransferOrderEvent::NAME,
//                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_RELEASE, $order->getQuotedCurrencyWallet()->getId(), $order->quotedCurrencyFreeTotal())
//                );
//            }elseif($order->isOffer()){
//                $this->eventDispatcher->dispatch(
//                    WalletTransferOrderEvent::NAME,
//                    new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_RELEASE, $order->getBaseCurrencyWallet()->getId(), $order->baseCurrencyFreeTotal())
//                );
//            }
//        }
//
//        return $order;
//    }

    /**
     * Reject order with no release or other wallet transfers
     *
     * @param Order $order
     * @return Order
     * @throws \Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function reject(Order $order) : Order
    {
        $order->setStatus(Order::STATUS_REJECTED);

        $order = $this->orderRepository->save($order);

        $this->notificationManager->create($order->getUser(), NotificationInterface::TYPE_ORDER_REJECTED, $order);

        return $order;
    }

    public function releaseBlockedAmount(Order $order)
    {
        if(is_numeric($order->getAmountBlocked())){
            $comp = bccomp($order->getAmountBlocked(), 0, PriceInterface::BC_SCALE);
            if($comp === 1){
                if($order->isBid()){
                    $this->eventDispatcher->dispatch(
                        WalletTransferOrderEvent::NAME,
                        new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_RELEASE, $order->getQuotedCurrencyWallet()->getId(), $order->getAmountBlocked())
                    );
                }elseif($order->isOffer()){
                    $this->eventDispatcher->dispatch(
                        WalletTransferOrderEvent::NAME,
                        new WalletTransferOrderEvent($order->getId(), WalletTransferInterface::TYPE_RELEASE, $order->getBaseCurrencyWallet()->getId(), $order->getAmountBlocked())
                    );
                }

                $this->eventDispatcher->dispatch(OrderReleaseAmountEvent::NAME, new OrderReleaseAmountEvent($order, $order->getAmountBlocked()));
            }
        }
    }
}
