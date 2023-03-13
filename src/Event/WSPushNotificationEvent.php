<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

class WSPushNotificationEvent extends Event
{
    public const NAME = 'websocket.on_push_notification';

    protected $notification;

    /**
     * WSPushNotificationEvent constructor.
     * @param $notification
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    /**
     * @return mixed
     */
    public function getNotification()
    {
        return $this->notification;
    }
}