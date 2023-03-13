<?php

namespace App\Model\RapidOrder;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;

class RapidMarketOrder
{
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
     * RapidOrderMarket constructor.
     * @param string $amount
     * @param string $totalPrice
     */
    public function __construct(string $amount, string $totalPrice)
    {
        $this->amount = $amount;
        $this->totalPrice = $totalPrice;
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
     * @return RapidMarketOrder
     */
    public function setAmount(string $amount): RapidMarketOrder
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
     * @return RapidMarketOrder
     */
    public function setTotalPrice(string $totalPrice): RapidMarketOrder
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }
}
