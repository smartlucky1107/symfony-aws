<?php

namespace App\Manager;

interface BlockchairInterface
{
    const CHAIN_BITCOIN         = 'bitcoin';
    const CHAIN_BITCOIN_CASH    = 'bitcoin-cash';
    const CHAIN_BITCOIN_SV      = 'bitcoin-sv';
    const CHAIN_ETHEREUM        = 'ethereum';
    const CHAIN_ETHEREUM_ERC20  = 'ethereum-erc20';

    const CHAINS = [
        self::CHAIN_BITCOIN         => 'Bitcoin',
        self::CHAIN_BITCOIN_CASH    => 'Bitcoin Cash',
        self::CHAIN_BITCOIN_SV      => 'Bitcoin SV',
        self::CHAIN_ETHEREUM        => 'Ethereum',
        self::CHAIN_ETHEREUM_ERC20  => 'Ethereum ERC-20',
    ];

    const ALLOWED_CHAINS = [
        self::CHAIN_BITCOIN,
        self::CHAIN_BITCOIN_CASH,
        self::CHAIN_BITCOIN_SV,
        self::CHAIN_ETHEREUM,
        self::CHAIN_ETHEREUM_ERC20
    ];
}
