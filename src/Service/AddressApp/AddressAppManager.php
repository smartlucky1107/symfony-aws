<?php

namespace App\Service\AddressApp;

class AddressAppManager
{
    /** @var AddressAppApi */
    private $api;

    /**
     * AddressAppManager constructor.
     * @param AddressAppApi $api
     */
    public function __construct(AddressAppApi $api)
    {
        $this->api = $api;
        $this->api->disableSSLVerification();
    }

    /**
     * @param string $btcAddress
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function validateBitcoinAddress(string $btcAddress)
    {
        $response = $this->api->doRequest('bitcoin/address/' . $btcAddress . '/validate', []);

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $btcAddress
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function validateBitcoinCashAddress(string $btcAddress)
    {
        $response = $this->api->doRequest('bitcoin-cash/address/' . $btcAddress . '/validate', []);

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $btcAddress
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function validateBitcoinSvAddress(string $btcAddress)
    {
        $response = $this->api->doRequest('bitcoin-sv/address/' . $btcAddress . '/validate', []);

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $ethAddress
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function validateEthereumAddress(string $ethAddress)
    {
        $response = $this->api->doRequest('ethereum/address/' . $ethAddress . '/validate', []);

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function generateBitcoinAddress()
    {
        $response = $this->api->doRequest('bitcoin/generate', [], 'POST');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function generateBitcoinCashAddress()
    {
        $response = $this->api->doRequest('bitcoin-cash/generate', [], 'POST');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function generateBitcoinSvAddress()
    {
        $response = $this->api->doRequest('bitcoin-sv/generate', [], 'POST');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function generateEthereumAddress()
    {
        $response = $this->api->doRequest('ethereum/generate', [], 'POST');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $smartContractAddress
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function generateEthereumErc20Address(string $smartContractAddress)
    {
        $response = $this->api->doRequest('ethereum/generate/' . $smartContractAddress, [], 'POST');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function downloadBitcoinTxs()
    {
        $this->api->disableSSLVerification();
        $response = $this->api->doRequest('bitcoin/txs');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function downloadBitcoinCashTxs()
    {
        $response = $this->api->doRequest('bitcoin-cash/txs');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function downloadBitcoinSvTxs()
    {
        $response = $this->api->doRequest('bitcoin-sv/txs');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function downloadEthereumTxs(){
        $response = $this->api->doRequest('ethereum/txs');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $txHash
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getEthereumTxBlockchainTx(string $txHash){
        $response = $this->api->doRequest('ethereum/tx/' . $txHash . '/blockchain-tx');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $txHash
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getBitcoinTxBlockchainTx(string $txHash){
        $response = $this->api->doRequest('bitcoin/tx/' . $txHash . '/blockchain-tx');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $txHash
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getBitcoinCashTxBlockchainTx(string $txHash){
        $response = $this->api->doRequest('bitcoin-cash/tx/' . $txHash . '/blockchain-tx');

        $this->api->resolveResponseErrors();

        return $response;
    }

    /**
     * @param string $txHash
     * @return mixed|null
     * @throws \App\Exception\ApiConnectionException
     */
    public function getBitcoinSvTxBlockchainTx(string $txHash){
        $response = $this->api->doRequest('bitcoin-sv/tx/' . $txHash . '/blockchain-tx');

        $this->api->resolveResponseErrors();

        return $response;
    }
}
