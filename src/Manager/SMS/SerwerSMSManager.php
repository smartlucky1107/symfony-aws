<?php

namespace App\Manager\SMS;

use GuzzleHttp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SerwerSMSManager
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $serwerSMSUrl;

    /** @var string */
    private $serwerSMSUsername;

    /** @var string */
    private $serwerSMSPassword;

    /**
     * SerwerSMSManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;

        $this->serwerSMSUrl       = $parameters->get('serwer_sms_url');
        $this->serwerSMSUsername  = $parameters->get('serwer_sms_username');
        $this->serwerSMSPassword  = $parameters->get('serwer_sms_password');
    }

    /**
     * @param string $phone
     * @param string $message
     */
    public function sendSMS(string $phone, string $message)
    {
        $client = new GuzzleHttp\Client();
        try{
            $response = $client->request('POST', $this->serwerSMSUrl . 'messages/send_sms', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'username'  => $this->serwerSMSUsername,
                    'password'  => $this->serwerSMSPassword,
                    'phone'     => $phone,
                    'text'      => $message,
                    'sender'    => 'swapcoin'
                ]
            ]);
            $result = (array) json_decode($response->getBody()->getContents());
        }catch (GuzzleHttp\Exception\GuzzleException $guzzleException){
        }
    }
}
