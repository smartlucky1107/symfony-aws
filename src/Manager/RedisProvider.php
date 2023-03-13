<?php

namespace App\Manager;

class RedisProvider
{
    /** @var \Redis */
    private $redis;

    /**
     * RedisProvider constructor.
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @return \Redis
     */
    public function getRedis(): \Redis
    {
        return $this->redis;
    }
}