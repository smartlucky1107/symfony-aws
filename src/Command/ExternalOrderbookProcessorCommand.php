<?php

namespace App\Command;

use App\Entity\CurrencyPair;
use App\Exception\AppException;
use App\Manager\Liquidity\ExternalOrderManager;
use App\Manager\Liquidity\Orderbook\BinanceOrderbookManager;
use App\Manager\Liquidity\Orderbook\BitbayOrderbookManager;
use App\Manager\Liquidity\Orderbook\KrakenOrderbookManager;
use App\Manager\Liquidity\Orderbook\WalutomatOrderbookManager;
use App\Repository\CurrencyPairRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExternalOrderbookProcessorCommand extends Command
{
    protected static $defaultName = 'app:external-orderbook:processor';

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var ExternalOrderManager */
    private $externalOrderManager;

    /** @var BitbayOrderbookManager */
    private $bitbayOrderbookManager;

    /** @var BinanceOrderbookManager */
    private $binanceOrderbookManager;

    /** @var KrakenOrderbookManager */
    private $krakenOrderbookManager;

    /** @var WalutomatOrderbookManager */
    private $walutomatOrderbookManager;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * ExternalOrderbookProcessorCommand constructor.
     * @param CurrencyPairRepository $currencyPairRepository
     * @param ExternalOrderManager $externalOrderManager
     * @param BitbayOrderbookManager $bitbayOrderbookManager
     * @param BinanceOrderbookManager $binanceOrderbookManager
     * @param KrakenOrderbookManager $krakenOrderbookManager
     * @param WalutomatOrderbookManager $walutomatOrderbookManager
     * @param EntityManagerInterface $em
     */
    public function __construct(CurrencyPairRepository $currencyPairRepository, ExternalOrderManager $externalOrderManager, BitbayOrderbookManager $bitbayOrderbookManager, BinanceOrderbookManager $binanceOrderbookManager, KrakenOrderbookManager $krakenOrderbookManager, WalutomatOrderbookManager $walutomatOrderbookManager, EntityManagerInterface $em)
    {
        $this->currencyPairRepository = $currencyPairRepository;
        $this->externalOrderManager = $externalOrderManager;
        $this->bitbayOrderbookManager = $bitbayOrderbookManager;
        $this->binanceOrderbookManager = $binanceOrderbookManager;
        $this->krakenOrderbookManager = $krakenOrderbookManager;
        $this->walutomatOrderbookManager = $walutomatOrderbookManager;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('pairShortName', InputArgument::REQUIRED, '')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws AppException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairShortName = $input->getArgument('pairShortName');

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findByShortName($pairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found');
        if(!$currencyPair->isExternalLiquidityEnabled()){
            throw new AppException('External orderbook is not configured for the currency pair');
        }

        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);

        try{
            $this->externalOrderManager->prepareOrderBookRemoval($currencyPair);

            if($currencyPair->isBitbayLiquidity()){
                $this->bitbayOrderbookManager->buildOrderBook($currencyPair);

                $this->externalOrderManager->clearRemovedOrderBook($currencyPair);
                $this->bitbayOrderbookManager->subscribe($currencyPair);
            }elseif($currencyPair->isBinanceLiquidity()){
                //$this->binanceOrderbookManager->buildOrderBook($currencyPair);

                $this->externalOrderManager->clearRemovedOrderBook($currencyPair);
                $this->binanceOrderbookManager->subscribe($currencyPair);

            }elseif($currencyPair->isKrakenLiquidity()){
                while(1 == 1){
                    $this->krakenOrderbookManager->buildOrderBook($currencyPair);
                    $this->externalOrderManager->clearRemovedOrderBook($currencyPair);

                    sleep(5);
                }
            }elseif($currencyPair->isWalutomatLiquidity()){
                $this->walutomatOrderbookManager->buildOrderBook($currencyPair);
            }
        }catch (\Exception $exception){
            dump($exception->getMessage());
        }
    }
}
