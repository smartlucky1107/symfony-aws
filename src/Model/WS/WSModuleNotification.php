<?php

namespace App\Model\WS;

class WSModuleNotification implements WSModuleInterface
{
    /** @var int */
    private $userId;

    /**
     * WSModuleNotification constructor.
     * @param int $userId
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param $matchParam
     * @return bool
     */
    public function isMatched($matchParam) : bool
    {
        if($this->userId === $matchParam){
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}