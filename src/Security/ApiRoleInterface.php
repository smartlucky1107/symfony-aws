<?php

namespace App\Security;

class ApiRoleInterface
{
    const ROLE_DEPOSIT = 'deposit';
    const ROLE_ORDER = 'order';
    const ROLE_USER = 'user';
    const ROLE_WALLET = 'wallet';
    const ROLE_WITHDRAWAL = 'withdrawal';
    const ROLE_WALLET_ANALYZE = 'wallet_analyze';
    const ROLE_WITHDRAWAL_APPROVE = 'withdrawal_approve';
    const ROLE_WITHDRAWAL_DECLINE = 'withdrawal_decline';
    const ROLE_WITHDRAWALS_FOR_EXTERNAL_APPROVAL= 'withdrawals_for_external_approval';
    const ROLE_INTERNAL_TRANSFER_CONFIRM = 'internal_transfer_confirm';
    const ROLE_BLOCKCHAIN_TX_CREATE = 'blockchain_tx_create';
    const ROLE_FINANCIAL_REPORTS = 'financial_reports';
    const ROLE_TRADE_REVERT = 'trade_revert';
    const ROLE_POS = 'pos';

    const ROLES = [
        self::ROLE_DEPOSIT,
        self::ROLE_ORDER,
        self::ROLE_USER,
        self::ROLE_WALLET,
        self::ROLE_WITHDRAWAL,
        self::ROLE_WALLET_ANALYZE,
        self::ROLE_WITHDRAWAL_APPROVE,
        self::ROLE_WITHDRAWAL_DECLINE,
        self::ROLE_WITHDRAWALS_FOR_EXTERNAL_APPROVAL,
        self::ROLE_INTERNAL_TRANSFER_CONFIRM,
        self::ROLE_BLOCKCHAIN_TX_CREATE,
        self::ROLE_FINANCIAL_REPORTS,
        self::ROLE_TRADE_REVERT,
        self::ROLE_POS,
    ];
}
