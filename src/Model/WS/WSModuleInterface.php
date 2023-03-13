<?php

namespace App\Model\WS;

interface WSModuleInterface
{
    /**
     * @param $matchParam
     * @return bool
     */
    public function isMatched($matchParam) : bool;
}