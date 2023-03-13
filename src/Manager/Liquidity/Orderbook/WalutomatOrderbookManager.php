<?php

namespace App\Manager\Liquidity\Orderbook;

use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Entity\Liquidity\ExternalOrder;
use GuzzleHttp;

class WalutomatOrderbookManager implements ExternalOrderbookManagerInterface
{
    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    private $counter;

    /**
     * WalutomatOrderbookManager constructor.
     * @param ExternalOrderRepository $externalOrderRepository
     * @param CurrencyPairRepository $currencyPairRepository
     */
    public function __construct(ExternalOrderRepository $externalOrderRepository, CurrencyPairRepository $currencyPairRepository)
    {
        $this->externalOrderRepository = $externalOrderRepository;
        $this->currencyPairRepository = $currencyPairRepository;

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
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.walutomat.pl/api/v2.0.0/market_fx/best_offers']);
        $response = $client->request('GET', '?currencyPair=' . $currencyPair->getExternalOrderbookSymbol());

        $orderbook = json_decode($response->getBody()->getContents());
        if(isset($orderbook->result)){
            if(isset($orderbook->result->asks)){
                foreach($orderbook->result->asks as $item){
                    if(isset($item->price) && isset($item->volume)){
                        /** @var ExternalOrder $externalOrder */
                        $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_SELL, $item->price, $item->volume);
                        $this->externalOrderRepository->save($externalOrder);
                        $this->externalOrderRepository->clear();
                    }
                }
            }
            if(isset($orderbook->result->bids)){
                foreach($orderbook->result->bids as $item){
                    if(isset($item->price) && isset($item->volume)){
                        /** @var ExternalOrder $externalOrder */
                        $externalOrder = new ExternalOrder($currencyPair, ExternalOrder::TYPE_BUY, $item->price, $item->volume);
                        $this->externalOrderRepository->save($externalOrder);
                        $this->externalOrderRepository->clear();
                    }
                }
            }
        }
    }

    public function subscribe(CurrencyPair $currencyPair)
    {

    }
}
