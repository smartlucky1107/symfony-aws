<?php

namespace App\Manager;

use App\Exception\AppException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class reCaptchaManager
{
    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $secretKeyV2;

    /** @var string */
    private $secretKeyV3;

    const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const REQUEST_PARAM_NAME = 'g-recaptcha-response';

    /**
     * reCaptchaManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;

        $this->secretKeyV2 = $parameters->get('recaptcha_v2_secret_key');
        $this->secretKeyV3 = $parameters->get('recaptcha_v3_secret_key');
    }

    /**
     * @return string
     */
    private function resolveSecret() : string
    {
        return $this->secretKeyV2;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws AppException
     */
    public function verifyRequest(Request $request) : bool
    {
        $recaptchaResponse = (string) $request->get(self::REQUEST_PARAM_NAME, '');

        if(!$this->isVerified($recaptchaResponse)) throw new AppException('Invalid reCaptcha');

        return true;
    }

    /**
     * @param $recaptchaResponse
     * @return bool
     */
    public function isVerified($recaptchaResponse) : bool
    {
        $params = [
            'secret'    => $this->resolveSecret(),
            'response'  => $recaptchaResponse
        ];

        $verifyResponse = file_get_contents(self::RECAPTCHA_URL . '?' . http_build_query($params));

        $responseData = json_decode($verifyResponse);
        if(isset($responseData->success) && $responseData->success === true){
            return true;
        }

        return false;
    }
}
