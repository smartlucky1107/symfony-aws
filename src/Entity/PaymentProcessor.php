<?php

namespace App\Entity;

use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentProcessorRepository")
 */
class PaymentProcessor
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @SWG\Property(description="ID of the object", example="1")
     * @Serializer\Groups({"output"})
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Name should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Name is not a valid.",
     *     pattern="/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]+$/i"
     * )
     * @ORM\Column(type="string", length=128)
     *
     * @SWG\Property(description="Full name of the payment processor", example="DotPay")
     * @Serializer\Groups({"output"})
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     *
     * @SWG\Property(description="Fee in percents", example="1.5")
     * @Serializer\Groups({"output"})
     */
    private $fee;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     *
     * @SWG\Property(description="Fixed Fee in PLN", example="0.20")
     * @Serializer\Groups({"output"})
     */
    private $feeFixed;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @SWG\Property(description="Is the processor enable or no.", example="true")
     * @Serializer\Groups({"output"})
     */
    private $enabled = true;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     *
     * @SWG\Property(description="Min payment value [PLN]", example="2")
     * @Serializer\Groups({"output"})
     */
    private $minPayment;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     *
     * @SWG\Property(description="Max payment value [PLN]", example="5000")
     * @Serializer\Groups({"output"})
     */
    private $maxPayment;

    /**
     * PaymentProcessor constructor.
     * @param string $name
     * @param float $fee
     * @param bool $enabled
     */
    public function __construct(string $name, float $fee, bool $enabled)
    {
        $this->name = $name;
        $this->fee = $fee;
        $this->enabled = $enabled;
    }

    /**
     * @param string $amount
     * @return bool
     */
    public function isValidPaymentAmount(string $amount) : bool
    {
        $comp = bccomp($amount, $this->minPayment, PriceInterface::BC_SCALE);
        if($comp === -1) return false;

        $comp = bccomp($amount, $this->maxPayment, PriceInterface::BC_SCALE);
        if($comp === 1) return false;

        return true;
    }

    /**
     * @return bool
     */
    public function isPaywallCardProcessor() : bool
    {
        if($this->id === 1) return true;

        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PaymentProcessor
     */
    public function setName(string $name): PaymentProcessor
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float
     */
    public function getFee(): float
    {
        return $this->fee;
    }

    /**
     * @param float $fee
     * @return PaymentProcessor
     */
    public function setFee(float $fee): PaymentProcessor
    {
        $this->fee = $fee;
        return $this;
    }

    /**
     * @return float
     */
    public function getFeeFixed(): float
    {
        return $this->feeFixed;
    }

    /**
     * @param float $feeFixed
     * @return PaymentProcessor
     */
    public function setFeeFixed(float $feeFixed): PaymentProcessor
    {
        $this->feeFixed = $feeFixed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return PaymentProcessor
     */
    public function setEnabled(bool $enabled): PaymentProcessor
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinPayment(): float
    {
        return $this->minPayment;
    }

    /**
     * @param float $minPayment
     * @return PaymentProcessor
     */
    public function setMinPayment(float $minPayment): PaymentProcessor
    {
        $this->minPayment = $minPayment;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxPayment(): float
    {
        return $this->maxPayment;
    }

    /**
     * @param float $maxPayment
     * @return PaymentProcessor
     */
    public function setMaxPayment(float $maxPayment): PaymentProcessor
    {
        $this->maxPayment = $maxPayment;
        return $this;
    }
}
