<?php

namespace App\Service;

use App\Exception\ApiConnectionException;

class ApiClient implements ApiInterface
{
    /** @var string */
    protected $apiUrl;

    protected $authUser     = null;
    protected $authPassword = null;

    protected $httpHeaders  = [];
    protected $postFields   = [];
    protected $response     = null;
    protected $rawResponse  = null;

    /** @var string */
    protected $requestUrl;

    /** @var int */
    protected $httpCode;

    /** @var bool */
    protected $SSLVerification = true;

    /**
     * ApiClient constructor.
     */
    public function __construct()
    {
        $this->clearHttpHeaders();
        $this->addHttpHeader('Content-Type', 'application/json');
    }

    /**
     * Initialize authorization data for the connection
     *
     * @param string $user
     * @param string $password
     */
    public function initAuthorization(string $user, string $password){
        $this->authUser = $user;
        $this->authPassword = $password;
    }

    public function disableSSLVerification() : void
    {
        $this->SSLVerification = false;
    }

    /**
     * @param string $endpoint
     * @param array|null $postFields
     * @param string $method
     * @param array|null $postData
     * @return mixed|null
     */
    public function doRequest(string $endpoint, array $postFields = null, string $method = 'GET', array $postData = null)
    {
        $this->requestUrl = $this->apiUrl.$endpoint;

        if(is_array($postData)){
            $this->postFields = $postData;

            $data = http_build_query($postData);
        }else{
            $this->postFields = $postFields;

            $data = '';
            if(!is_null($this->postFields)) {
                $data = json_encode($postFields, 320);
            }
        }

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL             => $this->requestUrl,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
//            CURLOPT_TIMEOUT         => 100,
            CURLOPT_ENCODING        => '',
            CURLOPT_HTTPHEADER      => $this->httpHeaders
//            CURLOPT_MAXREDIRS       => 10,
//            CURLOPT_CUSTOMREQUEST   => 'POST',
        ));

        //curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        //curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //curl_setopt ($ch, CURLOPT_CAINFO, "ca-bundle.crt"); //http://curl.haxx.se/docs/caextract.html

        if(!is_null($this->authUser) && !is_null($this->authPassword)){
            curl_setopt ($ch, CURLOPT_USERPWD, $this->authUser.':'.$this->authPassword);
        }

        if($data){
            curl_setopt ($ch, CURLOPT_POST, true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
        }

        if($method === 'POST'){
            curl_setopt ($ch, CURLOPT_POST, true);
        }

        if(!$this->SSLVerification){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

//        dump($ch);

        $this->rawResponse = curl_exec($ch); // API raw response
        $this->httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->response    = json_decode($this->rawResponse);

//        dump($this->rawResponse);
//        exit;

        curl_close($ch);

        return $this->response;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function exceptionMessageItems() : array
    {
        return [
            (new \DateTime('now'))->format('Y-m-d H:i:s'),
            $this->httpCode,
            $this->requestUrl,
            json_encode($this->postFields)
        ];
    }

    /**
     * @param array $items
     * @return string
     * @throws \Exception
     */
    protected function buildExceptionMessage(array $items = []) : string
    {
        $merged = array_merge($this->exceptionMessageItems(), $items);

        return join(' | ', $merged);
    }

    /**
     * @throws ApiConnectionException
     */
    public function resolveResponseErrors() : void
    {
        if($this->httpCode === self::HTTP_INTERNAL_SERVER_ERROR){
            throw new ApiConnectionException($this->buildExceptionMessage(), $this->httpCode);
        }elseif($this->httpCode === self::HTTP_OK){
            // no errors
        }elseif($this->httpCode === self::HTTP_CREATED){
            // no errors
        }else{
            throw new ApiConnectionException($this->buildExceptionMessage(), $this->httpCode);
        }
    }

    /**
     * Clear headers for the connection
     */
    public function clearHttpHeaders() : void
    {
        $this->httpHeaders  = [];
    }

    /**
     * Add headers item for the connection
     *
     * @param string $value
     * @param string $key
     */

    public function addHttpHeader(string $value, string $key) : void
    {
        $this->httpHeaders[] = $value.': '.$key;
    }

    /**
     * Set headers
     *
     * @param array $headers
     */
    public function setHttpHeaders(array $headers = []){
        $this->httpHeaders = $headers;
    }

    /**
     * @return null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return null
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @param string $requestUrl
     */
    public function setRequestUrl(string $requestUrl): void
    {
        $this->requestUrl = $requestUrl;
    }
}
