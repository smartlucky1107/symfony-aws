<?php

namespace App\Event;

use App\Model\WS\WSPushRequest;
use Symfony\Component\EventDispatcher\Event;

class WSPushEvent extends Event
{
    public const NAME = 'websocket.on_push';

    /** @var WSPushRequest */
    protected $wsPushRequest;

    public function __construct(WSPushRequest $wsPushRequest)
    {
        $this->wsPushRequest = $wsPushRequest;
    }

    /**
     * @return WSPushRequest
     */
    public function getWsPushRequest(): WSPushRequest
    {
        return $this->wsPushRequest;
    }
}