<?php

namespace App\Model\RapidOrder;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;

class RapidOrderWallet
{
    /**
     * @var string
     *
     * @SWG\Property(description="Free amount of the base currency", example="0.105")
     * @Serializer\Groups({"output"})
     */
    private $freeAmount;

    /**
     * RapidOrderWallet constructor.
     * @param string $freeAmount
     */
    public function __construct(string $freeAmount)
    {
        $this->freeAmount = $freeAmount;
    }

    /**
     * @return string
     */
    public function getFreeAmount(): string
    {
        return $this->freeAmount;
    }

    /**
     * @param string $freeAmount
     * @return RapidOrderWallet
     */
    public function setFreeAmount(string $freeAmount): RapidOrderWallet
    {
        $this->freeAmount = $freeAmount;
        return $this;
    }
}
