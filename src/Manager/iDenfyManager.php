<?php

namespace App\Manager;

use App\Entity\Verification;
use GuzzleHttp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class iDenfyManager
{
    /** @var string */
    private $iDenfyUrl;

    /** @var string */
    private $iDenfyKey;

    /** @var string */
    private $iDenfySecret;

    /**
     * iDenfyManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->iDenfyUrl = $parameters->get('idenfy_url');
        $this->iDenfyKey = $parameters->get('idenfy_key');
        $this->iDenfySecret = $parameters->get('idenfy_secret');
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
            $response = $client->request('POST', $this->iDenfyUrl, [
                'auth' => [
                    $this->iDenfyKey, $this->iDenfySecret
                ],
                'json' => [
                    'clientId' => $verification->getUser()->getId()
                ]
            ]);

            $result = (array) json_decode($response->getBody()->getContents());
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){

        }

        return $result;
    }
}
