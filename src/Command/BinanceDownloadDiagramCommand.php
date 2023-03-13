<?php

namespace App\Command;

use App\Document\OHLC;
use App\Entity\CurrencyPair;
use App\Exception\AppException;
use App\Manager\Liquidity\BinanceLiquidityManager;
use App\Repository\CurrencyPairRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Manager\Charting\OHLCManager;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class BinanceDownloadDiagramCommand extends Command
{
    protected static $defaultName = 'app:binance:download-diagram';

    /** @var DocumentManager  */
    private $dm;

    /** @var OHLCManager */
    private $OHLCManager;

    /** @var BinanceLiquidityManager */
    private $binanceLiquidityManager;

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /**
     * BinanceDownloadDiagramCommand constructor.
     * @param DocumentManager $dm
     * @param OHLCManager $OHLCManager
     * @param BinanceLiquidityManager $binanceLiquidityManager
     * @param CurrencyPairRepository $currencyPairRepository
     */
    public function __construct(DocumentManager $dm, OHLCManager $OHLCManager, BinanceLiquidityManager $binanceLiquidityManager, CurrencyPairRepository $currencyPairRepository)
    {
        $this->dm = $dm;
        $this->OHLCManager = $OHLCManager;
        $this->binanceLiquidityManager = $binanceLiquidityManager;
        $this->currencyPairRepository = $currencyPairRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('pairShortName', InputArgument::REQUIRED, '')
            ->addArgument('periodKey', InputArgument::REQUIRED, '')
            ->addArgument('from', InputArgument::REQUIRED, '')
            ->addArgument('to', InputArgument::REQUIRED, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairShortName = strtoupper($input->getArgument('pairShortName'));

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findByShortName($pairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found');

        $periodKey = strtoupper($input->getArgument('periodKey'));

        if($periodKey === 'D') {
            $interval = '1d';
        }elseif($periodKey === '60'){
            $interval = '1h';
        }else{
            throw new AppException('Period key not allowed');
        }

        $from = new \DateTime($input->getArgument('from'));
        $to = new \DateTime($input->getArgument('to'));

        $fromTimestamp = $from->getTimestamp() * 1000;
        $toTimestamp = $to->getTimestamp() * 1000;

        $response = $this->binanceLiquidityManager->getKlines(strtoupper($currencyPair->getExternalOrderbookSymbol()), $interval, $fromTimestamp, $toTimestamp);
        dump($response);
        if(is_array($response) && count($response) > 0){
            foreach($response as $item){
                $dateTime = new \DateTime();
                $dateTime->setTimestamp(substr($item[0],0,10));
                $time = $this->OHLCManager->periodToTime($periodKey, $dateTime);

                if(null === $ohlc = $this->OHLCManager->loadByTimePeriod($pairShortName,$time,$periodKey)){
                    $ohlc = new OHLC(
                        $pairShortName,
                        $periodKey,
                        $time,
                        0,
                        0
                    );
                }

                $ohlc->setOpen($item[1]);
                $ohlc->setHigh($item[2]);
                $ohlc->setLow($item[3]);
                $ohlc->setClose($item[4]);
                $ohlc->setVolume($item[5]);

                $this->dm->persist($ohlc);
                $this->dm->flush();
            }
        }
    }
}


//        $interval = '1m';
//
//        $from = new \DateTime($input->getArgument('from'));
//        $to = new \DateTime($input->getArgument('to'));
//
//        $fromTimestamp = $from->getTimestamp() * 1000;
//        $toTimestamp = $to->getTimestamp() * 1000;
//
//        $response = $this->binanceLiquidityManager->getKlines(strtoupper($pairShortName), $interval, $fromTimestamp, $toTimestamp);
//        if(is_array($response) && count($response) > 0){
//            $item = $response[0];
//
//            $high = $item[2];
//            $low = $item[3];
//
//            dump($high);
//            dump($low);
//
//            dump($item);
//        }
////        dump($response);
//
//        exit;
