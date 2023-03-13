<?php

namespace App\EventListener;

use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Event\WSPushExternalOrderbookEvent;
use App\Entity\OrderBook\Order;
use App\Entity\Wallet\Wallet;
use App\Event\WSPushEvent;
use App\Event\WSPushNotificationEvent;
use App\Manager\OrderBookManager;
use App\Model\OrderBook\OrderBookModel;
use App\Model\WS\WSPushRequest;
use App\Server\AppWebsocketInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WSPushListener
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var OrderBookManager */
    private $orderBookManager;

    /**
     * WSPushListener constructor.
     * @param ParameterBagInterface $parameters
     * @param OrderBookManager $orderBookManager
     */
    public function __construct(ParameterBagInterface $parameters, OrderBookManager $orderBookManager)
    {
        $this->parameters = $parameters;
        $this->orderBookManager = $orderBookManager;
    }

    /**
     * @param WSPushRequest $WSPushRequest
     * @throws \Exception
     */
    private function push(WSPushRequest $WSPushRequest){
        $socket = new \App\Lib\WebSocket\WebSocket($this->parameters->get('websocket_host'), $this->parameters->get('websocket_port'));
        $socketClient = $socket->connect();

        $socket->sendData($socketClient, json_encode($WSPushRequest));
    }

    /**
     * @param WSPushEvent $event
     * @throws \Exception
     */
    public function onPush(WSPushEvent $event)
    {
        $this->push($event->getWsPushRequest());
    }

    /**
     * @param WSPushExternalOrderbookEvent $event
     * @throws \Exception
     */
    public function onPushExternalOrderBook(WSPushExternalOrderbookEvent $event)
    {
        /** @var OrderBookModel $orderBook */
        $orderBook = $this->orderBookManager->generateExternalOrderbook($event->getCurrencyPair());

        /** @var WSPushRequest $wsPushRequest */
        $wsPushRequest = new WSPushRequest(AppWebsocketInterface::MODULE_EXTERNAL_ORDERBOOK, ['orderbook' => $orderBook], null, $event->getCurrencyPair()->pairShortName());
        $this->push($wsPushRequest);
    }

    /**
     * @param WSPushNotificationEvent $event
     * @throws \Exception
     */
    public function onPushNotification(WSPushNotificationEvent $event){
        /** @var WSPushRequest $wsPushRequest */
        $wsPushRequest = new WSPushRequest(AppWebsocketInterface::MODULE_NOTIFICATIONS, ['notification' => $event->getNotification()], $event->getNotification()->userId);
        $this->push($wsPushRequest);
    }
}
