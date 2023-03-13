<?php

namespace App\Manager\Liquidity;

use App\Entity\Currency;
use App\Entity\Liquidity\LiquidityTransaction;
use App\Exception\ApiConnectionException;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Service\ExternalMarket\BitbayApi;

class BitbayLiquidityManager implements LiquidityManagerInterface
{
    /** @var BitbayApi */
    protected $api;

    /**
     * BitbayLiquidityManager constructor.
     * @param BitbayApi $api
     */
    public function __construct(BitbayApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed|null
     * @throws ApiConnectionException
     * @throws \App\Exception\ApiException\BitbayException
     * @throws \Exception
     */
    public function getBalance()
    {
        $this->api->updateAuthHeaders();

        $response = $this->api->doRequest('balances/BITBAY/balance');
        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param Currency $currency
     * @return float
     * @throws ApiConnectionException
     * @throws \App\Exception\ApiException\BitbayException
     */
    public function getAvailableFunds(Currency $currency) : float
    {
        $response = $this->getBalance();

        if(isset($response->balances) && is_array($response->balances)){
            foreach($response->balances as $wallet){
                if(isset($wallet->availableFunds) && isset($wallet->currency) && strtoupper($wallet->currency) === strtoupper($currency->getShortName())){
                    return (float) $wallet->availableFunds;
                }
            }
        }

        return 0;
    }

    /**
     * @param string $side
     * @param string $symbol
     * @param string $amount
     * @return mixed|null
     * @throws ApiConnectionException
     * @throws AppException
     * @throws \App\Exception\ApiException\BitbayException
     * @throws \Exception
     */
    public function newMarketOrder(string $side, string $symbol, string $amount)
    {
        if(!($side === BitbayOrderInterface::SIDE_BUY || $side === BitbayOrderInterface::SIDE_SELL)){
            throw new AppException('Order side not allowed');
        }

        $data = [
            'offerType'     => $side,
            'amount'        => $amount,
            'price'         => null,
            'rate'          => null,
            'postOnly'      => false,
            'mode'          => BitbayOrderInterface::TYPE_MARKET,
            'fillOrKill'    => false
        ];

        $this->api->updateAuthHeaders($data); // authorization headers

        $response = $this->api->doRequest('trading/offer/'.$symbol, $data);
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
            $side = BitbayOrderInterface::SIDE_BUY;
        }elseif($liquidityTransaction->getType() === LiquidityTransaction::TYPE_SELL){
            $side = BitbayOrderInterface::SIDE_SELL;
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
        if(isset($response->transactions) && $response->transactions){
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
        $transactions = $response->transactions;
        $responsePrice = null;

        if(is_array($transactions)){
            $prices = [];

            foreach($transactions as $transaction){
                if(isset($transaction->rate)){
                    $prices[] = $transaction->rate;
                }
            }

            if(is_array($prices) && count($prices) > 0){
                $sum = 0;

                foreach($prices as $transactionPrice){
                    $sum = bcadd($sum, $transactionPrice, PriceInterface::BC_SCALE);
                }

                $responsePrice = bcdiv($sum, count($prices), PriceInterface::BC_SCALE);
            }
        }

        return $responsePrice;
    }
}
