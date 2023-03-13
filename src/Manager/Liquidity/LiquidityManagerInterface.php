<?php

namespace App\Manager\Liquidity;

use App\Entity\Liquidity\LiquidityTransaction;

/**
 * Interface LiquidityManagerInterface
 * @package App\Manager\Liquidity
 */
interface LiquidityManagerInterface
{
    /**
     * @param string $side
     * @param string $symbol
     * @param string $amount
     * @return mixed
     */
    public function newMarketOrder(string $side, string $symbol, string $amount);

    /**
     * @param LiquidityTransaction $liquidityTransaction
     * @return mixed
     */
    public function resolveOrderSide(LiquidityTransaction $liquidityTransaction);

    /**
     * @param $response
     * @return bool
     */
    public function isNewOrderResponseValid($response) : bool;

    /**
     * @param $response
     * @return string|null
     */
    public function resolveOrderAveragePrice($response) : ?string;
}
