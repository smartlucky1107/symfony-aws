<?php

namespace App\Manager\Liquidity;

use App\Entity\Currency;
use App\Entity\Liquidity\LiquidityTransaction;
use App\Exception\ApiConnectionException;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Service\ExternalMarket\KrakenApi;

class KrakenLiquidityManager implements LiquidityManagerInterface
{
    /** @var KrakenApi */
    protected $api;

    /**
     * KrakenManager constructor.
     * @param KrakenApi $api
     */
    public function __construct(KrakenApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return array
     * @throws \App\Service\ExternalMarket\KrakenAPIException
     */
    public function getBalance()
    {
        $response = $this->api->QueryPrivate('Balance');

        return $response;
    }

    /**
     * @param string $side
     * @param string $symbol
     * @param string $amount
     * @return array|mixed
     * @throws AppException
     * @throws \App\Service\ExternalMarket\KrakenAPIException
     */
    public function newMarketOrder(string $side, string $symbol, string $amount)
    {
        if(!($side === KrakenOrderInterface::SIDE_BUY || $side === KrakenOrderInterface::SIDE_SELL)){
            throw new AppException('Order side not allowed');
        }

        $data = [
            'pair'          => $symbol,
            'type'          => $side,
            'ordertype'     => KrakenOrderInterface::TYPE_MARKET,
            'volume'        => $amount
        ];

        $response = $this->api->QueryPrivate('AddOrder', $data);

        return $response;
    }

    /**
     * @param array $txs
     * @return array
     * @throws \App\Service\ExternalMarket\KrakenAPIException
     */
    public function getOrderInfo(array $txs) {
        $data = [
            'txid'      => join(',', $txs),
            'trades'    => true
        ];

        $response = $this->api->QueryPrivate('QueryOrders', $data);

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
            $side = KrakenOrderInterface::SIDE_BUY;
        }elseif($liquidityTransaction->getType() === LiquidityTransaction::TYPE_SELL){
            $side = KrakenOrderInterface::SIDE_SELL;
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
        if(isset($response['result']) && $response['result'] && isset($response['result']['txid']) && $response['result']['txid'] && is_array($response['result']['txid'])){
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
        $responsePrice = null;
        $prices = [];

        try{
            $orderResponse = $this->getOrderInfo($response['result']['txid']);
            if(isset($orderResponse['result']) && is_array($orderResponse['result'])){
                foreach($orderResponse['result'] as $transaction){
                    if(isset($transaction['price'])){
                        $prices[] = $transaction['price'];
                    }

                }
            }

            if(is_array($prices) && count($prices) > 0){
                $sum = 0;

                foreach($prices as $transactionPrice){
                    $sum = bcadd($sum, $transactionPrice, PriceInterface::BC_SCALE);
                }

                $responsePrice = bcdiv($sum, count($prices), PriceInterface::BC_SCALE);
            }
        }catch (\Exception $exception){}

        return $responsePrice;
    }
}
