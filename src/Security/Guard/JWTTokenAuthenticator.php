<?php

namespace App\Security\Guard;

use App\Model\TokenModel;
use App\Security\TokenManager;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator as BaseAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor;
use Symfony\Component\HttpFoundation\Request;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;

class JWTTokenAuthenticator extends BaseAuthenticator
{
    /** @var TokenManager */
    private $tokenManager;

    /**
     * @param TokenManager $tokenManager
     */
    public function setTokenManager(TokenManager $tokenManager): void
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getCredentials(Request $request)
    {
        return parent::getCredentials($request);

        $tokenExtractor = $this->getTokenExtractor();

        $jsonWebToken = $tokenExtractor->extract($request);

        /** @var TokenModel $tokenModel */
        $tokenModel = $this->tokenManager->findToken($jsonWebToken);
        if($tokenModel instanceof TokenModel){
            $this->tokenManager->removeIfExpired($tokenModel);

            throw new InvalidTokenException('Invalid Token');
        }

        return parent::getCredentials($request);
    }
}
