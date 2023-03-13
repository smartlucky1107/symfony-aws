<?php

namespace App\Entity\Payment;

interface PaywallTransactionInterface
{
    const STATUS_NEW        = 'NEW';
    const STATUS_PENDING    = 'PENDING';
    const STATUS_WAITING_ON_3DS_CONFIRMATION    = 'WAITING_ON_3DS_CONFIRMATION';
    const STATUS_SETTLED    = 'SETTLED';
    const STATUS_REJECTED   = 'REJECTED';
    const STATUS_ERROR      = 'ERROR';
    const ALLOWED_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PENDING,
        self::STATUS_WAITING_ON_3DS_CONFIRMATION,
        self::STATUS_SETTLED,
        self::STATUS_REJECTED,
        self::STATUS_ERROR,
    ];
}
