<?php

namespace App\Model;

interface FiatDepositInterface
{
    /**
     * Amount of maximum allowed deposit on user balance in PLN
     */
    const MAX_DEPOSIT_BALANCE_PLN = 8500;

    /**
     * Amount of maximum allowed deposit on user balance in EUR
     */
    const MAX_DEPOSIT_BALANCE_EUR = 2000;
}
