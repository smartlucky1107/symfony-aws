<?php

namespace App\Manager\Liquidity;

use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Entity\Liquidity\ExternalMarketWallet;
use App\Entity\Liquidity\ExternalOrder;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\RedisSubscribeManager;
use App\Model\PriceInterface;
use App\Model\SystemUserInterface;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\UserRepository;

class LiquidityManager
{
    /** @var ExternalMarketWalletManager */
    private $externalMarketWalletManager;

    /** @var LiquidityTransactionManager */
    private $liquidityTransactionManager;

    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var RedisSubscribeManager  */
    private $redisSubscribeManager;

    /**
     * LiquidityManager constructor.
     * @param ExternalMarketWalletManager $externalMarketWalletManager
     * @param LiquidityTransactionManager $liquidityTransactionManager
     * @param ExternalOrderRepository $externalOrderRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     * @param RedisSubscribeManager $redisSubscribeManager
     */
    public function __construct(ExternalMarketWalletManager $externalMarketWalletManager, LiquidityTransactionManager $liquidityTransactionManager, ExternalOrderRepository $externalOrderRepository, UserRepository $userRepository, OrderRepository $orderRepository, RedisSubscribeManager $redisSubscribeManager)
    {
        $this->externalMarketWalletManager = $externalMarketWalletManager;
        $this->liquidityTransactionManager = $liquidityTransactionManager;
        $this->externalOrderRepository = $externalOrderRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
        $this->redisSubscribeManager = $redisSubscribeManager;
    }

    /**
     * @param Order $order
     * @return Currency
     * @throws AppException
     */
    private function extractCurrency(Order $order) : Currency
    {
        if($order->isBid()){
            $currency = $order->getCurrencyPair()->getQuotedCurrency();
        }elseif($order->isOffer()){
            $currency = $order->getCurrencyPair()->getBaseCurrency();
        }else{
            throw new AppException('Wrong order type');
        }
        if(!($currency instanceof Currency)) throw new AppException('Currency not found');

        return $currency;
    }

    /**
     * @param Order $order
     * @return ExternalMarketWallet
     * @throws AppException
     */
    private function extractMarketWallet(Order $order) : ExternalMarketWallet
    {
        /** @var Currency $currency */
        $currency = $this->extractCurrency($order);

        if($order->getCurrencyPair()->isBitbayLiquidity()){
            $externalMarketId = ExternalMarketWallet::EXTERNAL_MARKET_BITBAY;
        }elseif($order->getCurrencyPair()->isBinanceLiquidity()){
            $externalMarketId = ExternalMarketWallet::EXTERNAL_MARKET_BINANCE;
        }elseif($order->getCurrencyPair()->isKrakenLiquidity()){
            $externalMarketId = ExternalMarketWallet::EXTERNAL_MARKET_KRAKEN;
        }elseif($order->getCurrencyPair()->isWalutomatLiquidity()){
            $externalMarketId = ExternalMarketWallet::EXTERNAL_MARKET_WALUTOMAT;
        }else{
            throw new AppException('Market balance error');
        }

        /** @var ExternalMarketWallet $externalMarketWallet */
        $externalMarketWallet = $this->externalMarketWalletManager->loadOrException($currency, $externalMarketId);

        return $externalMarketWallet;
    }

    /**
     * @param Order $order
     * @param string|null $instantLimitPrice
     * @throws AppException
     * @throws \Exception
     */
    public function verifyExternalMarketBalance(Order $order, string $instantLimitPrice = null) : void
    {
//        /** @var ExternalMarketWallet $externalMarketWallet */
//        $externalMarketWallet = $this->extractMarketWallet($order);
//
//        if($order->isBid()){
//            if($instantLimitPrice){
//                $total = $order->quotedCurrencyTotalCalculate($instantLimitPrice);
//            }else{
//                $total = $order->quotedCurrencyTotal();
//            }
//
//            if(!$externalMarketWallet->isTransferAllowed($total)){
//                throw new AppException('error.wallet.insufficient_funds');
//            }
//        }elseif($order->isOffer()){
//            if(!$externalMarketWallet->isTransferAllowed($order->baseCurrencyTotal())){
//                throw new AppException('error.wallet.insufficient_funds');
//            }
//        }
    }

