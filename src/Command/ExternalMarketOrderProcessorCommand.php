<?php

namespace App\Command;

use App\Entity\CheckoutOrder;
use App\Entity\CurrencyPair;
use App\Entity\Liquidity\LiquidityTransaction;
use App\Entity\OrderBook\Order;
use App\Exception\AppException;
use App\Manager\Liquidity\BinanceLiquidityManager;
use App\Manager\Liquidity\BinanceOrderInterface;
use App\Manager\Liquidity\BitbayLiquidityManager;
use App\Manager\Liquidity\BitbayOrderInterface;
use App\Manager\Liquidity\KrakenLiquidityManager;
use App\Manager\Liquidity\LiquidityManagerInterface;
use App\Manager\Liquidity\WalutomatManager;
use App\Model\PriceInterface;
use App\Repository\Liquidity\LiquidityTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExternalMarketOrderProcessorCommand extends Command
{
    protected static $defaultName = 'app:external-market:order:processor';

    /** @var EntityManagerInterface */
    private $em;

    /** @var LiquidityTransactionRepository */
    private $liquidityTransactionRepository;

    /** @var BinanceLiquidityManager */
    private $binanceLiquidityManager;

    /** @var BitbayLiquidityManager */
    private $bitbayLiquidityManager;

    /** @var KrakenLiquidityManager */
    private $krakenLiquidityManager;

    /** @var LiquidityManagerInterface */
    private $selectedLiquidityManager;

    /** @var WalutomatManager */
    private $walutomatManager;

    /**
     * ExternalMarketOrderProcessorCommand constructor.
     * @param EntityManagerInterface $em
     * @param LiquidityTransactionRepository $liquidityTransactionRepository
     * @param BinanceLiquidityManager $binanceLiquidityManager
     * @param BitbayLiquidityManager $bitbayLiquidityManager
     * @param KrakenLiquidityManager $krakenLiquidityManager
     * @param WalutomatManager $walutomatManager
     */
    public function __construct(EntityManagerInterface $em, LiquidityTransactionRepository $liquidityTransactionRepository, BinanceLiquidityManager $binanceLiquidityManager, BitbayLiquidityManager $bitbayLiquidityManager, KrakenLiquidityManager $krakenLiquidityManager, WalutomatManager $walutomatManager)
    {
        $this->em = $em;
        $this->liquidityTransactionRepository = $liquidityTransactionRepository;
        $this->binanceLiquidityManager = $binanceLiquidityManager;
        $this->bitbayLiquidityManager = $bitbayLiquidityManager;
        $this->krakenLiquidityManager = $krakenLiquidityManager;
        $this->walutomatManager = $walutomatManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('', InputArgument::OPTIONAL, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while(1 == 1) {
            try{
                $this->em->clear();

                $liquidityTransactions = $this->liquidityTransactionRepository->findForExternalRealization();
                if($liquidityTransactions){
                    /** @var LiquidityTransaction $liquidityTransaction */
                    foreach($liquidityTransactions as $liquidityTransaction){
                        try{
                            $externalOrderbook = null;
                            $externalOrderbookSymbol = null;
                            $amount = null;

                            if($liquidityTransaction->getOrder() instanceof Order){
                                $externalOrderbook          = $liquidityTransaction->getOrder()->getCurrencyPair()->getExternalOrderbook();
                                $externalOrderbookSymbol    = $liquidityTransaction->getOrder()->getCurrencyPair()->getExternalOrderbookSymbol();
                                $amount                     = $liquidityTransaction->getOrder()->getCurrencyPair()->toPrecision($liquidityTransaction->getAmount());
                            }elseif($liquidityTransaction->getCheckoutOrder() instanceof CheckoutOrder){
                                $externalOrderbook          = $liquidityTransaction->getCheckoutOrder()->getCurrencyPair()->getExternalOrderbook();
                                $externalOrderbookSymbol    = $liquidityTransaction->getCheckoutOrder()->getCurrencyPair()->getExternalOrderbookSymbol();
                                $amount                     = $liquidityTransaction->getCheckoutOrder()->getCurrencyPair()->toPrecision($liquidityTransaction->getAmount());
                            }elseif($liquidityTransaction->isTetherBalancerTransaction()){
                                $externalOrderbook          = $liquidityTransaction->getTetherBalancerOrderbook();
                                $externalOrderbookSymbol    = $liquidityTransaction->getTetherBalancerOrderbookSymbol();
                                $amount                     = bcadd($liquidityTransaction->getAmount(), 0, 5);
                            }elseif($liquidityTransaction->isEuroBalancerTransaction()){
                                $externalOrderbook          = $liquidityTransaction->getEuroBalancerOrderbook();
                                $externalOrderbookSymbol    = $liquidityTransaction->getEuroBalancerOrderbookSymbol();
                                $amount                     = bcadd($liquidityTransaction->getAmount(), 0, 5);
                            }else{
                                throw new AppException('Cannot find related order');
                            }

                            if($externalOrderbook === CurrencyPair::EXTERNAL_ORDERBOOK_WALUTOMAT){
                                $side = $this->walutomatManager->resolveOrderSide($liquidityTransaction);

                                try{
                                    $response = $this->walutomatManager->newMarketOrder($side, $externalOrderbookSymbol, $amount, $liquidityTransaction->getPrice());

                                    $liquidityTransaction->setMarketResponse((array) $response);
                                    $liquidityTransaction->setRealized(true);
                                    $liquidityTransaction->setSucceed(true);

                                    $this->liquidityTransactionRepository->save($liquidityTransaction);
                                }catch (\Exception $exception){
                                    $liquidityTransaction->setRealized(true);
                                    $liquidityTransaction->setSucceed(false);
                                    $this->liquidityTransactionRepository->save($liquidityTransaction);

                                    dump($exception->getMessage());

                                    // TODO SEND SMS with notification
                                }
                            }else{
                                switch ($externalOrderbook) {
                                    case CurrencyPair::EXTERNAL_ORDERBOOK_BITBAY:
                                        $this->selectedLiquidityManager = $this->bitbayLiquidityManager;

                                        break;
                                    case CurrencyPair::EXTERNAL_ORDERBOOK_BINANCE:
                                        $this->selectedLiquidityManager = $this->binanceLiquidityManager;

                                        break;
                                    case CurrencyPair::EXTERNAL_ORDERBOOK_KRAKEN:
                                        $this->selectedLiquidityManager = $this->krakenLiquidityManager;

                                        break;
                                }

                                if(!($this->selectedLiquidityManager instanceof LiquidityManagerInterface)) throw new AppException('Selected liquidity manager not allowed');

                                $side = $this->selectedLiquidityManager->resolveOrderSide($liquidityTransaction);

                                try{
                                    $response = $this->selectedLiquidityManager->newMarketOrder($side, $externalOrderbookSymbol, $amount);
                                    if($this->selectedLiquidityManager->isNewOrderResponseValid($response)){
                                        $responsePrice = $this->selectedLiquidityManager->resolveOrderAveragePrice($response);

                                        if($responsePrice) $liquidityTransaction->setPrice($responsePrice);
                                        $liquidityTransaction->setMarketResponse((array) $response);
                                        $liquidityTransaction->setRealized(true);
                                        $liquidityTransaction->setSucceed(true);

                                        $this->liquidityTransactionRepository->save($liquidityTransaction);
                                    }
                                }catch (\Exception $exception){
                                    $liquidityTransaction->setRealized(true);
                                    $liquidityTransaction->setSucceed(false);
                                    $this->liquidityTransactionRepository->save($liquidityTransaction);

                                    dump($exception->getMessage());

                                    // TODO SEND SMS with notification
                                }

                                $response = null;
                                unset($response);
                            }
                        }catch (\Exception $exception){
                            dump($exception->getMessage());
                        }
                    }
                }

                $liquidityTransactions = null;
                unset($liquidityTransactions);

                sleep(1);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                sleep(1);
            }
        }
    }
}
