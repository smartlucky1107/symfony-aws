<?php

namespace App\Model\WS;

class WSModuleExternalOrderbook implements WSModuleInterface
{
    /** @var string */
    private $currencyPairShortName;

    /**
     * WSModuleExternalOrderbook constructor.
     * @param string $currencyPairShortName
     */
    public function __construct(string $currencyPairShortName)
    {
        $this->currencyPairShortName = $currencyPairShortName;
    }

    /**
     * @param $matchParam
     * @return bool
     */
    public function isMatched($matchParam) : bool
    {
        if($this->currencyPairShortName === $matchParam){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getCurrencyPairShortName(): string
    {
        return $this->currencyPairShortName;
    }

    /**
     * @param string $currencyPairShortName
     */
    public function setCurrencyPairShortName(string $currencyPairShortName): void
    {
        $this->currencyPairShortName = $currencyPairShortName;
    }
}