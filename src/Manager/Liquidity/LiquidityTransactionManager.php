<?php

namespace App\Manager\Liquidity;

use App\Entity\CheckoutOrder;
use App\Entity\CurrencyPair;
use App\Entity\Liquidity\LiquidityTransaction;
use App\Entity\OrderBook\Order;
use App\Repository\Liquidity\LiquidityTransactionRepository;

class LiquidityTransactionManager
{
    /** @var LiquidityTransactionRepository */
    private $liquidityTransactionRepository;

    /**
     * LiquidityTransactionManager constructor.
     * @param LiquidityTransactionRepository $liquidityTransactionRepository
     */
    public function __construct(LiquidityTransactionRepository $liquidityTransactionRepository)
    {
        $this->liquidityTransactionRepository = $liquidityTransactionRepository;
    }

    /**
     * @param Order $order
     * @param string $amount
     * @param string $price
     * @return LiquidityTransaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createInternal(Order $order, string $amount, string $price) : LiquidityTransaction
    {
        /** @var LiquidityTransaction $liquidityTransaction */
        $liquidityTransaction = new LiquidityTransaction( LiquidityTransaction::MARKET_TYPE_INTERNAL, $amount, $price);
        $liquidityTransaction->setOrder($order);

        if($order->isBid()){
            $liquidityTransaction->setType(LiquidityTransaction::TYPE_BUY);
        }elseif($order->isOffer()){
            $liquidityTransaction->setType(LiquidityTransaction::TYPE_SELL);
        }

        $liquidityTransaction->setRealized(true);

