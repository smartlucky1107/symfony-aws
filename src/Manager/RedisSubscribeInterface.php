<?php

namespace App\Manager;

interface RedisSubscribeInterface
{
    const NOTIFICATION_LIST = 'notifications';
    const NOTIFICATIONS_SUBSCRIBE_CHANEL = 'newNotification';

    const TRADING_LIST = 'trading';
    const TRADING_SUBSCRIBE_CHANEL = 'newTrade';

    const WITHDRAWAL_REQUEST_LIST = 'withdrawalRequests';
    const WITHDRAWAL_APPROVE_REQUEST_LIST = 'withdrawalApproveRequests';
    const INTERNAL_TRANSFER_REQUEST_LIST = 'internalTransferRequests';
    const WALLET_TRANSFER_BATCH_LIST = 'walletTransferBatches';
}
