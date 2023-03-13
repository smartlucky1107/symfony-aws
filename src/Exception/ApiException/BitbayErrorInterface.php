<?php

namespace App\Exception\ApiException;

interface BitbayErrorInterface
{
    // standard error codes
    const CODE_MARKET_NOT_RECOGNIZED                    = 200201;
    const CODE_TICKER_NOT_FOUND                         = 200202;
    const CODE_NOT_RECOGNIZED_OFFER_TYPE                = 200203;
    const CODE_FUNDS_NOT_SUFFICIENT                     = 200204;
    const CODE_OFFER_FUNDS_NOT_EXCEEDING_MINIMUMS       = 200205;
    const CODE_OFFER_FUNDS_SCALE_ISSUE                  = 200206;
    const CODE_OFFER_COULD_NOT_BE_FILLED                = 200207;
    const CODE_OFFER_WOULD_HAVE_BEEN_PARTIALLY_FILLED   = 200208;
    const CODE_FILL_OR_KILL_COMBINED_WITH_POST_ONLY     = 200209;
    const CODE_INVALID_RESOLUTION                       = 200210;
    const CODE_OFFER_NOT_FOUND                          = 200211;
    const CODE_SECONDARY_AMOUNTONLY                     = 200212;
    const CODE_ALLOWED_WITH_MARKET_OFFER                = 200213;
    const CODE_SELF_TRADING                             = 200214;
    const CODE_PRICE_PRECISION_INVALID                  = 200215;
    const CODE_USER_OFFER_COUNT_LIMIT_EXCEEDED          = 200215;

    const BITBAY_CODES = [
        'FUNDS_NOT_SUFFICIENT' => self::CODE_FUNDS_NOT_SUFFICIENT
    ];

    const CODE_NAMES = [
        self::CODE_FUNDS_NOT_SUFFICIENT => 'Funds not sufficient'
    ];
}