    /**
     * @param Order $order
     * @param float $limitPrice
     * @return array|null
     */
    public function findExternalLiquidity(Order $order, float $limitPrice) : ?array
    {
        if($order->isBid()){
            return $this->externalOrderRepository->findLiquidity($order->getCurrencyPair(), ExternalOrder::TYPE_SELL, $limitPrice);
        }elseif($order->isOffer()){
            return $this->externalOrderRepository->findLiquidity($order->getCurrencyPair(), ExternalOrder::TYPE_BUY, $limitPrice);
        }

        return null;
    }

    /**
     * @param Order $order
     * @return string|null
     */
    public function findInstantLimitPrice(Order $order) : ?string
    {
        if($order->isBid()){
            return $this->externalOrderRepository->findLiquidityLimitPrice($order->getCurrencyPair(), ExternalOrder::TYPE_SELL, $order->getAmount());
        }elseif($order->isOffer()){
            return $this->externalOrderRepository->findLiquidityLimitPrice($order->getCurrencyPair(), ExternalOrder::TYPE_BUY, $order->getAmount());
        }

        return null;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return User|null
     * @throws AppException
     */
    private function resolveLiquidityUser(CurrencyPair $currencyPair) : ?User
    {
        if ($currencyPair->isBinanceLiquidity()) {
            $user = $this->userRepository->findBinanceLiquidityUser();
        } elseif ($currencyPair->isBitbayLiquidity()) {
            $user = $this->userRepository->findBitbayLiquidityUser();
        } elseif ($currencyPair->isKrakenLiquidity()) {
            $user = $this->userRepository->findKrakenLiquidityUser();
        } elseif ($currencyPair->isWalutomatLiquidity()) {
            $user = $this->userRepository->find(SystemUserInterface::WALUTOMAT_LIQ_USER);
        } else {
            $user = null;
        }

        if(!($user instanceof User)) throw new AppException('User not found');

        return $user;
    }

    /**
     * @param ExternalOrder $externalOrder
     * @param string $amount
     * @return Order|null
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createOrderFromExternalOrder(ExternalOrder $externalOrder, string $amount) : ?Order
    {
        /** @var CurrencyPair $currencyPair */
        $currencyPair = $externalOrder->getCurrencyPair();

        /** @var User $user */
        $user = $this->resolveLiquidityUser($currencyPair);

        // TODO refactor z orderTransformer
        $userWallets = $user->getWallets();
        if(!$userWallets) throw new AppException('User wallets not found');

        $baseCurrencyWallet = null;
        $quotedCurrencyWallet = null;

        /** @var Wallet $userWallet */
        foreach($userWallets as $userWallet){
            if($userWallet->getCurrency()->getId() === $currencyPair->getBaseCurrency()->getId()){
                $baseCurrencyWallet = $userWallet;
            }
            if($userWallet->getCurrency()->getId() === $currencyPair->getQuotedCurrency()->getId()){
                $quotedCurrencyWallet = $userWallet;
            }
        }
        // -> refactor z orderTransformer

        if($baseCurrencyWallet instanceof Wallet && $quotedCurrencyWallet instanceof Wallet){
            $limitPrice = $externalOrder->getLiquidityRate();

//            if($currencyPair->isTetherBalancer()){
//                $limitPrice = bcmul($limitPrice, $currencyPair->getTetherBalancerAsk(), PriceInterface::BC_SCALE);
//            }elseif($currencyPair->isEuroBalancer()){
//                $limitPrice = bcmul($limitPrice, $currencyPair->getEuroBalancerAsk(), PriceInterface::BC_SCALE);
//            }

            if($externalOrder->getType() === ExternalOrder::TYPE_BUY){
                $type = Order::TYPE_BUY;
            }elseif($externalOrder->getType() === ExternalOrder::TYPE_SELL){
                $type = Order::TYPE_SELL;
            }else{
                throw new AppException('Order type not allowed');
            }

            /** @var Order $order */
            $order = new Order($user, $baseCurrencyWallet, $quotedCurrencyWallet, $currencyPair, $type, $amount, $limitPrice);
            $order->setStatus(Order::STATUS_PENDING);
            $order->setExternalLiquidityOrder(true);
            $order->setAmountBlocked(0);
            $order = $this->orderRepository->save($order);

            return $order;
        }else{
            throw new AppException('Base currency wallet and quoted currency wallet is required');
        }
    }

