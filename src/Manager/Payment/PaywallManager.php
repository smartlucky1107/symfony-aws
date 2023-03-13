<?php

namespace App\Manager\Payment;

use App\Entity\CheckoutOrder;
use App\Entity\User;
use GuzzleHttp;
use App\Exception\AppException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PaywallManager implements PaymentProcessorInterface
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var RouterInterface */
    private $router;

    /** @var string */
    private $url;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $returnUrlTransaction;

    /** @var string */
    private $returnUrlCard;

    /**
     * PaywallManager constructor.
     * @param ParameterBagInterface $parameters
     * @param RouterInterface $router
     */
    public function __construct(ParameterBagInterface $parameters, RouterInterface $router)
    {
        $this->parameters = $parameters;
        $this->router = $router;

        $this->url      = $parameters->get('paywall_api_url');
        $this->apiKey   = $parameters->get('paywall_api_key');
        $this->returnUrlTransaction = $parameters->get('paywall_return_url_transaction');
        $this->returnUrlCard        = $parameters->get('paywall_return_url_card');
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     * @throws AppException
     */
    public function obtainPaymentUrl(CheckoutOrder $checkoutOrder) : string
    {
        $transactionResponse = $this->createTransaction($checkoutOrder);
        if($checkoutOrder->getId() !== $transactionResponse->transactionId) throw new AppException('Invalid transaction ID');

        return $transactionResponse->redirectUrl;
    }

    public function createTransaction(CheckoutOrder $checkoutOrder)
    {
        $returnUrl = $this->returnUrlTransaction . $checkoutOrder->getId();
        $notificationUrl = $this->router->generate('app_apipublic_payment_postpaywalltransactioncallback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $notificationUrl = str_replace('http://', 'https://', $notificationUrl);
//        $notificationUrl = str_replace('http://tokeneo.com.devo/', 'http://8be53f0e5ffe.ngrok.io/', $notificationUrl);

        $paywallCurrency = strtolower($checkoutOrder->getCurrencyPair()->getQuotedCurrency()->getShortName());
        if($paywallCurrency === 'pln' || $paywallCurrency === 'usd' || $paywallCurrency === 'usdt' || $paywallCurrency === 'eur'){
            if($paywallCurrency === 'usdt') $paywallCurrency = 'usd';
        }else{
            throw new AppException('Currency is not supported');
        }

        $data = [
            'userId'        => $checkoutOrder->getUser()->getUuid(),
            'cardId'        => $checkoutOrder->getPaymentCard()->getCardId(),
            'transactionId' => $checkoutOrder->getId(),
            'amount'        => (float) $checkoutOrder->toPrecisionQuoted($checkoutOrder->getTotalPaymentValue()),
            'currency'      => strtoupper($paywallCurrency),
            'locale'        => 'en',
            'returnUrl'     => $returnUrl,
            'notificationUrl' => $notificationUrl
        ];

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('POST', $this->url . 'transaction', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$this->apiKey,
                ],
                'json' => $data
            ]);
            $result = json_decode($response->getBody()->getContents());

            if(!isset($result->transactionId) || !isset($result->redirectUrl)) throw new AppException('Cannot register card');

            return $result;
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){
            // do nothing
        }catch (\Exception $exception){
            // do nothing
        }

        throw new AppException('Cannot register new transaction');
    }

    /**
     * @param User $user
     * @return mixed
     * @throws AppException
     */
    public function registerCard(User $user)
    {
        $returnUrl = $this->returnUrlCard;
        $notificationUrl = $this->router->generate('app_apipublic_payment_postpaywallregistercallback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $notificationUrl = str_replace('http://', 'https://', $notificationUrl);
//        $notificationUrl = str_replace('http://tokeneo.com.devo/', 'http://8be53f0e5ffe.ngrok.io/', $notificationUrl);

        $data = [
            'userId' => $user->getUuid(),
            'userDetails' => [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'locale' => 'en', // $user->getLocale()
                'email' => $user->getEmail(),
            ],
            'returnUrl' => $returnUrl,
            'notificationUrl' => $notificationUrl
        ];

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('POST', $this->url . 'register-card', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$this->apiKey,
                ],
                'json' => $data
            ]);
            $result = json_decode($response->getBody()->getContents());

            if(!isset($result->registrationId) || !isset($result->redirectUrl)) throw new AppException('Cannot register card');

            return $result;
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){
            // do nothing
        }catch (\Exception $exception){
            // do nothing
        }

        throw new AppException('Cannot register new card');
    }
}
