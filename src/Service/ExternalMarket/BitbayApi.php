<?php

namespace App\Service\ExternalMarket;

use App\Exception\ApiConnectionException;
use App\Exception\ApiException\BitbayException;
use App\Service\ApiClient;

class BitbayApi extends ApiClient
{
    const API_URL = 'https://api.bitbay.net/rest/';

    /** @var string */
    private $apiKeyPublic;

    /** @var string */
    private $apiKeyPrivate;

    /**
     * BitbayApi constructor.
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
     * @throws ApiConnectionException
     * @throws BitbayException
     */
    public function resolveResponseErrors() : void
    {
        if($this->httpCode === self::HTTP_OK){
            if(isset($this->response->errors)){
                throw new BitbayException($this->buildExceptionMessage([
                    join(', ', $this->response->errors)
                ]), BitbayException::BITBAY_CODES[$this->response->errors[0]]);
            }
        }

        parent::resolveResponseErrors();
    }

    /**
     * @param $data
     * @return string
     */
    private function getUUID($data){
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate and apply authorization headers based on $postFields
     *
     * @param array|null $postFields
     * @throws \Exception
     */
    public function updateAuthHeaders(array $postFields = null){
        $body    = is_array($postFields) ? json_encode($postFields) : '';
        $time    = time();
        $sign    = hash_hmac("sha512", $this->apiKeyPublic . $time . $body, $this->apiKeyPrivate);

        $this->setHttpHeaders([
            'API-Key: ' . $this->apiKeyPublic,
            'API-Hash: ' . $sign,
            'operation-id: ' . $this->getUUID(random_bytes(16)),
            'Request-Timestamp: ' . $time,
            'Content-Type: application/json'
        ]);
    }
}
