<?php

namespace App\Model\WalletTransfer;

interface WalletTransferInterface
{
    const TYPE_BLOCK            = 'block';
    const TYPE_RELEASE          = 'release';

    const TYPE_FUND             = 'fund';
    const TYPE_DEFUND           = 'defund';

    const TYPE_FUND_FEE         = 'fund_fee';
    const TYPE_DEFUND_FEE       = 'defund_fee';

    const TYPE_DEPOSIT          = 'deposit';
    const TYPE_WITHDRAWAL       = 'withdrawal';

    const TYPE_FUND_INTERNAL    = 'fund_internal';
    const TYPE_DEFUND_INTERNAL  = 'defund_internal';

    const TYPES = [
        self::TYPE_BLOCK,
        self::TYPE_RELEASE,
        self::TYPE_FUND,
        self::TYPE_FUND_FEE,
        self::TYPE_DEFUND,
        self::TYPE_DEFUND_FEE,
        self::TYPE_DEPOSIT,
        self::TYPE_WITHDRAWAL,
        self::TYPE_FUND_INTERNAL,
        self::TYPE_DEFUND_INTERNAL,
    ];

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return int
     */
    public function getWalletId(): int;

    /**
     * @return string
     */
    public function getAmount(): string;
}
