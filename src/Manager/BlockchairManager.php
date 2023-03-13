<?php

namespace App\Manager;

use App\Exception\AppException;
use GuzzleHttp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BlockchairManager
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $blockchairUrl;

    /** @var string */
    private $blockchairKey;

    /**
     * BlockchairManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;

        $this->blockchairUrl = $parameters->get('blockchair_url');
        $this->blockchairKey = $parameters->get('blockchair_key');
    }

    /**
     * @param string $chain
     * @return bool
     */
    private function isChainAllowed(string $chain) : bool
    {
        if(in_array($chain, BlockchairInterface::ALLOWED_CHAINS)){
            return true;
        }

        return false;
    }

    /**
     * @param string $blockchain
     * @param string $address
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array
     * @throws AppException
     */
    public function loadTransactions(string $blockchain, string $address, \DateTime $from = null, \DateTime $to = null){
        if(!$this->isChainAllowed($blockchain)) throw new AppException('Chain is not allowed');

        $resultTransactions = [];

        $client = new GuzzleHttp\Client();
        try{
            if($blockchain === BlockchairInterface::CHAIN_BITCOIN){
                $response = $client->request('GET', $this->blockchairUrl . BlockchairInterface::CHAIN_BITCOIN . '/dashboards/address/' . $address . '?key=' . $this->blockchairKey . '&transaction_details=true&limit=10000');
                $result = (array) json_decode($response->getBody()->getContents());

                if(isset($result['data']) && isset($result['data']->{$address})){
                    if(isset($result['data']->{$address}->transactions)){
                        foreach($result['data']->{$address}->transactions as $transaction){
                            try{
                                /** @var \DateTime $transactionDate */
                                $transactionDate = new \DateTime($transaction->time);
                            }catch (\Exception $exception){
                                $transactionDate = null;
                            }

                            if(!is_null($from) && $from instanceof \DateTime){
                                if(!($transactionDate instanceof \DateTime && $transactionDate > $from)){
                                    continue;
                                }
                            }

                            if(!is_null($to) && $to instanceof \DateTime){
                                if(!($transactionDate instanceof \DateTime && $transactionDate < $to)){
                                    continue;
                                }
                            }

                            $resultTransactions[] = [
                                'hash' => $transaction->hash,
                                'time' => $transaction->time,
                                'amount' => bcdiv($transaction->balance_change, 100000000, 8)
                            ];
                        }
                    }

                }
            }elseif($blockchain === BlockchairInterface::CHAIN_ETHEREUM){
//                $response = $client->request('GET', $this->blockchairUrl . BlockchairInterface::CHAIN_ETHEREUM . '/dashboards/address/' . $address . '?key=' . $this->blockchairKey);
            }elseif($blockchain === BlockchairInterface::CHAIN_ETHEREUM_ERC20){
//                $response = $client->request('GET', $this->blockchairUrl . BlockchairInterface::CHAIN_ETHEREUM . '/dashboards/address/' . $address . '?key=' . $this->blockchairKey . '&erc_20=true');
            }else{
                throw new AppException('Chain is not allowed');
            }
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){

        }

        return $resultTransactions;
    }

    public function getTransaction(string $chain, string $transaction) : array
    {
        if(!$this->isChainAllowed($chain)) throw new AppException('Chain is not allowed');



    }
}