        return $this->liquidityTransactionRepository->save($liquidityTransaction);
    }

    /**
     * @param Order $order
     * @param string $amount
     * @param string $price
     * @return LiquidityTransaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createExternal(Order $order, string $amount, string $price) : LiquidityTransaction
    {
        /** @var LiquidityTransaction $liquidityTransaction */
        $liquidityTransaction = new LiquidityTransaction(LiquidityTransaction::MARKET_TYPE_EXTERNAL, $amount, $price);
        $liquidityTransaction->setOrder($order);

        if($order->isBid()){
            $liquidityTransaction->setType(LiquidityTransaction::TYPE_SELL);
        }elseif($order->isOffer()){
            $liquidityTransaction->setType(LiquidityTransaction::TYPE_BUY);
        }

        $liquidityTransaction = $this->liquidityTransactionRepository->save($liquidityTransaction);

        /** @var LiquidityTransaction $euroBalancerTransaction */
        $euroBalancerTransaction = $this->createExternalForEuro($order);

        return $liquidityTransaction;
    }

    /**
     * @param Order $order
     * @param string $amount
     * @return LiquidityTransaction|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createExternalForEuro(Order $order) : ?LiquidityTransaction
    {
        if($order->getCurrencyPair()->isEuroBalancer() && $order->getCurrencyPair()->getEuroBalancerAsk()){
            $euroPrice = $order->getCurrencyPair()->getEuroBalancerAsk();

            // always ask price for Checkout order
            $euroAmount = bcdiv($order->getTotalValue(), $euroPrice, 5);

            /** @var LiquidityTransaction $liquidityTransaction */
            $liquidityTransaction = new LiquidityTransaction(LiquidityTransaction::MARKET_TYPE_EXTERNAL, $euroAmount, $euroPrice);

            $liquidityTransaction->setEuroBalancerOrderbook('walutomat');
            $liquidityTransaction->setEuroBalancerOrderbookSymbol('EUR_PLN');

            if($order->isBid()){
                $liquidityTransaction->setType(LiquidityTransaction::TYPE_SELL);
            }elseif($order->isOffer()){
                $liquidityTransaction->setType(LiquidityTransaction::TYPE_BUY);
            }

            $liquidityTransaction = $this->liquidityTransactionRepository->save($liquidityTransaction);

            return $liquidityTransaction;
        }

        return null;
    }


    /**
     * @param CheckoutOrder $checkoutOrder
     * @return LiquidityTransaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createExternalForCheckout(CheckoutOrder $checkoutOrder) : LiquidityTransaction
    {
        $amount = $checkoutOrder->getAmount();
        $price = $checkoutOrder->getInitiationPrice();

        /** @var LiquidityTransaction $liquidityTransaction */
        $liquidityTransaction = new LiquidityTransaction(LiquidityTransaction::MARKET_TYPE_EXTERNAL, $amount, $price);
        $liquidityTransaction->setCheckoutOrder($checkoutOrder);

        // ONLY BUY is possible HERE - for checkout order
        $liquidityTransaction->setType(LiquidityTransaction::TYPE_BUY);

        $liquidityTransaction = $this->liquidityTransactionRepository->save($liquidityTransaction);

        /** @var LiquidityTransaction $tetherBalancerTransaction */
        $tetherBalancerTransaction = $this->createExternalForTetherBalancer($checkoutOrder);

        /** @var LiquidityTransaction $euroBalancerTransaction */
        $euroBalancerTransaction = $this->createExternalForEuroBalancer($checkoutOrder);

        return $liquidityTransaction;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return LiquidityTransaction|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createExternalForTetherBalancer(CheckoutOrder $checkoutOrder) : ?LiquidityTransaction
    {
        if($checkoutOrder->getCurrencyPair()->isTetherBalancer() && $checkoutOrder->getCurrencyPair()->getTetherBalancerAsk()){
            $tetherPrice = $checkoutOrder->getCurrencyPair()->getTetherBalancerAsk();

            // always ask price for Checkout order
            $tetherAmount = bcdiv($checkoutOrder->getTotalPrice(), $tetherPrice, 5);

            /** @var LiquidityTransaction $liquidityTransaction */
            $liquidityTransaction = new LiquidityTransaction(LiquidityTransaction::MARKET_TYPE_EXTERNAL, $tetherAmount, $tetherPrice);

            $liquidityTransaction->setTetherBalancerOrderbook('bitbay');
            $liquidityTransaction->setTetherBalancerOrderbookSymbol('USDT-PLN');

            // ONLY BUY is possible HERE - for checkout order
            $liquidityTransaction->setType(LiquidityTransaction::TYPE_BUY);

            $liquidityTransaction = $this->liquidityTransactionRepository->save($liquidityTransaction);

            return $liquidityTransaction;
        }

        return null;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return LiquidityTransaction|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createExternalForEuroBalancer(CheckoutOrder $checkoutOrder) : ?LiquidityTransaction
    {
        if($checkoutOrder->getCurrencyPair()->isEuroBalancer() && $checkoutOrder->getCurrencyPair()->getEuroBalancerAsk()){
            $euroPrice = $checkoutOrder->getCurrencyPair()->getEuroBalancerAsk();

            // always ask price for Checkout order
            $euroAmount = bcdiv($checkoutOrder->getTotalPrice(), $euroPrice, 5);

            /** @var LiquidityTransaction $liquidityTransaction */
            $liquidityTransaction = new LiquidityTransaction(LiquidityTransaction::MARKET_TYPE_EXTERNAL, $euroAmount, $euroPrice);

            $liquidityTransaction->setEuroBalancerOrderbook('walutomat');
            $liquidityTransaction->setEuroBalancerOrderbookSymbol('EUR_PLN');

            // ONLY BUY is possible HERE - for checkout order
            $liquidityTransaction->setType(LiquidityTransaction::TYPE_BUY);

            $liquidityTransaction = $this->liquidityTransactionRepository->save($liquidityTransaction);

            return $liquidityTransaction;
        }

        return null;
    }

    public function makeExternalOrder(Order $order)
    {
        // TODO - przenieść ExternalMarketOrderProcessorCommand tutaj
    }
}
