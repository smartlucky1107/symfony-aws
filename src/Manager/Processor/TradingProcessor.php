<?php

namespace App\Manager\Processor;

use App\Document\NotificationInterface;
use App\Entity\Liquidity\ExternalOrder;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Exception\AppException;
use App\Manager\Liquidity\LiquidityManager;
use App\Manager\NewOrderManager;
use App\Manager\NotificationManager;
use App\Manager\RedisSubscribeManager;
use App\Manager\TradeManager;
use App\Manager\WalletManager;
use App\Model\PriceInterface;
use App\Repository\OrderBook\OrderRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TradingProcessor
{
    /** @var Order */
    private $order;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var TradeManager */
    private $tradeManager;

    /** @var WalletManager */
    private $walletManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NewOrderManager */
    private $newOrderManager;

    /** @var LiquidityManager */
    private $liquidityManager;

    /** @var RedisSubscribeManager  */
    private $redisSubscribeManager;

    /** @var NotificationManager */
    private $notificationManager;

    /**
     * TradingProcessor constructor.
     * @param OrderRepository $orderRepository
     * @param TradeManager $tradeManager
     * @param WalletManager $walletManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param NewOrderManager $newOrderManager
     * @param LiquidityManager $liquidityManager
     * @param RedisSubscribeManager $redisSubscribeManager
     * @param NotificationManager $notificationManager
     */
    public function __construct(OrderRepository $orderRepository, TradeManager $tradeManager, WalletManager $walletManager, EventDispatcherInterface $eventDispatcher, NewOrderManager $newOrderManager, LiquidityManager $liquidityManager, RedisSubscribeManager $redisSubscribeManager, NotificationManager $notificationManager)
    {
        $this->orderRepository = $orderRepository;
        $this->tradeManager = $tradeManager;
        $this->walletManager = $walletManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->newOrderManager = $newOrderManager;
        $this->liquidityManager = $liquidityManager;
        $this->redisSubscribeManager = $redisSubscribeManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param int $orderId
     * @return Order
     * @throws AppException
     */
    public function loadOrder(int $orderId) : Order
    {
        $this->order = $this->orderRepository->find($orderId);
        if(!($this->order instanceof Order)) throw new AppException('error.trading.order_not_found');

        return $this->order;
    }

    public function clearMemory() : void
    {
        $this->order = null;
        unset($this->order);
    }

    /**
     * @param Order $order
     * @param string $amount
     * @return Order
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function fillOrder(Order $order, string $amount) : Order
    {
        $comp = bccomp($amount, $order->getAmount(), PriceInterface::BC_SCALE);
        if($comp === 1) throw new AppException('error.trading.order_cannot_be_filled');

        $bcadd = bcadd($amount, $order->getAmountFilled(),PriceInterface::BC_SCALE);
        $comp = bccomp($bcadd, $order->getAmount(), PriceInterface::BC_SCALE);
        if($comp === 1) throw new AppException('error.trading.order_cannot_be_filled');

        $newAmountFilled = bcadd($amount, $order->getAmountFilled(), PriceInterface::BC_SCALE);
        $order->setAmountFilled($newAmountFilled);
        if($order->getAmountFilled() === $order->getAmount()){
            $order->setIsFilled(true);
            $order->setStatus(Order::STATUS_FILLED);
        }

        $order = $this->orderRepository->save($order);

        // push notification
        try{
            $this->notificationManager->create($order->getUser(), NotificationInterface::TYPE_ORDER_FILLED, $order);
        }catch (\Exception $exception){}

        return $order;
    }

    /**
     * Load liquidity for trading new order
     *
     * @param Order $order
     * @param float $limitPrice
     * @return array|null
     */
    private function findLiquidity(Order $order, float $limitPrice) : ?array
    {
        if($order->isBid()){
            return $this->orderRepository->findLiquidity($order->getCurrencyPair(), Order::TYPE_SELL, $limitPrice);
        }elseif($order->isOffer()){
            return $this->orderRepository->findLiquidity($order->getCurrencyPair(), Order::TYPE_BUY, $limitPrice);
        }

        return null;
    }

    /**
     * @param Trade $trade
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function processTrade(Trade $trade){
        // update wallets balances
        $this->walletManager->transferTheTrade($trade);
//
//        // update trading volumes for users in the trade
//        TODO dodac wyliczanie trading volume
    }

    /**
     * @return bool
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processTrading() : bool
    {
        $instantLimitPrice = null;

        try{
            // verify if status == NEW, make sure the order was not rejected before this process runs
            if(!$this->order->isNew()) throw new AppException('Invalid order');

            if($this->order->isInstantExecution()){
                if($this->order->getCurrencyPair()->isExternalLiquidityEnabled()){
                    $instantLimitPrice = $this->liquidityManager->findInstantLimitPrice($this->order);
                    // Sprawdzanie balancu czy na koncie external liquidity np. bitbay jest taka ilość którą on chce kupić/sprzedać
                    $this->liquidityManager->verifyExternalMarketBalance($this->order, $instantLimitPrice);
                }else{
                    $instantLimitPrice = $this->newOrderManager->findInstantLimitPrice($this->order);
                }
                $this->newOrderManager->verifyPlaceOrderInstant($this->order, $instantLimitPrice);
            }else{
                $this->newOrderManager->verifyPlaceOrder($this->order);
            }
        }catch (\Exception $exception){
            // reject
            dump($exception->getMessage());
            $this->newOrderManager->reject($this->order);

            return false;
        }

        if($this->order->getCurrencyPair()->isExternalLiquidityEnabled() && !$this->order->isExternalLiquidityOrder()){
            // create temporary orders from liquidity orders
            try{
                // Sprawdzenie czy balance na external liquidity jest wystarczający aby przyjąć transakcję - jeśli nie to Reject
                $instantLimitPrice = $this->liquidityManager->findInstantLimitPrice($this->order);
                $this->liquidityManager->verifyExternalMarketBalance($this->order, $instantLimitPrice);
                // TODO - LATER sprawdzanie REAL balance na external liquidity? - czy to rzeczywiście jest potrzebne?

                /**
                 *
                // TODO Sprawdzenie ExternalOrders - czy istnieje płynność - załadowanie sztucznej płynności i na jej podstawie.
                // Jeśli istnieje płynność -> utworzyć normalne orders dla konta liquidity, takie,
                // które pozwola na wypełnienie tego zleceni
                // TODO brać pod uwagę obecnie istniejące REAL Orders a nie tylko external
                 *
                 */

                if($this->order->isInstantExecution()){
//                    throw new AppException('Temporary disabled');
                    $externalLiquidity = $this->liquidityManager->findExternalLiquidity($this->order, $instantLimitPrice);
                }else{
                    $externalLiquidity = $this->liquidityManager->findExternalLiquidity($this->order, $this->order->getLimitPrice());
                }

                if($externalLiquidity && is_array($externalLiquidity)){
                    $amountLeft = $this->order->getAmount();

                    $partialExternalOrders = $this->liquidityManager->makePartialOrders($externalLiquidity, $amountLeft);
                    // TODO Po zakończeniu procesu tradingu - usunąć utworzone $partialExternalOrders, które nie zostały wypełnione.
                }else{
                    //throw new AppException('Liquidity not found');
                }

                $externalLiquidity = null;
                unset($externalLiquidity);
            }catch (\Exception $exception){
                dump($exception->getMessage());
                $this->newOrderManager->reject($this->order);

                return false;
            }
        }elseif($this->order->getCurrencyPair()->isExternalLiquidityEnabled() && $this->order->isExternalLiquidityOrder()){
            // TODO przeniesione niżej
        }

        $liquidityOrders = null;
        try{
            if($this->order->isInstantExecution()){
                if($instantLimitPrice){
                    $liquidityOrders = $this->findLiquidity($this->order, $instantLimitPrice);
                }else{
                    throw new AppException('No liquidity for instant order');
                }
            }else{
                $liquidityOrders = $this->findLiquidity($this->order, $this->order->getLimitPrice());
                if(!$liquidityOrders){
                    if($this->order->getCurrencyPair()->isExternalLiquidityEnabled() && $this->order->isExternalLiquidityOrder()){
                        throw new AppException('No liquidity for external order');
                    }
                }
            }
        }catch (\Exception $exception){
            // reject
            dump($exception->getMessage());
            $this->newOrderManager->reject($this->order);

            return false;
        }

        // set order to pending
        $this->order->setStatus(Order::STATUS_PENDING);
        $this->order = $this->orderRepository->save($this->order);

        // block wallets
        $this->order = $this->newOrderManager->blockOrderWallets($this->order, $instantLimitPrice);
        if($liquidityOrders){
            $amountToFill = $this->order->getAmount();

            /**
             *
            // TODO
            // 1. Zrobić przeliczenie jaka część ordera zostanie zrealizowana z External market a jaka z Internal
            // 2. Przed wypełnianiem zlecenia, zrealizować zlecenie na External Market aby mieć pewność że jest pokrycie
            // 3. Zapisać LiquidityTransaction dla External Market
            // 4. Realizować maczing orderów w internal market

            // TODO 2
            // 1. Alternatywą jest realizowanie External Market order w pętni poniżej, przy tworzeniue nowego Trade
            // ŁATWIEJSZA OPCJA

            // TODO 3
            // 1. Sumować amount każdego trejda i potem zrobić zbiorczke external transaction dla sumarycznego amount
             *
             */

            // block liquidity and create trades
            /** @var Order $tradingOrder */
            foreach($liquidityOrders as $tradingOrder){
                $freeAmount = $tradingOrder->freeAmount();

                if($freeAmount > 0 && $this->order->freeAmount() > 0){
                    $comp = bccomp($amountToFill, $freeAmount, PriceInterface::BC_SCALE);

                    //if($amountToFill === $freeAmount){
                    if($comp === 0){
                        $this->fillOrder($tradingOrder, $freeAmount);
                        $this->fillOrder($this->order, $freeAmount);

                        /** @var Trade $trade */
                        $trade = $this->tradeManager->makeTrade([$tradingOrder, $this->order], $freeAmount, $tradingOrder->getLimitPrice());
                        $this->processTrade($trade);

                        ## TODO it's external LIQ
                        if($tradingOrder->isExternalLiquidityOrder() || $this->order->isExternalLiquidityOrder()){
                            $this->liquidityManager->pushForLiquidityTransaction($trade);
                        }

                        return true;
                    //}elseif($amountToFill > $freeAmount){
                    }elseif($comp === 1){
                        $this->fillOrder($tradingOrder, $freeAmount);
                        $this->fillOrder($this->order, $freeAmount);

                        /** @var Trade $trade */
                        $trade = $this->tradeManager->makeTrade([$tradingOrder, $this->order], $freeAmount, $tradingOrder->getLimitPrice());
                        $this->processTrade($trade);

                        ## TODO it's external LIQ
                        if($tradingOrder->isExternalLiquidityOrder() || $this->order->isExternalLiquidityOrder()){
                            $this->liquidityManager->pushForLiquidityTransaction($trade);
                        }

                        //$amountToFill -= $freeAmount;
                        $amountToFill = bcsub($amountToFill, $freeAmount, PriceInterface::BC_SCALE);
                    //}elseif($amountToFill < $freeAmount && $amountToFill > 0){
                    }elseif($comp === -1 && $amountToFill > 0){
                        $this->fillOrder($tradingOrder, $amountToFill);
                        $this->fillOrder($this->order, $amountToFill);

                        /** @var Trade $trade */
                        $trade = $this->tradeManager->makeTrade([$tradingOrder, $this->order], $amountToFill, $tradingOrder->getLimitPrice());
                        $this->processTrade($trade);

                        ## TODO it's external LIQ
                        if($tradingOrder->isExternalLiquidityOrder() || $this->order->isExternalLiquidityOrder()){
                            $this->liquidityManager->pushForLiquidityTransaction($trade);
                        }

                        $amountToFill = 0;
                    }
                }
            }

            if($this->order->getCurrencyPair()->isExternalLiquidityEnabled() && $this->order->isExternalLiquidityOrder()){
                $compFA = bccomp($this->order->freeAmount(), 0, PriceInterface::BC_SCALE);
                if($compFA === 1){
                    $this->order->setStatus(Order::STATUS_REJECTED);
                    $this->order = $this->orderRepository->save($this->order);
                }
            }

//            if($amountToFill > 0){
//                // pending order - already created
//            }

            unset($liquidityOrders);

            return true;
        }else{
            // pending order when no liquidity found for asked price
            return true;
        }
    }

    /**
     * @return OrderRepository
     */
    public function getOrderRepository(): OrderRepository
    {
        return $this->orderRepository;
    }
}
