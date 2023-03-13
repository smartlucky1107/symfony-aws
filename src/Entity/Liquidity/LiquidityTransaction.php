<?php

namespace App\Entity\Liquidity;

use App\Entity\Liquidity\Traits\OrderTypeInterface;
use App\Entity\Liquidity\Traits\OrderTypeTrait;
use App\Entity\OrderBook\Order;
use App\Entity\CheckoutOrder;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Liquidity\LiquidityTransactionRepository")
 */
class LiquidityTransaction implements OrderTypeInterface
{
    const MARKET_TYPE_INTERNAL = 1;
    const MARKET_TYPE_EXTERNAL = 2;

    const MARKET_TYPES = [
        self::MARKET_TYPE_INTERNAL  => 'Internal',
        self::MARKET_TYPE_EXTERNAL  => 'External'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var Order|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\OrderBook\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    private $order;

    /**
     * @var CheckoutOrder|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\CheckoutOrder")
     * @ORM\JoinColumn(name="checkout_order_id", referencedColumnName="id", nullable=true)
     */
    private $checkoutOrder;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $tetherBalancerOrderbook;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $tetherBalancerOrderbookSymbol;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $euroBalancerOrderbook;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $euroBalancerOrderbookSymbol;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $marketType;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $price;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $realized = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $succeed = false;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $marketResponse;

    /**
     * LiquidityTransaction constructor.
     * @param $marketType
     * @param string $amount
     * @param string $price
     * @throws \Exception
     */
    public function __construct($marketType, string $amount, string $price)
    {
        $this->marketType = $marketType;
        $this->amount = $amount;
        $this->price = $price;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setRealized(false);
        $this->setSucceed(false);
        $this->setMarketResponse([]);
    }

    use OrderTypeTrait;

    /**
     * @return bool
     */
    public function isTetherBalancerTransaction() : bool
    {
        if($this->tetherBalancerOrderbook && $this->tetherBalancerOrderbookSymbol){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEuroBalancerTransaction() : bool
    {
        if($this->euroBalancerOrderbook && $this->euroBalancerOrderbookSymbol){
            return true;
        }

        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @param Order|null $order
     */
    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    /**
     * @return CheckoutOrder|null
     */
    public function getCheckoutOrder(): ?CheckoutOrder
    {
        return $this->checkoutOrder;
    }

    /**
     * @param CheckoutOrder|null $checkoutOrder
     */
    public function setCheckoutOrder(?CheckoutOrder $checkoutOrder): void
    {
        $this->checkoutOrder = $checkoutOrder;
    }

    /**
     * @return mixed
     */
    public function getMarketType()
    {
        return $this->marketType;
    }

    /**
     * @param mixed $marketType
     */
    public function setMarketType($marketType): void
    {
        $this->marketType = $marketType;
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
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @param string $price
     */
    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    /**
     * @return bool
     */
    public function isRealized(): bool
    {
        return $this->realized;
    }

    /**
     * @param bool $realized
     */
    public function setRealized(bool $realized): void
    {
        $this->realized = $realized;
    }

    /**
     * @return array
     */
    public function getMarketResponse(): array
    {
        return $this->marketResponse;
    }

    /**
     * @param array $marketResponse
     */
    public function setMarketResponse(array $marketResponse): void
    {
        $this->marketResponse = $marketResponse;
    }

    /**
     * @return bool
     */
    public function isSucceed(): bool
    {
        return $this->succeed;
    }

    /**
     * @param bool $succeed
     */
    public function setSucceed(bool $succeed): void
    {
        $this->succeed = $succeed;
    }

    /**
     * @return string|null
     */
    public function getTetherBalancerOrderbook(): ?string
    {
        return $this->tetherBalancerOrderbook;
    }

    /**
     * @param string|null $tetherBalancerOrderbook
     */
    public function setTetherBalancerOrderbook(?string $tetherBalancerOrderbook): void
    {
        $this->tetherBalancerOrderbook = $tetherBalancerOrderbook;
    }

    /**
     * @return string|null
     */
    public function getTetherBalancerOrderbookSymbol(): ?string
    {
        return $this->tetherBalancerOrderbookSymbol;
    }

    /**
     * @param string|null $tetherBalancerOrderbookSymbol
     */
    public function setTetherBalancerOrderbookSymbol(?string $tetherBalancerOrderbookSymbol): void
    {
        $this->tetherBalancerOrderbookSymbol = $tetherBalancerOrderbookSymbol;
    }

    /**
     * @return string|null
     */
    public function getEuroBalancerOrderbook(): ?string
    {
        return $this->euroBalancerOrderbook;
    }

    /**
     * @param string|null $euroBalancerOrderbook
     */
    public function setEuroBalancerOrderbook(?string $euroBalancerOrderbook): void
    {
        $this->euroBalancerOrderbook = $euroBalancerOrderbook;
    }

    /**
     * @return string|null
     */
    public function getEuroBalancerOrderbookSymbol(): ?string
    {
        return $this->euroBalancerOrderbookSymbol;
    }

    /**
     * @param string|null $euroBalancerOrderbookSymbol
     */
    public function setEuroBalancerOrderbookSymbol(?string $euroBalancerOrderbookSymbol): void
    {
        $this->euroBalancerOrderbookSymbol = $euroBalancerOrderbookSymbol;
    }
}
