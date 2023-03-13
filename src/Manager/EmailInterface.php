<?php

namespace App\Manager;

use App\Document\NotificationInterface;

interface EmailInterface extends NotificationInterface {
    const TYPES_ALLOWED = [
        self::TYPE_USER_REGISTERED,
        self::TYPE_USER_EMAIL_CONFIRMED,
        self::TYPE_USER_BANK_ACCOUNT_APPROVED,
        self::TYPE_USER_PASSWORD_REQUEST,

        self::TYPE_USER_TIER2_APPROVED,
        self::TYPE_USER_TIER3_APPROVED,

        self::TYPE_USER_TIER2_DECLINED,
        self::TYPE_USER_TIER3_DECLINED,

        self::TYPE_WITHDRAWAL_CREATED,
        self::TYPE_WITHDRAWAL_APPROVED,
        self::TYPE_WITHDRAWAL_DECLINED,
        self::TYPE_WITHDRAWAL_REJECTED,

        self::TYPE_INTERNAL_TRANSFER_CREATED,
        self::TYPE_INTERNAL_TRANSFER_APPROVED,
        self::TYPE_INTERNAL_TRANSFER_DECLINED,
        self::TYPE_INTERNAL_TRANSFER_REJECTED,
    ];
    const TYPES = [
        self::TYPE_USER_REGISTERED              => ['title' => 'email.title.user.registered',               'twigName' => 'user_registered'],
        self::TYPE_USER_EMAIL_CONFIRMED         => ['title' => 'email.title.user.email_confirmed',          'twigName' => 'user_email_confirmed'],
        self::TYPE_USER_BANK_ACCOUNT_APPROVED   => ['title' => 'email.title.user.bank_account_approved',    'twigName' => 'user_bank_account_approved'],
        self::TYPE_USER_PASSWORD_REQUEST        => ['title' => 'email.title.user.password_request',         'twigName' => 'user_password_request'],

        self::TYPE_USER_TIER2_APPROVED          => ['title' => 'email.title.user.tier2.approved',           'twigName' => 'user_tier2_approved'],
        self::TYPE_USER_TIER3_APPROVED          => ['title' => 'email.title.user.tier3.approved',           'twigName' => 'user_tier3_approved'],

        self::TYPE_USER_TIER2_DECLINED          => ['title' => 'email.title.user.tier2.declined',           'twigName' => 'user_tier2_declined'],
        self::TYPE_USER_TIER3_DECLINED          => ['title' => 'email.title.user.tier3.declined',           'twigName' => 'user_tier3_declined'],

        self::TYPE_WITHDRAWAL_CREATED           => ['title' => 'email.title.withdrawal.created',            'twigName' => 'withdrawal_created'],
        self::TYPE_WITHDRAWAL_APPROVED          => ['title' => 'email.title.withdrawal.approved',           'twigName' => 'withdrawal_approved'],
        self::TYPE_WITHDRAWAL_DECLINED          => ['title' => 'email.title.withdrawal.declined',           'twigName' => 'withdrawal_declined'],
        self::TYPE_WITHDRAWAL_REJECTED          => ['title' => 'email.title.withdrawal.rejected',           'twigName' => 'withdrawal_rejected'],

        self::TYPE_INTERNAL_TRANSFER_CREATED    => ['title' => 'email.title.internal_transfer.created',     'twigName' => 'internal_transfer_created'],
        self::TYPE_INTERNAL_TRANSFER_APPROVED   => ['title' => 'email.title.internal_transfer.approved',    'twigName' => 'internal_transfer_approved'],
        self::TYPE_INTERNAL_TRANSFER_DECLINED   => ['title' => 'email.title.internal_transfer.declined',    'twigName' => 'internal_transfer_declined'],
        self::TYPE_INTERNAL_TRANSFER_REJECTED   => ['title' => 'email.title.internal_transfer.rejected',    'twigName' => 'internal_transfer_rejected'],
    ];
}
