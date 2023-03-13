<?php

namespace App\Model;

interface SystemUserInterface
{
    const FEE_USER          = 101;
    const CHECKOUT_FEE_USER = 102;

//    const SUPER_ADMIN_USER  = 666;

    const CHECKOUT_LIQ_USER     = 10000;
    const BITBAY_LIQ_USER       = 10001;
    const BINANCE_LIQ_USER      = 10002;
    const KRAKEN_LIQ_USER       = 10003;
    const WALUTOMAT_LIQ_USER    = 10004;

    const FEE_USERS = [
        self::FEE_USER,
        self::CHECKOUT_FEE_USER
    ];

    const LIQ_USER = [
        self::CHECKOUT_LIQ_USER,
        self::BITBAY_LIQ_USER,
        self::BINANCE_LIQ_USER,
        self::KRAKEN_LIQ_USER,
        self::WALUTOMAT_LIQ_USER,
    ];
}
