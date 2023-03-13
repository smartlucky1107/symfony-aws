<?php

namespace App\Server;

interface AppWebsocketInterface
{
    const ACTION_SUBSCRIBE  = 'subscribe';
    const ACTION_UNSUBSCRIBE  = 'unsubscribe';
    const ACTION_PUSH       = 'push';
    const ACTIONS = [
        self::ACTION_SUBSCRIBE  => self::ACTION_SUBSCRIBE,
        self::ACTION_UNSUBSCRIBE=> self::ACTION_UNSUBSCRIBE,
        self::ACTION_PUSH       => self::ACTION_PUSH
    ];

    const MODULE_ORDERBOOK      = 'orderbook';
    const MODULE_EXTERNAL_ORDERBOOK   = 'externalOrderbook';
    const MODULE_NOTIFICATIONS  = 'notifications';
    const MODULES = [
        self::MODULE_ORDERBOOK      => self::MODULE_ORDERBOOK,
        self::MODULE_EXTERNAL_ORDERBOOK   => self::MODULE_EXTERNAL_ORDERBOOK,
        self::MODULE_NOTIFICATIONS  => self::MODULE_NOTIFICATIONS,
    ];
}
