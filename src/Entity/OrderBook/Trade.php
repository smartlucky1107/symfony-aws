<?php

namespace App\Entity\OrderBook;

use App\Entity\User;
use App\Model\PriceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrderBook\TradeRepository")
 */
class Trade
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'createdAt' => 'createdAt',
        'amount'    => 'amount'
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
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order")
     * @ORM\JoinColumn(name="order_sell_id", referencedColumnName="id")
     */
    private $orderSell;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order")
     * @ORM\JoinColumn(name="order_buy_id", referencedColumnName="id")
     */
    private $orderBuy;

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
    private $feeOffer;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $feeBid;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $price;

    /**
     * @ORM\Column(type="string")
     */
    private $signature;

    /**
     * Trade constructor.
     * @param Order $orderSell
     * @param Order $orderBuy
     * @param string $amount
     * @param string $price
     * @throws \Exception
     */
    public function __construct(Order $orderSell, Order $orderBuy, string $amount, string $price)
    {
        $this->orderSell = $orderSell;
        $this->orderBuy = $orderBuy;
        $this->amount = $amount;
        $this->price = $price;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setFeeBid(0);
        $this->setFeeOffer(0);
    }

    /**
     * Serialize and return public data of the object
     *
     * @param bool $extended
     * @return array
     */
    public function serialize(bool $extended = false) : array
    {
        $serialized = [
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->format('c'),
            'amount'        => $this->toPrecision($this->amount),
            'feeOffer'      => $this->feeOffer,
            'feeBid'        => $this->feeBid,
            'price'         => $this->toPrecision($this->price),
            'signature'     => $this->signature,
            'orderSell'     => $this->orderSell->serializeBasic(),
            'orderBuy'      => $this->orderBuy->serializeBasic(),
            'totalValue'    => $this->getTotalValue()
        ];

        if($extended){
            $serialized['orderSell'] = $this->orderSell->serialize();
            $serialized['orderBuy'] = $this->orderBuy->serialize();
        }

        return $serialized;
    }

    /**
     * @param User|null $user
     * @return array
     *
     */
    public function serializeForPrivateApi(User $user = null) : array
    {
        $serialized = [
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->getTimestamp(),
            'amount'        => $this->toPrecision($this->amount),
            'price'         => $this->toPrecisionQuoted($this->price),
            'totalValue'    => $this->getTotalValue()
        ];

        if($user instanceof User){
            if($this->orderSell->getUser()->getId() === $user->getId()){
                $serialized['orderSell'] = $this->orderSell->serializeForPrivateApi();
            }

            if($this->orderBuy->getUser()->getId() === $user->getId()){
                $serialized['orderBuy'] = $this->orderBuy->serializeForPrivateApi();
            }
        }

        return $serialized;
    }

    /**
     * @return string
     */
    public function getTotalValue()
    {
        return $this->toPrecisionQuoted(bcmul($this->amount, $this->price, PriceInterface::BC_SCALE));
    }

    /**
     * Quoted currency amount
     *
     * @return string
     */
    public function getQuotedAmount()
    {
        //return $this->amount * $this->price;
        return bcmul($this->amount, $this->price, PriceInterface::BC_SCALE);
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getOrderSell()->getCurrencyPair()->getBaseCurrency()->getRoundPrecision());
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecisionQuoted(string $value)
    {
        return bcadd($value, 0, $this->getOrderSell()->getCurrencyPair()->getQuotedCurrency()->getRoundPrecision());
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPairPrecision(string $value)
    {
        if(is_null($this->getOrderSell()->getCurrencyPair()->getRoundPrecision())){
            return bcadd($value, 0, $this->getOrderSell()->getCurrencyPair()->getQuotedCurrency()->getRoundPrecision());
        }else{
            return bcadd($value, 0, $this->getOrderSell()->getCurrencyPair()->getRoundPrecision());
        }
    }

    /**
     * Generate trade signature.
     *
     * @return string
     */
    public function generateSignature(){
        return md5($this->amount . $this->price . $this->getOrderSell()->getId() . $this->getOrderBuy()->getId());
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Order
     */
    public function getOrderSell(): Order
    {
        return $this->orderSell;
    }

    /**
     * @param Order $orderSell
     */
    public function setOrderSell(Order $orderSell): void
    {
        $this->orderSell = $orderSell;
    }

    /**
     * @return Order
     */
    public function getOrderBuy(): Order
    {
        return $this->orderBuy;
    }

    /**
     * @param Order $orderBuy
     */
    public function setOrderBuy(Order $orderBuy): void
    {
        $this->orderBuy = $orderBuy;
    }

    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param mixed $signature
     */
    public function setSignature($signature): void
    {
        $this->signature = $signature;
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
    public function getFeeOffer(): string
    {
        return $this->feeOffer;
    }

    /**
     * @param string $feeOffer
     */
    public function setFeeOffer(string $feeOffer): void
    {
        $this->feeOffer = $feeOffer;
    }

    /**
     * @return string
     */
    public function getFeeBid(): string
    {
        return $this->feeBid;
    }

    /**
     * @param string $feeBid
     */
    public function setFeeBid(string $feeBid): void
    {
        $this->feeBid = $feeBid;
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
}
