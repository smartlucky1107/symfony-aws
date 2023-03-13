<?php

namespace App\Manager\Liquidity;

use App\Entity\Liquidity\LiquidityTransaction;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Service\ExternalMarket\BinanceApi;

class BinanceLiquidityManager implements LiquidityManagerInterface
{
    /** @var BinanceApi */
    protected $api;

    /**
     * BinanceLiquidityManager constructor.
     * @param BinanceApi $api
     */
    public function __construct(BinanceApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getBalance()
    {
        $params = [
            'timestamp' => round(microtime(true) * 1000)
        ];
        $this->api->updateAuthHeaders($params);
        $params['signature'] = $this->api->sign($params);

        $response = $this->api->doRequest('api/v3/account?'.http_build_query($params));
        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $symbol
     * @param string $interval
     * @param int $startTime
     * @param int $endTime
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getKlines(string $symbol, string $interval, int $startTime, int $endTime)
    {
        $params = [
            'symbol'    => $symbol,
            'interval'  => $interval,
            'startTime' => $startTime,
            'endTime'   => $endTime
        ];

        $response = $this->api->doRequest('api/v3/klines?'.http_build_query($params));
        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $side
     * @param string $symbol
     * @param string $amount
     * @return mixed|null
     * @throws AppException
     * @throws \App\Exception\ApiConnectionException
     */
    public function newMarketOrder(string $side, string $symbol, string $amount)
    {
        if(!($side === BinanceOrderInterface::SIDE_BUY || $side === BinanceOrderInterface::SIDE_SELL)){
            throw new AppException('Order side not allowed');
        }

        $params = [
            'symbol'    => $symbol,
            'side'      => $side,
            'type'      => BinanceOrderInterface::TYPE_MARKET,
            'quantity'  => $amount,
            'timestamp' => round(microtime(true) * 1000)
        ];
        $this->api->updateAuthHeaders($params);
        $params['signature'] = $this->api->sign($params);

        $response = $this->api->doRequest('api/v3/order', null, 'POST', $params);
        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param LiquidityTransaction $liquidityTransaction
     * @return mixed|string
     * @throws AppException
     */
    public function resolveOrderSide(LiquidityTransaction $liquidityTransaction)
    {
        if($liquidityTransaction->getType() === LiquidityTransaction::TYPE_BUY){
            $side = BinanceOrderInterface::SIDE_BUY;
        }elseif($liquidityTransaction->getType() === LiquidityTransaction::TYPE_SELL){
            $side = BinanceOrderInterface::SIDE_SELL;
        }else{
            throw new AppException('Liquidity transaction type not allowed');
        }

        return $side;
    }

    /**
     * @param $response
     * @return bool
     */
    public function isNewOrderResponseValid($response) : bool
    {
        if(isset($response->fills) && $response->fills){
            return true;
        }

        return false;
    }

    /**
     * @param $response
     * @return string|null
     */
    public function resolveOrderAveragePrice($response) : ?string
    {
        $transactions = $response->fills;
        $responsePrice = null;

        if(is_array($transactions)){
            $prices = [];

            foreach($transactions as $fill){
                if(isset($fill->price)){
                    $prices[] = $fill->price;
                }
            }

            if(is_array($prices) && count($prices) > 0){
                $sum = 0;

                foreach($prices as $fillPrice){
                    $sum = bcadd($sum, $fillPrice, PriceInterface::BC_SCALE);
                }

                $responsePrice = bcdiv($sum, count($prices), PriceInterface::BC_SCALE);
            }
        }

        return $responsePrice;
    }
}
