<?php

namespace App\Manager\Liquidity\Orderbook;

use App\Entity\CurrencyPair;
use App\Manager\Liquidity\LiquidityManager;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Entity\Liquidity\ExternalOrder;
use GuzzleHttp;

class KrakenOrderbookManager implements ExternalOrderbookManagerInterface
{
    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /** @var LiquidityManager */
    private $liquidityManager;

    private $counter;

    /**
     * KrakenOrderbookManager constructor.
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
     * @return mixed|void
     * @throws GuzzleHttp\Exception\GuzzleException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function buildOrderBook(CurrencyPair $currencyPair)
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.kraken.com/0/public/Depth']);
        $response = $client->request('GET', '?pair=' . $currencyPair->getExternalOrderbookSymbol());

        $orderbook = json_decode($response->getBody()->getContents());

        if(isset($orderbook->result)) {
            if(isset($orderbook->result->{$currencyPair->getExternalOrderbookSymbol()})){
                $orderbook = $orderbook->result->{$currencyPair->getExternalOrderbookSymbol()};
            }elseif(isset($orderbook->result->XXBTZEUR)){
                $orderbook = $orderbook->result->XXBTZEUR;
            }elseif(isset($orderbook->result->XETHZEUR)){
                $orderbook = $orderbook->result->XETHZEUR;
            }elseif(isset($orderbook->result->XBTUSDT)){
                $orderbook = $orderbook->result->XBTUSDT;
            }elseif(isset($orderbook->result->USDTZUSD)){
                $orderbook = $orderbook->result->USDTZUSD;
            }

            if(isset($orderbook->asks)){
                foreach($orderbook->asks as $item){
                    if(isset($item[0]) && isset($item[1])){
                        /** @var ExternalOrder $externalOrder */
                        $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_SELL, $item[0], $item[1]);
                        $this->externalOrderRepository->save($externalOrder);
                        $this->externalOrderRepository->clear();
                    }
                }
            }

            if(isset($orderbook->bids)){
                foreach($orderbook->bids as $item){
                    if(isset($item[0]) && isset($item[1])){
                        /** @var ExternalOrder $externalOrder */
                        $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_BUY, $item[0], $item[1]);
                        $this->externalOrderRepository->save($externalOrder);
                        $this->externalOrderRepository->clear();
                    }
                }
            }
        }
    }

    public function subscribe(CurrencyPair $currencyPair)
    {
        // TODO
    }
}
