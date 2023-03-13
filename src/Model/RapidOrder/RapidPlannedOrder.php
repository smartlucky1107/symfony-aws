<?php

namespace App\Model\RapidOrder;

use App\Entity\PaymentProcessor;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;

class RapidPlannedOrder
{
    const PAYMENT_PROCESSOR_DOTPAY = 1;
    const ALLOWED_PAYMENT_PROCESSORS = [
        self::PAYMENT_PROCESSOR_DOTPAY
    ];

    /**
     * @var string
     *
     * @SWG\Property(description="Amount of the base currency", example="0.005")
     * @Serializer\Groups({"output"})
     */
    private $amount;

    /**
     * @var string
     *
     * @SWG\Property(description="Total price in the quoted currency", example="1500")
     * @Serializer\Groups({"output"})
     */
    private $totalPrice;

    /**
     * @var PaymentProcessor
     *
     * @SWG\Property(description="Payment processor", ref=@Model(type=PaymentProcessor::class, groups={"output"}))
     * @Serializer\Groups({"output"})
     */
    private $paymentProcessor;

    /**
     * @var string
     *
     * @SWG\Property(description="Total payment value in the quoted currency, including payment fee", example="1540")
     * @Serializer\Groups({"output"})
     */
    private $totalPaymentValue;

    /**
     * RapidPlannedOrder constructor.
     * @param string $amount
     * @param string $totalPrice
     * @param PaymentProcessor $paymentProcessor
     * @param string $totalPaymentValue
     */
    public function __construct(string $amount, string $totalPrice, PaymentProcessor $paymentProcessor, string $totalPaymentValue)
    {
        $this->amount = $amount;
        $this->totalPrice = $totalPrice;
        $this->paymentProcessor = $paymentProcessor;
        $this->totalPaymentValue = $totalPaymentValue;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return RapidPlannedOrder
     */
    public function setAmount(string $amount): RapidPlannedOrder
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    /**
     * @param string $totalPrice
     * @return RapidPlannedOrder
     */
    public function setTotalPrice(string $totalPrice): RapidPlannedOrder
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    /**
     * @return PaymentProcessor
     */
    public function getPaymentProcessor(): PaymentProcessor
    {
        return $this->paymentProcessor;
    }

    /**
     * @param PaymentProcessor $paymentProcessor
     * @return RapidPlannedOrder
     */
    public function setPaymentProcessor(PaymentProcessor $paymentProcessor): RapidPlannedOrder
    {
        $this->paymentProcessor = $paymentProcessor;
        return $this;
    }

    /**
     * @return string
     */
    public function getTotalPaymentValue(): string
    {
        return $this->totalPaymentValue;
    }

    /**
     * @param string $totalPaymentValue
     * @return RapidPlannedOrder
     */
    public function setTotalPaymentValue(string $totalPaymentValue): RapidPlannedOrder
    {
        $this->totalPaymentValue = $totalPaymentValue;
        return $this;
    }
}
