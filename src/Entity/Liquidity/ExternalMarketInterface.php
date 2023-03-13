<?php

namespace App\Entity\Liquidity;

interface ExternalMarketInterface
{
    const EXTERNAL_MARKET_BITBAY = 1;
    const EXTERNAL_MARKET_BINANCE = 2;
    const EXTERNAL_MARKET_KRAKEN = 3;
    const EXTERNAL_MARKET_WALUTOMAT = 4;
}
