<?php

namespace App\Command;

use App\Entity\CurrencyPair;
use App\Manager\OrderBookManager;
use App\Model\OrderBook\OrderBookModel;
use App\Model\WS\WSPushRequest;
use App\Repository\CurrencyPairRepository;
use App\Server\AppWebsocketInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderbookRefreshCommand extends Command
{
    protected static $defaultName = 'app:orderbook:refresh';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityManagerInterface */
    private $em;

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var ParameterBagInterface */
    private $parameters;

    /** @var OrderBookManager */
    private $orderBookManager;

    /**
     * OrderbookRefreshCommand constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $em
     * @param CurrencyPairRepository $currencyPairRepository
     * @param ParameterBagInterface $parameters
     * @param OrderBookManager $orderBookManager
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em, CurrencyPairRepository $currencyPairRepository, ParameterBagInterface $parameters, OrderBookManager $orderBookManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;
        $this->currencyPairRepository = $currencyPairRepository;
        $this->parameters = $parameters;
        $this->orderBookManager = $orderBookManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $socket = new \App\Lib\WebSocket\WebSocket($this->parameters->get('websocket_host'), $this->parameters->get('websocket_port'));
        $socketClient = $socket->connect();

        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);

        while(1 == 1) {
            try{
                var_dump(memory_get_usage(true)/1024/1024);
                echo '---'.PHP_EOL;

                $this->em->clear();

                $currencyPairs = $this->currencyPairRepository->findBy(['enabled' => true]);
                if($currencyPairs){
                    /** @var CurrencyPair $currencyPair */
                    foreach ($currencyPairs as $currencyPair){
                        try{
                            /** @var OrderBookModel $orderBook */
                            $orderBook = $this->orderBookManager->generateOrderBook($currencyPair);

                            if(!empty($orderBook->offerOrders) && !empty($orderBook->bidOrders)) {
                                /** @var WSPushRequest $wsPushRequest */
                                $wsPushRequest = new WSPushRequest(AppWebsocketInterface::MODULE_ORDERBOOK, ['orderbook' => $orderBook], null, $currencyPair->pairShortName());
                                $socket->sendData($socketClient, json_encode($wsPushRequest));
                            }
                        }catch (\Exception $exception){
                            dump($exception->getMessage());
                        }
                    }
                }

                $currencyPairs = null;
                unset($currencyPairs);

                sleep(2);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                sleep(2);
            }
        }
    }
}
