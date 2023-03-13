<?php

namespace App\Entity\Liquidity\Traits;

interface OrderTypeInterface
{
    const TYPE_BUY = 1;
    const TYPE_SELL = 2;

    const TYPES = [
        self::TYPE_BUY      => 'Buy',
        self::TYPE_SELL     => 'Sell'
    ];
}