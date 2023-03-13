<?php

namespace App\Event;

use App\Entity\CurrencyPair;
use Symfony\Component\EventDispatcher\Event;

class WSPushExternalOrderbookEvent extends Event
{
    public const NAME = 'websocket.on_push_external_orderbook';

    /** @var CurrencyPair */
    protected $currencyPair;

    /**
     * WSPushExternalOrderbookEvent constructor.
     * @param CurrencyPair $currencyPair
     */
    public function __construct(CurrencyPair $currencyPair)
    {
        $this->currencyPair = $currencyPair;
    }

    /**
     * @return CurrencyPair
     */
    public function getCurrencyPair(): CurrencyPair
    {
        return $this->currencyPair;
    }
}