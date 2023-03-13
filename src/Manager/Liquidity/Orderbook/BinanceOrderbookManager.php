<?php

namespace App\Manager\Liquidity\Orderbook;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Manager\Liquidity\LiquidityManager;
use App\Model\PriceInterface;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Entity\Liquidity\ExternalOrder;

class BinanceOrderbookManager implements ExternalOrderbookManagerInterface
{
    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /** @var LiquidityManager */
    private $liquidityManager;

    private $counter;

    /**
     * BinanceOrderbookManager constructor.
     * @param ExternalOrderRepository $externalOrderRepository
     * @param LiquidityManager $liquidityManager
     */
    public function __construct(ExternalOrderRepository $externalOrderRepository, LiquidityManager $liquidityManager)
    {
        $this->externalOrderRepository = $externalOrderRepository;
        $this->liquidityManager = $liquidityManager;

        $this->counter = 0;
    }

    public function buildOrderBook(CurrencyPair $currencyPair)
    {

    }

//    /**
//     * @param CurrencyPair $currencyPair
//     * @throws GuzzleHttp\Exception\GuzzleException
//     * @throws \Doctrine\ORM\ORMException
//     * @throws \Doctrine\ORM\OptimisticLockException
//     */
//    public function buildOrderBook(CurrencyPair $currencyPair)
//    {
//        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.binance.com/api/v3/']);
//        $response = $client->request('GET', 'depth?symbol=' . $currencyPair->getExternalOrderbookSymbol());
//
//        $orderbook = json_decode($response->getBody()->getContents());
//        if(isset($orderbook->asks)){
//            foreach($orderbook->asks as $item){
//                if(isset($item[0]) && isset($item[1])){
//                    /** @var ExternalOrder $externalOrder */
//                    $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_SELL, $item[0], $item[1]);
//                    $this->externalOrderRepository->save($externalOrder);
//                    $this->externalOrderRepository->clear();
//                }
//            }
//        }
//        if(isset($orderbook->bids)){
//            foreach($orderbook->bids as $item){
//                if(isset($item[0]) && isset($item[1])){
//                    /** @var ExternalOrder $externalOrder */
//                    $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_BUY, $item[0], $item[1]);
//                    $this->externalOrderRepository->save($externalOrder);
//                    $this->externalOrderRepository->clear();
//                }
//            }
//        }
//    }

    public function subscribe(CurrencyPair $currencyPair)
    {
        $subscribeName = strtolower($currencyPair->getExternalOrderbookSymbol());

        \Ratchet\Client\connect('wss://stream.binance.com:9443/ws/' . $subscribeName . '@depth')->then(function($conn) use ($currencyPair, $subscribeName) {
            $conn->send('
                {
                  "method": "SUBSCRIBE",
                  "params": [
                    "' . $subscribeName . '@depth"
                  ],
                  "id": ' . $currencyPair->getId() . '
                }
            ');

            var_dump(memory_get_usage(true)/1024/1024);
            echo '---'.PHP_EOL;

            $conn->on('message', function($msg) use ($conn, $currencyPair) {
                $this->counter++;
                print_r($this->counter);
                echo PHP_EOL;
                echo ' = ';
                print_r(memory_get_usage(true)/1024/1024);
                
                //return true;
                echo PHP_EOL;

                $data = json_decode($msg);
                if(isset($data->e) && isset($data->E) && isset($data->s) && isset($data->b) && isset($data->a)){
                    $shortName = str_replace('-', '', $currencyPair->getExternalOrderbookSymbol());
                    if(strtoupper($shortName) === strtoupper($data->s)){
                        if($data->e === 'depthUpdate'){
                            $bids = $data->b;
                            $asks = $data->a;

                            if(is_array($bids) && count($bids) > 0){
                                foreach($bids as $bid){
                                    /** @var ExternalOrder $externalOrder */
                                    $externalOrder = $this->externalOrderRepository->findOneBy([
                                        'currencyPair'  => $currencyPair->getId(),
                                        'type'          => ExternalOrder::TYPE_BUY,
                                        'rate'          => $bid['0'],
                                    ]);
                                    if($externalOrder instanceof ExternalOrder){
                                        $comp = bccomp($bid['1'], 0 , PriceInterface::BC_SCALE);
                                        if($comp === 0){
                                            $this->externalOrderRepository->remove($externalOrder);
                                            $this->externalOrderRepository->detach($externalOrder);
                                        }else{
                                            $externalOrder->setAmount($bid['1']);
                                            $externalOrder->refreshLiquidityValues();

                                            $externalOrder = $this->externalOrderRepository->save($externalOrder);
                                            $this->externalOrderRepository->detach($externalOrder);
                                        }

                                        $comp = null;
                                        unset($comp);
                                    }else{
                                        $comp = bccomp($bid['1'], 0 , PriceInterface::BC_SCALE);
                                        if($comp === 1){
                                            /** @var ExternalOrder $externalOrder */
                                            $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_BUY, $bid['0'], $bid['1']);
                                            $externalOrder = $this->externalOrderRepository->save($externalOrder);
                                            $this->externalOrderRepository->detach($externalOrder);
                                        }

                                        $comp = null;
                                        unset($comp);
                                    }

                                    if($externalOrder instanceof ExternalOrder){
                                        $this->liquidityManager->makePartialOppositeOrders($externalOrder);
                                    }

                                    $externalOrder = null;
                                    unset($externalOrder);

                                    $bid = null;
                                    unset($bid);
                                }
                            }

                            $bids = null;
                            unset($bids);

                            if(is_array($asks) && count($asks) > 0){
                                foreach($asks as $ask){
                                    /** @var ExternalOrder $externalOrder */
                                    $externalOrder = $this->externalOrderRepository->findOneBy([
                                        'currencyPair'  => $currencyPair->getId(),
                                        'type'          => ExternalOrder::TYPE_SELL,
                                        'rate'          => $ask['0'],
                                    ]);
                                    if($externalOrder instanceof ExternalOrder){
                                        $comp = bccomp($ask['1'], 0 , PriceInterface::BC_SCALE);
                                        if($comp === 0){
                                            $this->externalOrderRepository->remove($externalOrder);
                                            $this->externalOrderRepository->detach($externalOrder);
                                        }else{
                                            $externalOrder->setAmount($ask['1']);
                                            $externalOrder->refreshLiquidityValues();

                                            $externalOrder = $this->externalOrderRepository->save($externalOrder);
                                            $this->externalOrderRepository->detach($externalOrder);
                                        }

                                        $comp = null;
                                        unset($comp);
                                    }else{
                                        $comp = bccomp($ask['1'], 0 , PriceInterface::BC_SCALE);
                                        if($comp === 1){
                                            /** @var ExternalOrder $externalOrder */
                                            $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_SELL, $ask['0'], $ask['1']);
                                            $externalOrder = $this->externalOrderRepository->save($externalOrder);
                                            $this->externalOrderRepository->detach($externalOrder);
                                        }

                                        $comp = null;
                                        unset($comp);
                                    }

                                    if($externalOrder instanceof ExternalOrder){
                                        $this->liquidityManager->makePartialOppositeOrders($externalOrder);
                                    }

                                    $externalOrder = null;
                                    unset($externalOrder);

                                    $ask = null;
                                    unset($ask);
                                }
                            }

                            $asks = null;
                            unset($asks);
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
