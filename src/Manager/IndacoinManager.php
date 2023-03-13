<?php

namespace App\Manager;

use App\Entity\Address;
use App\Entity\CheckoutOrder;
use App\Entity\CurrencyPair;
use App\Entity\Verification;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\Payment\PaymentProcessorInterface;
use GuzzleHttp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class IndacoinManager implements PaymentProcessorInterface
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $indacoinUrl;

    /** @var string */
    private $indacoinPartner;

    /** @var string */
    private $indacoinSecret;

    /**
     * IndacoinManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;

        $this->indacoinUrl = $parameters->get('indacoin_url');
        $this->indacoinPartner = $parameters->get('indacoin_partner');
        $this->indacoinSecret = $parameters->get('indacoin_secret');
    }

    /**
     * @param string $amount
     * @param CurrencyPair $currencyPair
     * @return string
     * @throws AppException
     */
    public function calculateTotalPrice(string $amount, CurrencyPair $currencyPair) : string
    {
        if(!$currencyPair->isIndacoinAllowed()) throw new AppException('Cannot calculate payment price');

        $target_url = 'https://indacoin.com/api/GetCoinConvertAmount';

        $currencyFrom = $currencyPair->getBaseCurrency()->getShortName();
        $currencyTo = $currencyPair->getQuotedCurrency()->getShortName();
        $url = "$target_url/$currencyFrom/$currencyTo/$amount";

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('GET', $url, [
            ]);

            return $response->getBody()->getContents();
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){

        }

        throw new AppException('Cannot calculate payment price');
    }

    /**
     * @param string $totalPrice
     * @param CurrencyPair $currencyPair
     * @return string
     * @throws AppException
     */
    public function calculateAmount(string $totalPrice, CurrencyPair $currencyPair) : string
    {
        if(!$currencyPair->isIndacoinAllowed()) throw new AppException('Cannot calculate payment price');

        $target_url = 'https://indacoin.com/api/GetCoinConvertAmount';

        $currencyFrom = $currencyPair->getQuotedCurrency()->getShortName();
        $currencyTo = $currencyPair->getBaseCurrency()->getShortName();
        $url = "$target_url/$currencyFrom/$currencyTo/$totalPrice";

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('GET', $url, [
            ]);

            return $response->getBody()->getContents();
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){

        }

        throw new AppException('Cannot calculate payment price');
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     * @throws AppException
     */
    public function createTransaction(CheckoutOrder $checkoutOrder) : string
    {
//        $cur_out = 'btc';
//        $cur_in = 'usd';

        $cur_out = $checkoutOrder->getCurrencyPair()->getBaseCurrency()->getShortName();
        $cur_in = $checkoutOrder->getCurrencyPair()->getQuotedCurrency()->getShortName();
        $user_id = $checkoutOrder->getUser()->getId();

        $address = null;

        // resolve address
        /** @var Wallet $wallet */
        foreach($checkoutOrder->getUser()->getWallets() as $wallet){
            if($wallet->getCurrency()->getId() === $checkoutOrder->getCurrencyPair()->getBaseCurrency()->getId()){
                if($wallet->getAddresses()){
                    $address = $wallet->getAddresses()[0];
                }
            }
        }

        if($address instanceof Address){
            $target_address = $address->getAddress();
        }else{
            throw new AppException('Address not found');
        }

        $amount_in = (float) $checkoutOrder->toPrecisionQuoted($checkoutOrder->getTotalPaymentValue());

        $method = 'POST';
        $target_url = 'https://indacoin.com/api/exgw_createTransaction';

        $nonce = 1000000;
        $partnername = $this->indacoinPartner;
        $string = $partnername . "_" . $nonce;
        $secret = $this->indacoinSecret;
        $sig = base64_encode(hash_hmac('sha256', $string, $secret,true));

        $arr = array(
            'user_id' => $user_id,
            'cur_in' => $cur_in,
            'cur_out' => $cur_out,
            'target_address' => $target_address,
            'amount_in' => $amount_in,
            'success_url' => 'https://swapcoin.today/status/'.$checkoutOrder->getId(),
            'fail_url' => 'https://swapcoin.today/status/'.$checkoutOrder->getId(),
        );

        $data = json_encode($arr);

        $options = array(
            'http' => array(
                'header' => "Content-Type: application/json\r\n"
                    ."gw-partner: $partnername\r\n"
                    ."gw-nonce: ".$nonce."\r\n"
                    ."gw-sign: ".$sig."\r\n",
                'method' => $method,
                'content' => $data
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($target_url, false, $context);

        $transactionId = $result;

        ##

        $transaction_id = $transactionId;
        $string=$partnername."_".$transaction_id;
        $secret=$this->indacoinSecret;
        $sig = base64_encode(base64_encode(hash_hmac('sha256', $string, $secret,true)));

        return 'https://indacoin.com/gw/payment_form?transaction_id=' . $transaction_id . '&partner=' . $partnername . '&cnfhash=' . $sig;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     * @throws AppException
     */
    public function obtainPaymentUrl(CheckoutOrder $checkoutOrder): string
    {
        return $this->createTransaction($checkoutOrder);

//        $transactionResponse = $this->createTransaction($checkoutOrder);
//        if($checkoutOrder->getId() !== $transactionResponse->transactionId) throw new AppException('Invalid transaction ID');
//
//        return $transactionResponse->redirectUrl;
    }
}
