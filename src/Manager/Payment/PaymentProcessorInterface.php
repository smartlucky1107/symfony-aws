<?php

namespace App\Manager\Payment;

use App\Entity\CheckoutOrder;

interface PaymentProcessorInterface
{
    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     */
    public function obtainPaymentUrl(CheckoutOrder $checkoutOrder) : string;
}
