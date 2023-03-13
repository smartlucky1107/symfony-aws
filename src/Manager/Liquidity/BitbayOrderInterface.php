<?php

namespace App\Manager\Liquidity;

interface BitbayOrderInterface
{
    const SIDE_BUY = 'buy';
    const SIDE_SELL = 'sell';

    const TYPE_MARKET = 'market';
}
