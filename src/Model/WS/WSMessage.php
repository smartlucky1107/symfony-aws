<?php

namespace App\Model\WS;

class WSMessage
{
    /** @var string */
    public $action;

    /** @var string */
    public $module;

    /** @var mixed */
    public $data;

    /** @var int|null */
    public $userId;

    /** @var string|null */
    public $userWsHash;

    /** @var string|null */
    public $currencyPairShortName;

    /** @var string|null */
    public $currencyShortName;

    /**
     * WSMessage constructor.
     * @param array $array
     */
    public function __construct(array $array)
    {
        if(isset($array['action'])) $this->setAction((string) $array['action']);
        if(isset($array['module'])) $this->setModule((string) $array['module']);
        if(isset($array['data'])) $this->setData($array['data']);

        if(isset($array['userId'])) $this->setUserId($array['userId']);
        if(isset($array['userWsHash'])) $this->setUserWsHash($array['userWsHash']);
        if(isset($array['currencyPairShortName'])) $this->setCurrencyPairShortName($array['currencyPairShortName']);
        if(isset($array['currencyShortName'])) $this->setCurrencyShortName($array['currencyShortName']);
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        if($this->action && $this->module){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string $module
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string|null
     */
    public function getCurrencyPairShortName(): ?string
    {
        return $this->currencyPairShortName;
    }

    /**
     * @param string|null $currencyPairShortName
     */
    public function setCurrencyPairShortName(?string $currencyPairShortName): void
    {
        $this->currencyPairShortName = $currencyPairShortName;
    }

    /**
     * @return string|null
     */
    public function getCurrencyShortName(): ?string
    {
        return $this->currencyShortName;
    }

    /**
     * @param string|null $currencyShortName
     */
    public function setCurrencyShortName(?string $currencyShortName): void
    {
        $this->currencyShortName = $currencyShortName;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return string|null
     */
    public function getUserWsHash(): ?string
    {
        return $this->userWsHash;
    }

    /**
     * @param string|null $userWsHash
     */
    public function setUserWsHash(?string $userWsHash): void
    {
        $this->userWsHash = $userWsHash;
    }
}