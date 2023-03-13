<?php

namespace App\Service\AddressApp;

use App\Service\ApiClient;

class AddressAppApi extends ApiClient
{
    /**
     * AddressAppApi constructor.
     * @param string $apiUrl
     * @param string $apiKey
     */
    public function __construct(string $apiUrl, string $apiKey)
    {
        parent::__construct();

        $this->apiUrl = $apiUrl;

        $this->addHttpHeader('Authorization', 'apiKey '.$apiKey);
    }
}