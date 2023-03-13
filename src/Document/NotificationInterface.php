<?php

namespace App\Document;

interface NotificationInterface
{
    // orders
    const TYPE_ORDER_CREATED                = 101;
    const TYPE_ORDER_UPDATED                = 102;
    const TYPE_ORDER_FILLED                 = 104;
    const TYPE_ORDER_PARTLY_FILLED          = 105;
    const TYPE_ORDER_REJECTED               = 106;

    // user
    const TYPE_USER_REGISTERED              = 301;
    const TYPE_USER_EMAIL_CONFIRMED         = 303;
    const TYPE_USER_BANK_ACCOUNT_APPROVED   = 305;
    const TYPE_USER_PASSWORD_REQUEST        = 306;

    const TYPE_USER_TIER2_APPROVED          = 310;
    const TYPE_USER_TIER3_APPROVED          = 311;
    const TYPE_USER_PHONE_CONFIRMED         = 312;

    const TYPE_USER_TIER2_DECLINED          = 313;
    const TYPE_USER_TIER3_DECLINED          = 314;

    // withdrawal
    const TYPE_WITHDRAWAL_CREATED           = 401;
    const TYPE_WITHDRAWAL_APPROVED          = 403;
    const TYPE_WITHDRAWAL_DECLINED          = 404;
    const TYPE_WITHDRAWAL_REJECTED          = 406;

    // deposit
    const TYPE_DEPOSIT_ACCEPTED             = 601;

    // internal transfer
    const TYPE_INTERNAL_TRANSFER_CREATED    = 701;
    const TYPE_INTERNAL_TRANSFER_APPROVED   = 703;
    const TYPE_INTERNAL_TRANSFER_DECLINED   = 704;
    const TYPE_INTERNAL_TRANSFER_REJECTED   = 706;

    const ALLOWED_TYPES_USER = [
        self::TYPE_USER_REGISTERED,
        self::TYPE_USER_EMAIL_CONFIRMED,
        self::TYPE_USER_BANK_ACCOUNT_APPROVED,
        self::TYPE_USER_TIER2_APPROVED,
        self::TYPE_USER_TIER3_APPROVED,
        self::TYPE_USER_PHONE_CONFIRMED,
    ];

    /**
     * Check if passed $type is allowed for notification
     *
     * @param int $type
     * @return bool
     */
    public function isTypeAllowed(int $type) : bool;
}
