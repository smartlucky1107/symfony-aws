<?php

namespace App\Manager\Liquidity\Orderbook;

use App\Entity\CurrencyPair;

interface ExternalOrderbookManagerInterface
{
    /**
     * @param CurrencyPair $currencyPair
     * @return mixed
     */
    public function buildOrderBook(CurrencyPair $currencyPair);

    /**
     * @param CurrencyPair $currencyPair
     * @return mixed
     */
    public function subscribe(CurrencyPair $currencyPair);
}
