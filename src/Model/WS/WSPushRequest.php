<?php

namespace App\Model\WS;

use App\Server\AppWebsocketInterface;

class WSPushRequest extends WSMessage
{
    /**
     * WSPushRequest constructor.
     * @param string $module
     * @param array $data
     * @param int|null $userId
     * @param string|null $currencyPairShortName
     */
    public function __construct(string $module, array $data, int $userId = null, string $currencyPairShortName = null)
    {
        $this->action = AppWebsocketInterface::ACTION_PUSH;
        $this->module = $module;
        $this->data = $data;

        if(!is_null($userId)) $this->setUserId($userId);
        if(!is_null($currencyPairShortName)) $this->setCurrencyPairShortName($currencyPairShortName);

        parent::__construct([]);
    }
}