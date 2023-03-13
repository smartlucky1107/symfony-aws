<?php

namespace App\Command;

use App\Document\TradingTransaction;
use App\Document\WalletTransferBatch;
use App\Entity\OrderBook\Order;
use App\Exception\ProcessorLoopException;
use App\Manager\OrderManager;
use App\Manager\Processor\TradingProcessor;
use App\Manager\Processor\WalletTransferProcessor;
use App\Manager\Queue\QueueItemManager;
use App\Manager\RedisProvider;
use App\Manager\RedisSubscribeInterface;
use App\Model\TradingTransactionModel;
use App\Model\WalletTransfer\WalletTransferBatchModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TradingProcessorCommand extends Command
{
    protected static $defaultName = 'app:trading:processor';

    /** @var RedisProvider */
    private $redisProvider;

    /** @var TradingProcessor */
    private $tradingProcessor;

    /** @var LoggerInterface */
    private $logger;

    /** @var QueueItemManager */
    private $queueItemManager;

    /** @var ParameterBagInterface */
    private $parameters;

    /** @var EntityManagerInterface */
    private $em;

    /** @var WalletTransferProcessor */
    private $walletTransferProcessor;

    private $stopwatch;

    /**
     * TradingProcessorCommand constructor.
     * @param RedisProvider $redisProvider
     * @param TradingProcessor $tradingProcessor
     * @param LoggerInterface $logger
     * @param QueueItemManager $queueItemManager
     * @param ParameterBagInterface $parameters
     * @param EntityManagerInterface $em
     * @param WalletTransferProcessor $walletTransferProcessor
     */
    public function __construct(RedisProvider $redisProvider, TradingProcessor $tradingProcessor, LoggerInterface $logger, QueueItemManager $queueItemManager, ParameterBagInterface $parameters, EntityManagerInterface $em, WalletTransferProcessor $walletTransferProcessor)
    {
        $this->redisProvider = $redisProvider;
        $this->tradingProcessor = $tradingProcessor;
        $this->logger = $logger;
        $this->queueItemManager = $queueItemManager;
        $this->parameters = $parameters;
        $this->em = $em;
        $this->walletTransferProcessor = $walletTransferProcessor;

        $this->stopwatch = new Stopwatch(true);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redis = $this->redisProvider->getRedis();
        $redisClient = new \Redis();
        $redisClient->connect($this->parameters->get('redis_host'), $this->parameters->get('redis_port'));

        $subscribers = $redisClient->pubsub('numsub', [RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL]);
        if(isset($subscribers[RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL])){
            if($subscribers[RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL] > 0){
                echo 'Processor already running'.PHP_EOL;
                return false;
            }
        }

        // reset Redis list and load TradingTransactions from Mongo to Redis list
        $redisClient->del(RedisSubscribeInterface::TRADING_LIST);
        $tradingTransactions = $this->queueItemManager->findNotProcessedTradingTransactions();
        if($tradingTransactions){
            /** @var TradingTransaction $tradingTransaction */
            foreach($tradingTransactions as $tradingTransaction){
                $tradingTransactionModel = new TradingTransactionModel();
                $tradingTransactionModel->setTradingTransactionId($tradingTransaction->getId());
                $tradingTransactionModel->setOrderId($tradingTransaction->getOrderId());

                $redisClient->lPush(RedisSubscribeInterface::TRADING_LIST, json_encode($tradingTransactionModel));

                $tradingTransactionModel = null;
                unset($tradingTransactionModel);
            }
        }

        $tradingTransactions = null;
        unset($tradingTransactions);

        $redisClient->del(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);
        $walletTransferBatches = $this->queueItemManager->findNotProcessedWalletTransferBatches();
        if($walletTransferBatches){
            /** @var WalletTransferBatch $walletTransferBatch */
            foreach($walletTransferBatches as $walletTransferBatch){
                try{
                    $walletTransferBatchModel = new WalletTransferBatchModel();
                    $walletTransferBatchModel->importFromDocument($walletTransferBatch);

                    $redisClient->lPush(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST, json_encode($walletTransferBatchModel));
                    $walletTransferBatchModel = null;
                    unset($walletTransferBatchModel);
                }catch (\Exception $exception){
                    //$this->logger->error($exception->getMessage());
                    $exception = null;
                    unset($exception);
                }
            }
        }
        $walletTransferBatches = null;
        unset($walletTransferBatches);

        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);

//        $this->stopwatch->start('command');

        ini_set('default_socket_timeout', -1);
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $redis->subscribe([RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL], function(\Redis $redis, $chan, $msg) use ($redisClient){
            try{
//                dump('#################### >>>>>> COMMAND | STOPWATCH');
//                dump($this->stopwatch->lap('command')->getDuration());
//                dump('<<<<<< COMMAND');

                $this->em->clear();
                $this->queueItemManager->dmClear();

                var_dump(memory_get_usage(true)/1024/1024);
                echo '---'.PHP_EOL;

//                // check if all transfers are done then process next trading
//                $len = $redisClient->lLen(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);
//                if($len > 0){
//                    throw new ProcessorLoopException('Trading is waiting..');
//                }

                ############################
                #### process wallet transfers
                ##

                $length = $redisClient->lLen(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);
                dump($length . ' wallet transfers');
                if($length > 0) {
                    for ($i = 1; $i <= $length; $i++) {
                        if($i % 10 === 0) {
                            $this->em->clear();
                            $this->queueItemManager->dmClear();
                            dump('clear memory');
                            //throw new ProcessorLoopException('More than 50 | wallet transfers');
                        }

                        $stopwatch = new Stopwatch(true);
                        $stopwatch->start('walletTransfer');

                        /** @var WalletTransferBatchModel $walletTransferBatchModel */
                        $walletTransferBatchModel = new WalletTransferBatchModel((array) json_decode($redisClient->rPop(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST)));
                        if(!$walletTransferBatchModel->isValid()) throw new ProcessorLoopException('Loaded empty WalletTransferBatchModel');

                        $stopwatch->lap('walletTransfer');

                        // set backup item as processed
                        /** @var WalletTransferBatch $walletTransferBatch */
                        $walletTransferBatch = $this->queueItemManager->setWalletTransferBatchProcessed($walletTransferBatchModel->getWalletTransferBatchId());

                        $stopwatch->lap('walletTransfer');

                        // process WalletTransferBatch
                        /** @var WalletTransferBatchModel $walletTransferBatchModel */
                        $walletTransferBatchModel = $this->walletTransferProcessor->processBatch($walletTransferBatchModel);

                        $stopwatch->lap('walletTransfer');

                        if($walletTransferBatchModel instanceof WalletTransferBatchModel){
                            // Save WalletTransferBatch
                            $walletTransferBatch->importProcessed($walletTransferBatchModel);
                            $this->queueItemManager->setWalletTransferBatchSuccess($walletTransferBatch);
                        }
                        $stopwatch->lap('walletTransfer');

//                        dump('======== process wallet transfers | STOPWATCH');
//                        dump($stopwatch->stop('walletTransfer')->getDuration());

                        $walletTransferBatchModel = null;
                        unset($walletTransferBatchModel);

                        $walletTransferBatch = null;
                        unset($walletTransferBatch);
                    }
                    $this->em->clear();
                    $this->queueItemManager->dmClear();
                }

                ############################
                #### process trading
                ##

                $lengthTrading = $redisClient->lLen(RedisSubscribeInterface::TRADING_LIST);
                dump($lengthTrading . ' trading list');
                if($lengthTrading > 0) {
                    for ($ii = 1; $ii <= $lengthTrading; $ii++) {
                        if ($ii % 10 === 0) {
                            $this->em->clear();
                            $this->queueItemManager->dmClear();
                            dump('clear memory');
                            //throw new ProcessorLoopException('More than 50 | wallet transfers');
                        }

                        $stopwatch = new Stopwatch(true);
                        $stopwatch->start('trading');

                        /** @var TradingTransactionModel $tradingTransactionModel */
                        $tradingTransactionModel = new TradingTransactionModel((array) json_decode($redisClient->rPop(RedisSubscribeInterface::TRADING_LIST)));
                        if(!$tradingTransactionModel->isValid()) throw new ProcessorLoopException('Loaded empty TradingTransactionModel');

                        $stopwatch->lap('trading');

                        try{
                            // set backup item as processed
                            $this->queueItemManager->setTradingTransactionProcessed($tradingTransactionModel->getTradingTransactionId());

                            $stopwatch->lap('trading');

                            $this->tradingProcessor->getOrderRepository()->checkConnection();

                            // process trading
                            $this->tradingProcessor->loadOrder($tradingTransactionModel->getOrderId());

                            $stopwatch->lap('trading');

                            $this->tradingProcessor->processTrading();

//                            dump('======== trading transfers | STOPWATCH');
//                            dump($stopwatch->stop('trading')->getDuration());

                            $this->em->clear();
                            $this->queueItemManager->dmClear();

                            $this->tradingProcessor->clearMemory();
                        }catch (\Exception $exception){
                            dump($exception->getMessage());
                        }

                        $tradingTransactionModel = null;
                        unset($tradingTransactionModel);

                        ############################
                        #### process wallet transfers
                        ##

                        $length = $redisClient->lLen(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);
                        dump($length . ' wallet transfers');
                        if($length > 0) {
                            for ($i = 1; $i <= $length; $i++) {
                                if($i % 10 === 0) {
                                    $this->em->clear();
                                    $this->queueItemManager->dmClear();
                                    dump('clear memory');
                                    //throw new ProcessorLoopException('More than 50 | wallet transfers');
                                }

                                $stopwatch = new Stopwatch(true);
                                $stopwatch->start('walletTransfer');

                                /** @var WalletTransferBatchModel $walletTransferBatchModel */
                                $walletTransferBatchModel = new WalletTransferBatchModel((array) json_decode($redisClient->rPop(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST)));
                                if(!$walletTransferBatchModel->isValid()) throw new ProcessorLoopException('Loaded empty WalletTransferBatchModel');

                                $stopwatch->lap('walletTransfer');

                                // set backup item as processed
                                /** @var WalletTransferBatch $walletTransferBatch */
                                $walletTransferBatch = $this->queueItemManager->setWalletTransferBatchProcessed($walletTransferBatchModel->getWalletTransferBatchId());

                                $stopwatch->lap('walletTransfer');

                                // process WalletTransferBatch
                                /** @var WalletTransferBatchModel $walletTransferBatchModel */
                                $walletTransferBatchModel = $this->walletTransferProcessor->processBatch($walletTransferBatchModel);

                                $stopwatch->lap('walletTransfer');

                                if($walletTransferBatchModel instanceof WalletTransferBatchModel){
                                    // Save WalletTransferBatch
                                    $walletTransferBatch->importProcessed($walletTransferBatchModel);
                                    $this->queueItemManager->setWalletTransferBatchSuccess($walletTransferBatch);
                                }
                                $stopwatch->lap('walletTransfer');

//                                dump('======== process wallet transfers | STOPWATCH');
//                                dump($stopwatch->stop('walletTransfer')->getDuration());

                                $walletTransferBatchModel = null;
                                unset($walletTransferBatchModel);

                                $walletTransferBatch = null;
                                unset($walletTransferBatch);
                            }
                            $this->em->clear();
                            $this->queueItemManager->dmClear();
                        }
                    }
                }else{
                    throw new ProcessorLoopException('Empty trading list');
                }

                $redisClient->publish(RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL, json_encode([]));
            } catch (ProcessorLoopException $exception){
                $exception = null;
                unset($exception);

                $lengthTransfers = $redisClient->lLen(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);

                if(!($lengthTransfers > 0)) { sleep(1); }
                unset($lengthTransfers);

                $redisClient->publish(RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL, json_encode([]));
            } catch (\Exception $exception){
                $exception = null;
                unset($exception);

                $lengthTransfers = $redisClient->lLen(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);

                if(!($lengthTransfers > 0)) { sleep(1); }
                unset($lengthTransfers);

                $redisClient->publish(RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL, json_encode([]));

                //$this->logger->error($exception->getMessage());
            }
        });
    }
}
