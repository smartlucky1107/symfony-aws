<?php

namespace App\Service;

interface ApiInterface
{
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Get response after json_decode
     * @return mixed
     */
    public function getResponse();

    /**
     * Get raw response from the API
     *
     * @return mixed
     */
    public function getRawResponse();

    /**
     * Get http code of the response
     *
     * @return int
     */
    public function getHttpCode(): int;

    /**
     * @param string $endpoint          Main endpoint for the connection
     * @param array|null $postFields    Array of parameters for POST
     * @param string $method            GET | POST
     * @return mixed
     */
    public function doRequest(string $endpoint, array $postFields = null, string $method = 'GET');

    /**
     * Resolve HTTP errors and throw specified exceptions depending on the Exception class
     */
    public function resolveResponseErrors() : void;
}