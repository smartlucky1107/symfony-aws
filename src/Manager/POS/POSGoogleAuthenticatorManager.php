<?php

namespace App\Manager\POS;

use App\Manager\GoogleAuthenticatorManager;

class POSGoogleAuthenticatorManager extends GoogleAuthenticatorManager
{
    const REQUEST_PARAM_NAME = 'g-auth-code';
    const ISSUER = 'KryptowalutyPOS';

    /**
     * POSGoogleAuthenticatorManager constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->paramName = self::REQUEST_PARAM_NAME;
        $this->issuer = self::ISSUER;
    }
}
