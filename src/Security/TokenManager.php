<?php

namespace App\Security;

use App\Exception\AppException;
use App\Model\TokenModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TokenManager
{
    const REDIS_LIST = 'revokedTokens';

    private $redisClient;

    /** @var int */
    private $ttl = 0;

    /**
     * TokenManager constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->redisClient = new \Redis();
        $this->redisClient->connect($parameters->get('redis_host'), $parameters->get('redis_port'));

        $this->ttl = $parameters->get('lexik_jwt_authentication.token_ttl');
    }

    /**
     * Add the $token to revoke tokens list in Redis for further blocking
     *
     * @param string $token
     * @throws AppException
     */
    public function revokeToken(string $token){
        /** @var TokenModel $tokenModel */
        $tokenModel = $this->findToken($token);
        if($tokenModel instanceof TokenModel) throw new AppException('Already revoked');

        $expiresAt = new \DateTime('now');
        $expiresAt->modify('+ ' . $this->ttl . ' seconds');

        $tokenModel = new TokenModel();
        $tokenModel->setToken($token);
        $tokenModel->setExpiresAt($expiresAt->format('c'));

        $this->redisClient->lPush(self::REDIS_LIST, json_encode($tokenModel));
    }

    /**
     * @param string $token
     * @return TokenModel|null
     */
    public function findToken(string $token) : ?TokenModel
    {
        $length = $this->redisClient->lLen(self::REDIS_LIST);
        if($length > 0){
            $tokens = $this->redisClient->lRange(self::REDIS_LIST, 0, $length);
            if($tokens){
                foreach($tokens as $tokenItem){
                    /** @var TokenModel $tokenModel */
                    $tokenModel = new TokenModel((array) json_decode($tokenItem));
                    if($tokenModel->isValid()){
                        if($tokenModel->getToken() === $token){
                            return $tokenModel;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param TokenModel $tokenModel
     * @throws \Exception
     */
    public function removeIfExpired(TokenModel $tokenModel)
    {
        /** @var \DateTime $nowDate */
        $nowDate = new \DateTime('now');
        /** @var \DateTime $expiresAt */
        $expiresAt = new \DateTime($tokenModel->getExpiresAt());

        if($nowDate > $expiresAt){
            // remove expired token
            $this->redisClient->lRem(self::REDIS_LIST, json_encode($tokenModel), 0);
        }
    }
}
