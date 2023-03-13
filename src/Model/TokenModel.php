<?php

namespace App\Model;

class TokenModel
{
    /** @var string */
    public $token;

    /** @var string */
    public $expiresAt;

    /**
     * TokenModel constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['token'])) $this->setToken($data['token']);
        if(isset($data['expiresAt'])) $this->setExpiresAt($data['expiresAt']);
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->token && $this->expiresAt){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getExpiresAt(): string
    {
        return $this->expiresAt;
    }

    /**
     * @param string $expiresAt
     */
    public function setExpiresAt(string $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }
}