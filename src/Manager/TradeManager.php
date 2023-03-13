<?php

namespace App\Manager;

use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Event\WalletBalance\WalletBalanceBeforeTradeEvent;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Repository\OrderBook\TradeRepository;
use App\Resolver\FeeResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TradeManager
{
    /** @var TradeRepository */
    private $tradeRepository;

    /** @var FeeResolver */
    private $feeResolver;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * TradeManager constructor.
     * @param TradeRepository $tradeRepository
     * @param FeeResolver $feeResolver
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(TradeRepository $tradeRepository, FeeResolver $feeResolver, EventDispatcherInterface $eventDispatcher)
    {
        $this->tradeRepository = $tradeRepository;
        $this->feeResolver = $feeResolver;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param array $orders
     * @param string $amount
     * @param string $price
     * @return Trade
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function makeTrade(array $orders, string $amount, string $price) : Trade
    {
        if(!($orders[0] instanceof Order && $orders[1] instanceof Order)) throw new AppException('error.trade.orders_not_valid');

        if($orders[0]->isOffer() && $orders[1]->isBid()){
            $trade = new Trade($orders[0], $orders[1], $amount, $price);
            // $orders[0] MAKER | $orders[1] TAKER
            $trade->setFeeOffer($this->feeResolver->tradeMakerFee(bcmul($amount, $price, PriceInterface::BC_SCALE), $orders[0]->getUser(), $orders[0]->isCryptoCrypto()));
            $trade->setFeeBid($this->feeResolver->tradeTakerFee($amount, $orders[1]->getUser(), $orders[1]->isCryptoCrypto()));
        }elseif($orders[0]->isBid() && $orders[1]->isOffer()){
            $trade = new Trade($orders[1], $orders[0], $amount, $price);
            // $orders[1] TAKER | $orders[0] MAKER
            $trade->setFeeOffer($this->feeResolver->tradeTakerFee(bcmul($amount, $price, PriceInterface::BC_SCALE), $orders[1]->getUser(), $orders[1]->isCryptoCrypto()));
            $trade->setFeeBid($this->feeResolver->tradeMakerFee($amount, $orders[0]->getUser(), $orders[0]->isCryptoCrypto()));
        }else{
            throw new AppException('error.trade.bid_offer_required');
        }

        $trade->setSignature($trade->generateSignature());

        $trade = $this->tradeRepository->save($trade);

        // save wallet balance
        $this->eventDispatcher->dispatch(WalletBalanceBeforeTradeEvent::NAME, new WalletBalanceBeforeTradeEvent($orders[0]->getBaseCurrencyWallet(), $orders[0]->getBaseCurrencyWallet()->getAmount(), $trade));
        $this->eventDispatcher->dispatch(WalletBalanceBeforeTradeEvent::NAME, new WalletBalanceBeforeTradeEvent($orders[0]->getQuotedCurrencyWallet(), $orders[0]->getQuotedCurrencyWallet()->getAmount(), $trade));

        $this->eventDispatcher->dispatch(WalletBalanceBeforeTradeEvent::NAME, new WalletBalanceBeforeTradeEvent($orders[1]->getBaseCurrencyWallet(), $orders[1]->getBaseCurrencyWallet()->getAmount(), $trade));
        $this->eventDispatcher->dispatch(WalletBalanceBeforeTradeEvent::NAME, new WalletBalanceBeforeTradeEvent($orders[1]->getQuotedCurrencyWallet(), $orders[1]->getQuotedCurrencyWallet()->getAmount(), $trade));

        return $trade;
    }
}
