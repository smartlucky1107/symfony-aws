<?php

namespace App\Manager\Liquidity\Orderbook;

use App\Entity\CurrencyPair;
use App\Manager\Liquidity\LiquidityManager;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Entity\Liquidity\ExternalOrder;
use GuzzleHttp;

class BitbayOrderbookManager implements ExternalOrderbookManagerInterface
{
    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /** @var LiquidityManager */
    private $liquidityManager;

    private $counter;

    /**
     * BitbayOrderbookManager constructor.
     * @param ExternalOrderRepository $externalOrderRepository
     * @param LiquidityManager $liquidityManager
     */
    public function __construct(ExternalOrderRepository $externalOrderRepository, LiquidityManager $liquidityManager)
    {
        $this->externalOrderRepository = $externalOrderRepository;
        $this->liquidityManager = $liquidityManager;

        $this->counter = 0;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @throws GuzzleHttp\Exception\GuzzleException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function buildOrderBook(CurrencyPair $currencyPair)
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.bitbay.net/rest/trading/orderbook/']);
        $response = $client->request('GET', $currencyPair->getExternalOrderbookSymbol());

        $orderbook = json_decode($response->getBody()->getContents());
        if(isset($orderbook->sell)){
            foreach($orderbook->sell as $item){
                if(isset($item->ra) && isset($item->ca)){
                    /** @var ExternalOrder $externalOrder */
                    $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_SELL, $item->ra, $item->ca);
                    $this->externalOrderRepository->save($externalOrder);
                    $this->externalOrderRepository->clear();
                }
            }
        }
        if(isset($orderbook->buy)){
            foreach($orderbook->buy as $item){
                if(isset($item->ra) && isset($item->ca)){
                    /** @var ExternalOrder $externalOrder */
                    $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_BUY, $item->ra, $item->ca);
                    $this->externalOrderRepository->save($externalOrder);
                    $this->externalOrderRepository->clear();
                }
            }
        }
    }

    public function subscribe(CurrencyPair $currencyPair)
    {
        var_dump(memory_get_usage(true)/1024/1024);
        echo '---'.PHP_EOL;

        \Ratchet\Client\connect('wss://api.bitbay.net/websocket/')->then(function($conn) use ($currencyPair) {
            $conn->send('
                {
                 "action": "subscribe-public",
                 "module": "trading",
                 "path": "orderbook/'.strtolower($currencyPair->getExternalOrderbookSymbol()).'"
                }
            ');

            $conn->on('message', function($msg) use ($conn, $currencyPair) {
                $this->counter++;
                print_r($this->counter);
                echo PHP_EOL;
                echo ' = ';
                print_r(memory_get_usage(true)/1024/1024);

                //return true;
                echo PHP_EOL;

                $data = json_decode($msg);
                if(isset($data->message) && isset($data->message->changes)){
                    foreach($data->message->changes as $change){
                        if($change->action === 'update'){
                            /** @var ExternalOrder $externalOrder */
                            $externalOrder = $this->externalOrderRepository->findOneBy([
                                'currencyPair'  => $currencyPair->getId(),
                                'type'          => ($change->entryType === 'Buy' ? ExternalOrder::TYPE_BUY : ExternalOrder::TYPE_SELL),
                                'rate'          => $change->rate,
                                'removed'       => false
                            ]);
                            if($externalOrder instanceof ExternalOrder){
                                $externalOrder->setAmount($change->state->ca);
                                $externalOrder->refreshLiquidityValues();

                                $this->externalOrderRepository->save($externalOrder);
//                                    $this->orderRepository->detach($order);
                            }else{
                                /** @var ExternalOrder $externalOrder */
                                $externalOrder = new ExternalOrder($currencyPair, ($change->entryType === 'Buy' ? ExternalOrder::TYPE_BUY : ExternalOrder::TYPE_SELL), $change->state->ra, $change->state->ca);
                                $this->externalOrderRepository->save($externalOrder);
                            }

                            if($externalOrder instanceof ExternalOrder){
                                $this->liquidityManager->makePartialOppositeOrders($externalOrder);
                            }

                            $externalOrder = null;
                            unset($externalOrder);
                        }elseif($change->action === 'remove'){
                            /** @var ExternalOrder $externalOrder */
                            $externalOrder = $this->externalOrderRepository->findOneBy([
                                'currencyPair'  => $currencyPair->getId(),
                                'type'          => ($change->entryType === 'Buy' ? ExternalOrder::TYPE_BUY : ExternalOrder::TYPE_SELL),
                                'rate'          => $change->rate,
                                'removed'       => false
                            ]);
                            if($externalOrder instanceof ExternalOrder){
                                $this->externalOrderRepository->remove($externalOrder);
                            }
                            $externalOrder = null;
                            unset($externalOrder);
                        }
                    }
                }

                $data = null;
                unset($data);

                $msg = null;
                unset($msg);

                $currencyPair = null;
                unset($currencyPair);
//
//                $conn = null;
//                unset($conn);

                $this->externalOrderRepository->clear();
            });
        }, function (\Exception $exception) {
            dump($exception->getMessage());
        });
    }
}
