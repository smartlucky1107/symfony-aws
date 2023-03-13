<?php

namespace App\Security;

interface VoterRoleInterface
{
    const ROLE_USER             = 'ROLE_USER';
    const ROLE_ADMIN            = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN      = 'ROLE_SUPER_ADMIN';
    const ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN
    ];

    const MODULE_USER           = 'user';
    const MODULE_WALLET         = 'wallet';
    const MODULE_ORDER          = 'order';
    const MODULE_CHECKOUT_ORDER = 'checkout_order';
    const MODULE_POS_ORDER      = 'pos_order';
    const MODULE_WORKSPACE      = 'workspace';
    const MODULE_EMPLOYEE       = 'employee';
    const MODULE_TRADE          = 'trade';
    const MODULE_WITHDRAWAL     = 'withdrawal';
    const MODULE_INTERNAL_TRANSFER     = 'internal_transfer';
    const MODULE_DEPOSIT        = 'deposit';
    const MODULE_CURRENCY       = 'currency';
    const MODULE_CURRENCY_PAIR  = 'currency_pair';
    const MODULE_VOTER_ROLE     = 'voter_role';
    const MODULE_API_KEY        = 'api_key';
    const MODULE_BLOCKCHAIN     = 'blockchain';
    const MODULE_GIIF_REPORTS   = 'giif_reports';
    const MODULES = [
        self::MODULE_USER,
        self::MODULE_WALLET,
        self::MODULE_ORDER,
        self::MODULE_CHECKOUT_ORDER,
        self::MODULE_POS_ORDER,
        self::MODULE_WORKSPACE,
        self::MODULE_EMPLOYEE,
        self::MODULE_TRADE,
        self::MODULE_WITHDRAWAL,
        self::MODULE_INTERNAL_TRANSFER,
        self::MODULE_DEPOSIT,
        self::MODULE_CURRENCY,
        self::MODULE_CURRENCY_PAIR,
        self::MODULE_VOTER_ROLE,
        self::MODULE_API_KEY,
        self::MODULE_BLOCKCHAIN,
        self::MODULE_GIIF_REPORTS,
    ];

    const ACTION_MANAGE     = 'manage';
    const ACTION_CREATE     = 'create';
    const ACTION_VERIFY     = 'VERIFY';
    const ACTION_ANALYZE    = 'ANALYZE';
    const ACTION_LIST       = 'list';
    const ACTION_VIEW       = 'view';
    const ACTIONS = [
        self::ACTION_MANAGE,
        self::ACTION_CREATE,
        self::ACTION_VERIFY,
        self::ACTION_ANALYZE,
        self::ACTION_LIST,
        self::ACTION_VIEW,
    ];
    const ACTIONS_HIERARCHY = [
        self::ACTION_MANAGE => [
            self::ACTION_CREATE,
            self::ACTION_VERIFY,
            self::ACTION_VIEW,
            self::ACTION_LIST => [
                self::ACTION_VIEW
            ]
        ]
    ];
}
