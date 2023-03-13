<?php

namespace App\Service\MailerLite;

use App\Service\ApiClient;

class MailerLiteApi extends ApiClient
{
    const API_URL = 'https://api.mailerlite.com/api/v2/';

    /** @var string */
    private $apiKey;

    /**
     * MailerLiteApi constructor.
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->apiUrl = self::API_URL;
    }

    public function updateAuthHeaders(): void
    {
        $this->setHttpHeaders([
            'Content-Type: application/json',
            'X-MailerLite-ApiKey: ' . $this->apiKey,
        ]);
    }
}
