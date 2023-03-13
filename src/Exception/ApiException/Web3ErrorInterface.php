<?php

namespace App\Exception\ApiException;

interface Web3ErrorInterface
{
    const CODE_RESULT_ERROR = 200301;

    const CODE_NAMES = [
        self::CODE_RESULT_ERROR => 'General result error'
    ];
}
