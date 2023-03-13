<?php

namespace App\Manager\Liquidity;

interface KrakenOrderInterface
{
    const SIDE_BUY = 'buy';
    const SIDE_SELL = 'sell';

    const TYPE_MARKET = 'market';
}