    /**
     * @param Order $originalOrder
     * @param string $amount
     * @return Order|null
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createOppositeOrderFromOrder(Order $originalOrder, string $amount) : ?Order
    {
        /** @var CurrencyPair $currencyPair */
        $currencyPair = $originalOrder->getCurrencyPair();

        /** @var User $user */
        $user = $this->resolveLiquidityUser($currencyPair);

        // TODO refactor z orderTransformer
        $userWallets = $user->getWallets();
        if(!$userWallets) throw new AppException('User wallets not found');

        $baseCurrencyWallet = null;
        $quotedCurrencyWallet = null;

        /** @var Wallet $userWallet */
        foreach($userWallets as $userWallet){
            if($userWallet->getCurrency()->getId() === $currencyPair->getBaseCurrency()->getId()){
                $baseCurrencyWallet = $userWallet;
            }
            if($userWallet->getCurrency()->getId() === $currencyPair->getQuotedCurrency()->getId()){
                $quotedCurrencyWallet = $userWallet;
            }
        }
        // -> refactor z orderTransformer

        if($baseCurrencyWallet instanceof Wallet && $quotedCurrencyWallet instanceof Wallet){
            $limitPrice = $originalOrder->getLimitPrice();

            if($originalOrder->getType() === Order::TYPE_BUY){
                $type = Order::TYPE_SELL;
            }elseif($originalOrder->getType() === Order::TYPE_SELL){
                $type = Order::TYPE_BUY;
            }else{
                throw new AppException('Order type not allowed');
            }

            /** @var Order $order */
            $order = new Order($user, $baseCurrencyWallet, $quotedCurrencyWallet, $currencyPair, $type, $amount, $limitPrice);
            $order->setStatus(Order::STATUS_NEW);
            $order->setExternalLiquidityOrder(true);
            $order->setAmountBlocked(0);
            $order = $this->orderRepository->save($order);

            return $order;
        }else{
            throw new AppException('Base currency wallet and quoted currency wallet is required');
        }
    }

    /**
     * @param ExternalOrder $externalOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function makePartialOppositeOrders(ExternalOrder $externalOrder)
    {
        $internalLiquidity = null;

        if($externalOrder->getType() === ExternalOrder::TYPE_BUY){
            $internalLiquidity = $this->orderRepository->findLiquidity($externalOrder->getCurrencyPair(), Order::TYPE_SELL, $externalOrder->getLiquidityRate());
        }elseif($externalOrder->getType() === ExternalOrder::TYPE_SELL){
            $internalLiquidity = $this->orderRepository->findLiquidity($externalOrder->getCurrencyPair(), Order::TYPE_BUY, $externalOrder->getLiquidityRate());
        }

        if($internalLiquidity){
            $amountAvailable = $externalOrder->getAmount();

            /** @var Order $internalLiquidityOrder */
            foreach($internalLiquidity as $internalLiquidityOrder){
                $comp0 = bccomp($amountAvailable, 0, PriceInterface::BC_SCALE);
                if($comp0 === 1){
                    $comp1 = bccomp($amountAvailable, $internalLiquidityOrder->freeAmount(),  PriceInterface::BC_SCALE);
                    if($comp1 === 1 || $comp1 === 0){
                        /** @var Order $oppositeOrder */
                        $oppositeOrder = $this->createOppositeOrderFromOrder($internalLiquidityOrder, $internalLiquidityOrder->freeAmount());
                        $this->redisSubscribeManager->pushOrder($oppositeOrder->getId());

                        $amountAvailable = bcsub($amountAvailable, $internalLiquidityOrder->freeAmount(), PriceInterface::BC_SCALE);

                        $oppositeOrder = null;
                        unset($oppositeOrder);
                    }else{
                        /** @var Order $oppositeOrder */
                        $oppositeOrder = $this->createOppositeOrderFromOrder($internalLiquidityOrder, $amountAvailable);

                        $this->redisSubscribeManager->pushOrder($oppositeOrder->getId());
                        $amountAvailable = bcsub($amountAvailable, $amountAvailable, PriceInterface::BC_SCALE);

                        $oppositeOrder = null;
                        unset($oppositeOrder);
                    }
                }else{
                    break;
                }
            }
        }

        $internalLiquidity = null;
        unset($internalLiquidity);
    }

    /**
     * @param array $externalLiquidity
     * @param string $amountLeft
     * @return array|null
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function makePartialOrders(array $externalLiquidity, string $amountLeft) : ?array
    {
        $partialOrders = [];

        /** @var ExternalOrder $externalOrder */
        foreach($externalLiquidity as $externalOrder){
            $comp0 = bccomp($amountLeft, 0, PriceInterface::BC_SCALE);
            if($comp0 === 1){
                $comp1 = bccomp($amountLeft, $externalOrder->getLiquidityAmount(),  PriceInterface::BC_SCALE);
                if($comp1 === 1 || $comp1 === 0){
                    $partialOrders[] = $this->createOrderFromExternalOrder($externalOrder, $externalOrder->getLiquidityAmount());
                    $amountLeft = bcsub($amountLeft, $externalOrder->getLiquidityAmount(), PriceInterface::BC_SCALE);
                }else{
                    $partialOrders[] = $this->createOrderFromExternalOrder($externalOrder, $amountLeft);
                    $amountLeft = bcsub($amountLeft, $amountLeft, PriceInterface::BC_SCALE);
                }
            }else{
                break;
            }
        }

        return $partialOrders;
    }

    /**
     * @param Trade $trade
     */
    public function pushForLiquidityTransaction(Trade $trade) : void
    {
        try{
            if($trade->getOrderBuy()->isExternalLiquidityOrder() && $trade->getOrderSell()->isExternalLiquidityOrder()){
                throw new AppException('System does not save LIQ&LIQ orders');
            }

            if($trade->getOrderBuy()->isExternalLiquidityOrder()){
                $this->liquidityTransactionManager->createInternal($trade->getOrderBuy(), $trade->getAmount(), $trade->getPrice());
                $this->liquidityTransactionManager->createExternal($trade->getOrderBuy(), $trade->getAmount(), $trade->getPrice());
            }

            if($trade->getOrderSell()->isExternalLiquidityOrder()){
                $this->liquidityTransactionManager->createInternal($trade->getOrderSell(), $trade->getAmount(), $trade->getPrice());
                $this->liquidityTransactionManager->createExternal($trade->getOrderSell(), $trade->getAmount(), $trade->getPrice());
            }
        }catch (\Exception $exception){
            dump($exception->getMessage());
        }
    }

    public function verifyRealExternalMarketBalance(Order $order) : void
    {

    }

    public function verifyLiquidity(Order $order) : void
    {

    }

    public function isLiquidityAvailable() : bool
    {

    }

    public function isExternalLiquidityAvailable() : bool
    {

    }
}
