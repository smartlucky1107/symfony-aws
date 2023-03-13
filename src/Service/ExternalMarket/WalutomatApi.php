<?php

namespace App\Service\ExternalMarket;

use App\Service\ApiClient;

class WalutomatApi extends ApiClient
{
    /** @var string */
    private $apiKey;

    /**
     * WalutomatApi constructor.
     * @param string $apiUrl
     * @param string $apiKey
     */
    public function __construct(string $apiUrl, string $apiKey)
    {
        parent::__construct();

        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * Generate and apply authorization headers based on $params
     */
    public function updateAuthHeaders(){
        $this->setHttpHeaders([
            'X-API-Key: ' . $this->apiKey
        ]);
    }
}
