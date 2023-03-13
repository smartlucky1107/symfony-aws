<?php

namespace App\Manager\Payment;

use App\Entity\CheckoutOrder;
use App\Exception\AppException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Przelewy24Manager implements PaymentProcessorInterface
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var RouterInterface */
    private $router;

    /** @var string */
    private $url;

    /** @var string */
    private $orderKey;

    /** @var string */
    private $crc;

    /** @var string */
    private $merchantId;

    /** @var string */
    private $posId;

    /** @var string */
    private $reportKey;

    /** @var string */
    private $paymentBaseUrl;

    /** @var string */
    private $returnBaseUrl;

    /**
     * Przelewy24Manager constructor.
     * @param ParameterBagInterface $parameters
     * @param RouterInterface $router
     */
    public function __construct(ParameterBagInterface $parameters, RouterInterface $router)
    {
        $this->parameters = $parameters;
        $this->router = $router;

        $this->url = $parameters->get('przelewy24_url');
        $this->orderKey = $parameters->get('przelewy24_order_key');
        $this->crc = $parameters->get('przelewy24_crc');
        $this->merchantId = $parameters->get('przelewy24_merchant_id');
        $this->posId = $parameters->get('przelewy24_pos_id');
        $this->reportKey = $parameters->get('przelewy24_report_key');
        $this->paymentBaseUrl = $parameters->get('przelewy24_payment_base_url');
        $this->returnBaseUrl = $parameters->get('przelewy24_return_base_url');
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     * @throws AppException
     */
    private function obtainPaymentToken(CheckoutOrder $checkoutOrder) : string
    {
        $urlStatus = $this->router->generate('app_apipublic_payment_postprzelewy24callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $urlReturn = $this->returnBaseUrl . 'status/' . $checkoutOrder->getId();

//        $urlStatus = 'http://f46dbb2a4dd7.ngrok.io/index.php/api-public/payment/przelewy24-callback';

        $data = [
            'merchantId'    => (int) $this->merchantId,
            'posId'         => (int) $this->posId,
            'sessionId'     => $checkoutOrder->getId(),
            'urlStatus'     => $urlStatus,
            'description'   => 'Oplata za wymiane: ' . $checkoutOrder->getAmount() . ' ' . $checkoutOrder->getCurrencyPair()->getBaseCurrency()->getShortName(),
            'currency'      => $checkoutOrder->getCurrencyPair()->getQuotedCurrency()->getShortName(),
            'amount'        => (int) ($checkoutOrder->getTotalPaymentValue() * 100),
            'urlReturn'     => $urlReturn,
            'email'         => $checkoutOrder->getUser()->getEmail(),
            'country'       => 'PL',
            'language'      => 'pl',
            'timeLimit'     => 5
        ];
        $data['sign'] = hash('sha384',
            json_encode([
                'sessionId'     => $data['sessionId'],
                'merchantId'    => intval($data['merchantId']),
                'amount'        => intval($data['amount']),
                'currency'      => $data['currency'],
                'crc'           => $this->crc
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('POST', $this->url . 'transaction/register', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($this->posId.':'.$this->reportKey)
                ],
                'json' => $data
            ]);
            $result = json_decode($response->getBody()->getContents());
            if(!(isset($result->responseCode) && $result->responseCode === 0 && isset($result->data->token))) throw new AppException('Cannot obtain payment token');

            return $result->data->token;
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){}

        throw new AppException('Cannot obtain payment token');
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     * @throws AppException
     */
    public function obtainPaymentUrl(CheckoutOrder $checkoutOrder) : string
    {
        $token = $this->obtainPaymentToken($checkoutOrder);

        return $this->paymentBaseUrl . $token;
    }

    /**
     * @param array $data
     * @return bool
     * @throws AppException
     */
    public function verifySign(array $data): bool
    {
        $sign = hash('sha384',
            json_encode([
                'merchantId'    => (int) $this->merchantId,
                'posId'         => (int) $this->posId,
                'sessionId'     => (string) $data['sessionId'],
                'amount'        => (int) $data['amount'],
                'originAmount'  => (int) $data['originAmount'],
                'currency'      => (string) $data['currency'],
                'orderId'       => (int) $data['orderId'],
                'methodId'      => (int) $data['methodId'],
                'statement'     => (string) $data['statement'],
                'crc'           => (string) $this->crc,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if($sign !== $data['sign']) throw new AppException('Invalid sign');

        return true;
    }

    /**
     * @param array $data
     * @return bool
     * @throws AppException
     */
    public function verifyTransaction(array $data) : bool
    {
        $verifyData = [
            'merchantId'    => $data['merchantId'],
            'posId'         => $data['posId'],
            'sessionId'     => $data['sessionId'],
            'amount'        => $data['amount'],
            'currency'      => $data['currency'],
            'orderId'       => $data['orderId'],
        ];
        $verifyData['sign'] = hash('sha384',
            json_encode([
                'sessionId' => $data['sessionId'],
                'orderId'   => $data['orderId'],
                'amount'    => $data['amount'],
                'currency'  => $data['currency'],
                'crc'       => $this->crc
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('PUT', $this->url . 'transaction/verify', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($this->posId.':'.$this->reportKey)
                ],
                'json' => $verifyData
            ]);
            $result = json_decode($response->getBody()->getContents());

            if(!(isset($result->responseCode) && $result->responseCode === 0 && isset($result->data->status) && $result->data->status === 'success')) throw new AppException('Cannot obtain payment token');

            return true;
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){}

        throw new AppException('Verification failed');
    }
}
