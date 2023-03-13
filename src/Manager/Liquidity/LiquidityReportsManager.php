<?php

namespace App\Manager\Liquidity;

use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Repository\OrderBook\TradeRepository;

class LiquidityReportsManager
{
    /** @var int */
    private $liquidityWalletPln;

    /** @var int */
    private $liquidityWalletBtc;

    /** @var int */
    private $liquidityWalletEth;

    /** @var int */
    private $cashWalletPln;

    /** @var int */
    private $cashWalletBtc;

    /** @var int */
    private $cashWalletEth;

    /** @var TradeRepository */
    private $tradeRepository;

    /**
     * LiquidityReportsManager constructor.
     * @param int $cashWalletPln
     * @param int $cashWalletBtc
     * @param int $cashWalletEth
     * @param TradeRepository $tradeRepository
     */
    public function __construct(int $cashWalletPln, int $cashWalletBtc, int $cashWalletEth, TradeRepository $tradeRepository)
    {
        $this->cashWalletPln = $cashWalletPln;
        $this->cashWalletBtc = $cashWalletBtc;
        $this->cashWalletEth = $cashWalletEth;
        $this->tradeRepository = $tradeRepository;
    }

    /**
     * @param string|null $value
     * @return string
     */
    private function round(string $value = null) : string
    {
        if(is_null($value)) $value = 0;

        return bcadd($value, 0, 8);
    }

    /**
     * @param User $user
     */
    public function initLiquidityUser(User $user){
        $wallets = $user->getWallets();

        /** @var Wallet $wallet */
        foreach($wallets as $wallet){
            if($wallet->isEthWallet()){
                $this->liquidityWalletEth = $wallet->getId();
            }elseif($wallet->isBtcWallet()){
                $this->liquidityWalletBtc = $wallet->getId();
            }
        }
    }

    /**
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function getReports(\DateTime $from = null, \DateTime $to = null) : array
    {
        $report = ['global' => [], 'dates' => []];

        if(!($from instanceof \DateTime)) $from = new \DateTime('now');;
        $from->setTime(0,0,0);

        if(!($to instanceof \DateTime)) $to = new \DateTime('now');
        $to->setTime(0,0,0)->modify('+1 day');

        if($to <= $from) throw new AppException('Dates not allowed');

        while($from < $to){
            $dateStart = clone $to;
            $dateEnd = clone $dateStart;
            $dateEnd->modify('+1 day');

            $report['dates'][$dateStart->format('Y-m-d')] = [
                'soldBTC' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT, $this->liquidityWalletBtc, null, $dateStart, $dateEnd)),
                'soldBTCValue' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT_PRICE, $this->liquidityWalletBtc, null, $dateStart, $dateEnd)),
                'soldETH' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT, $this->liquidityWalletEth, null, $dateStart, $dateEnd)),
                'soldETHValue' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT_PRICE, $this->liquidityWalletEth, null, $dateStart, $dateEnd)),
            ];

            $to->modify('-1 day');
        }

        $report['global'] = [
            'soldBTC' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT, $this->liquidityWalletBtc)),
            'soldBTCValue' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT_PRICE, $this->liquidityWalletBtc)),
            'soldETH' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT, $this->liquidityWalletEth)),
            'soldETHValue' => $this->round($this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT_PRICE, $this->liquidityWalletEth)),
        ];

        return $report;

//        $report = [
//            'todayForCash' => [
//                'soldBTC' => $this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT, $this->liquidityWalletBtc, $this->cashWalletPln, $todayStart, $todayEnd),
//                'soldBTCValue' => $this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT_PRICE, $this->liquidityWalletBtc, $this->cashWalletPln, $todayStart, $todayEnd),
//            ],
//            'globalForCash' => [
//                'soldBTC' => $this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT, $this->liquidityWalletBtc, $this->cashWalletPln),
//                'soldBTCValue' => $this->tradeRepository->getSoldByWallet(TradeRepository::RESULT_MODE_SUM_AMOUNT_PRICE, $this->liquidityWalletBtc, $this->cashWalletPln),
//            ]
//        ];
    }
}
