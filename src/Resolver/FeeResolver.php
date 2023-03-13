<?php

namespace App\Resolver;

use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\TradeFeeLevel;
use App\Entity\User;
use App\Model\PriceInterface;
use App\Repository\TradeFeeLevelRepository;

class FeeResolver
{
    /** @var TradeFeeLevelRepository */
    private $tradeFeeLevelRepository;

    /**
     * TradeFeeResolver constructor.
     * @param TradeFeeLevelRepository $tradeFeeLevelRepository
     */
    public function __construct(TradeFeeLevelRepository $tradeFeeLevelRepository)
    {
        $this->tradeFeeLevelRepository = $tradeFeeLevelRepository;
    }

//    /**
//     * Calculate trade fee for bid wallet
//     *
//     * @param Trade $trade
//     * @return string|null
//     */
//    public function tradeFeeBid(Trade $trade)
//    {
//        $amount = $trade->getAmount();
//        $takerFee = $this->takerFee($trade->getOrderBuy()->getUser());
//
//        //return $amount * $takerFee / 100;
//
//        $a = bcmul($amount, $takerFee, PriceInterface::BC_SCALE);
//        $b = bcdiv($a, '100', PriceInterface::BC_SCALE);
//
//        return $b;
//    }

//    /**
//     * Calculate trade fee for offer wallet
//     *
//     * @param Trade $trade
//     * @return string|null
//     */
//    public function tradeFeeOffer(Trade $trade)
//    {
//        $amount = $trade->getAmount();
//        $makerFee = $this->makerFee($trade->getOrderSell()->getUser());
//
//        //return $amount * $makerFee / 100;
//
//        $a = bcmul($amount, $makerFee, PriceInterface::BC_SCALE);
//        $b = bcdiv($a, '100', PriceInterface::BC_SCALE);
//
//        return $b;
//    }

    /**
     * @param $amount
     * @param User $user
     * @param bool $isCryptoCrypto
     * @return string|null
     */
    public function tradeTakerFee($amount, User $user, bool $isCryptoCrypto = false)
    {
        $takerFee = $this->takerFee($user, $isCryptoCrypto);

        //return $amount * $takerFee / 100;

        $a = bcmul($amount, $takerFee, PriceInterface::BC_SCALE);
        $b = bcdiv($a, '100', PriceInterface::BC_SCALE);

        return $b;
    }

    /**
     * @param $amount
     * @param User $user
     * @param bool $isCryptoCrypto
     * @return string|null
     */
    public function tradeMakerFee($amount, User $user, bool $isCryptoCrypto = false)
    {
        $makerFee = $this->makerFee($user, $isCryptoCrypto);

        //return $amount * $makerFee / 100;

        $a = bcmul($amount, $makerFee, PriceInterface::BC_SCALE);
        $b = bcdiv($a, '100', PriceInterface::BC_SCALE);

        return $b;
    }

    /**
     * Get user's BID fee, based on the trading volume
     *
     * @param User $user
     * @param bool $isCryptoCrypto
     * @return float
     */
    public function takerFee(User $user, bool $isCryptoCrypto = false) : float
    {
        /** @var TradeFeeLevel $tradeFeeLevel */
        $tradeFeeLevel = $this->tradeFeeLevelRepository->findLevel(0);
        if($tradeFeeLevel instanceof TradeFeeLevel){
            if($isCryptoCrypto){
                return $tradeFeeLevel->getTakerFeeCrypto();
            }else{
                return $tradeFeeLevel->getTakerFee();
            }
        }

        return 0;
    }

    /**
     * Get user's OFFER fee, based on the trading volume
     *
     * @param User $user
     * @param bool $isCryptoCrypto
     * @return float
     */
    public function makerFee(User $user, bool $isCryptoCrypto = false) : float
    {
        /** @var TradeFeeLevel $tradeFeeLevel */
        $tradeFeeLevel = $this->tradeFeeLevelRepository->findLevel(0);
        if($tradeFeeLevel instanceof TradeFeeLevel){
            if($isCryptoCrypto){
                return $tradeFeeLevel->getMakerFeeCrypto();
            }else{
                return $tradeFeeLevel->getMakerFee();
            }
        }

        return 0;
    }
}
