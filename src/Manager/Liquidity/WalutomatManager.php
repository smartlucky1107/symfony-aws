<?php

namespace App\Manager\Liquidity;

use App\Entity\Liquidity\LiquidityTransaction;
use App\Exception\AppException;
use App\Service\ExternalMarket\WalutomatApi;

class WalutomatManager
{
    /** @var WalutomatApi */
    protected $api;

    /**
     * WalutomatManager constructor.
     * @param WalutomatApi $api
     */
    public function __construct(WalutomatApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getBalance()
    {
        $this->api->updateAuthHeaders(); // authorization headers

        $response = $this->api->doRequest('account/balances');
        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $side
     * @param string $symbol
     * @param string $amount
     * @param string $rateExpected
     * @return mixed|null
     * @throws AppException
     * @throws \App\Exception\ApiConnectionException
     */
    public function newMarketOrder(string $side, string $symbol, string $amount, string $rateExpected)
    {
        if(!($side === WalutomatOrderInterface::SIDE_BUY || $side === WalutomatOrderInterface::SIDE_SELL)){
            throw new AppException('Order side not allowed');
        }

        $submitId = md5(uniqid().uniqid()).uniqid();

        $data = [
            'submitId'      => substr($submitId, 0, 35),
            'currencyPair'  => str_replace('_', '', $symbol),
            'buySell'       => $side,
            'volume'        => bcadd($amount, 0, 2),
            'volumeCurrency'=> explode('_', $symbol)[0],
            'limitPrice'    => bcadd($rateExpected, 0, 4)
        ];

        $this->api->updateAuthHeaders(); // authorization headers

        $response = $this->api->doRequest('market_fx/orders', null, 'POST', $data);
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
            $side = WalutomatOrderInterface::SIDE_BUY;
        }elseif($liquidityTransaction->getType() === LiquidityTransaction::TYPE_SELL){
            $side = WalutomatOrderInterface::SIDE_SELL;
        }else{
            throw new AppException('Liquidity transaction type not allowed');
        }

        return $side;
    }

    /**
     * @param string $orderId
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getOrderInfo(string $orderId)
    {
        $this->api->updateAuthHeaders(); // authorization headers

        $data = [
            'orderId' => $orderId
        ];

        $response = $this->api->doRequest('market_fx/orders?' . http_build_query($data));
        $this->api->resolveResponseErrors();

        return $response;
    }
}
