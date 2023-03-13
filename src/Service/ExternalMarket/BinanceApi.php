<?php

namespace App\Service\ExternalMarket;

use App\Exception\ApiConnectionException;
use App\Service\ApiClient;

class BinanceApi extends ApiClient
{
    const API_URL = 'https://api.binance.com/';

    /** @var string */
    private $apiKeyPublic;

    /** @var string */
    private $apiKeyPrivate;

    /**
     * BinanceApi constructor.
     * @param string $apiKeyPublic
     * @param string $apiKeyPrivate
     */
    public function __construct(string $apiKeyPublic, string $apiKeyPrivate)
    {
        parent::__construct();

        $this->apiKeyPublic = $apiKeyPublic;
        $this->apiKeyPrivate = $apiKeyPrivate;

        $this->apiUrl = self::API_URL;
    }

    /**
     * @param array $params
     * @return string
     */
    public function sign(array $params) : string
    {
        return hash_hmac("sha256", http_build_query($params), $this->apiKeyPrivate);
    }

    /**
     * Generate and apply authorization headers based on $params
     *
     * @param array $params
     */
    public function updateAuthHeaders(array $params){
        $this->setHttpHeaders([
            'X-MBX-APIKEY: ' . $this->apiKeyPublic,
//            'Content-Type: application/json'
        ]);
    }
}
