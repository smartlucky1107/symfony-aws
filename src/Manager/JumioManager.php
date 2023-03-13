<?php

namespace App\Manager;

use App\Entity\Verification;
use GuzzleHttp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JumioManager
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $jumioUrl;

    /** @var string */
    private $jumioToken;

    /** @var string */
    private $jumioSecret;

    /**
     * JumioManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;

        $this->jumioUrl = $parameters->get('jumio_url');
        $this->jumioToken = $parameters->get('jumio_token');
        $this->jumioSecret = $parameters->get('jumio_secret');
    }

    /**
     * @return string
     */
    private function generateBasicToken() : string
    {
        return base64_encode($this->jumioToken . ':' . $this->jumioSecret);
    }

    /**
     * @param Verification $verification
     * @param string $locale
     * @return array
     */
    public function initiate(Verification $verification, string $locale = 'en') : array
    {
        $result = [];

        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('POST', $this->jumioUrl, [
                'headers' => [
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'swapcoin.today'
                ],
                'auth' => [
                    $this->jumioToken, $this->jumioSecret
                ],
                'json' => [
                    'customerInternalReference' => $verification->getCustomerInternalReference(),
                    'userReference' => $verification->getUserReference(),
                    //'callbackUrl' => '' // Zdefiniowany domyslnie w panelu jumio - bo kieruje na front
                    "locale" => 'pl', //$locale
//                    'workflowId' => 201
                ]
            ]);
            $result = (array) json_decode($response->getBody()->getContents());
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){
        }

        return $result;
    }
}
