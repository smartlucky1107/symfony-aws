<?php

namespace App\Manager\Aml;

use GuzzleHttp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class iAmlManager
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $iAmlUrl;

    /** @var string */
    private $iAmlKey;

    /**
     * iAmlManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;

        $this->iAmlUrl = $parameters->get('iaml_api_url');
        $this->iAmlKey = $parameters->get('iaml_api_key');
    }

    /**
     * @param string $name
     * @return array|mixed
     */
    public function getPepInfo(string $name)
    {
        $client = new GuzzleHttp\Client();
        try{
            $params = [
                'name' => $name
            ];

            $response = $client->request('GET', $this->iAmlUrl . 'peps?' . GuzzleHttp\Psr7\build_query($params), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => $this->iAmlKey
                ]
            ]);
            $result = (array) json_decode($response->getBody()->getContents());
            if(isset($result['peps'])) return $result['peps'];
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){
        }

        return [];
    }
}
