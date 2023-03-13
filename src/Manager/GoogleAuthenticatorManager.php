<?php

namespace App\Manager;

use App\Exception\AppException;
use Symfony\Component\HttpFoundation\Request;

class GoogleAuthenticatorManager
{
    const REQUEST_PARAM_NAME = 'g-auth-code';
    const ISSUER = 'KryptowalutyAuth';

    /** @var \Sonata\GoogleAuthenticator\GoogleAuthenticator */
    protected $ga;

    /** @var string */
    protected $paramName;

    /** @var string */
    protected $issuer;

    /**
     * GoogleAuthenticatorManager constructor.
     */
    public function __construct()
    {
        $this->ga = new \Google\Authenticator\GoogleAuthenticator();
        $this->paramName = self::REQUEST_PARAM_NAME;
        $this->issuer = self::ISSUER;
    }

    /**
     * @param string $secret
     * @param Request $request
     * @return bool
     * @throws AppException
     */
    public function verifyRequest(string $secret, Request $request) : bool
    {
        $code = (string) $request->request->get($this->paramName, '');

        if(!$this->checkCode($secret, $code)) throw new AppException('Invalid Google Authentication code');

        return true;
    }

    /**
     * @param string $secret
     * @param string $code
     * @return bool
     * @throws AppException
     */
    public function verifyCode(string $secret, string $code) : bool
    {
        if(!$this->checkCode($secret, $code)) throw new AppException('Invalid Google Authentication code');

        return true;
    }

    /**
     * @return string
     */
    public function generateSecret() : string
    {
        return $this->ga->generateSecret();
    }

    /**
     * @param string $username
     * @param string $secret
     * @return string
     */
    public function generateQrUrl(string $username, string $secret) : string
    {
        return \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($username, $secret, $this->issuer);
    }

    /**
     * @param string $secret
     * @param string $code
     * @return bool
     */
    public function checkCode(string $secret, string $code) : bool
    {
        if($this->ga->checkCode($secret, $code)){
            return true;
        }

        return false;
    }
}
