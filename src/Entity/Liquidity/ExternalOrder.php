<?php

namespace App\Entity\Liquidity;

use App\Entity\CurrencyPair;
use App\Entity\Liquidity\Traits\OrderTypeInterface;
use App\Model\PriceInterface;
use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\Price;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Liquidity\Traits\OrderTypeTrait;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Liquidity\ExternalOrderRepository")
 */
class ExternalOrder implements OrderTypeInterface, ExternalOrderInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var CurrencyPair
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\CurrencyPair")
     * @ORM\JoinColumn(name="currency_pair_id", referencedColumnName="id")
     */
    private $currencyPair;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $rate;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $liquidityRate;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amount;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $liquidityAmount;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $removed = false;

    use OrderTypeTrait;

    /**
     * ExternalOrder constructor.
     * @param CurrencyPair $currencyPair
     * @param $type
     * @param $rate
     * @param $amount
     */
    public function __construct(CurrencyPair $currencyPair, $type, $rate, $amount)
    {
        $this->currencyPair = $currencyPair;
        $this->type = $type;
        $this->rate = $rate;
        $this->amount = $amount;
        $this->removed = false;

        $this->refreshLiquidityValues();
    }

    public function refreshLiquidityValues() : void
    {
        if($this->type === self::TYPE_BUY){
            $rateSpread = bcdiv(bcmul($this->rate, $this->currencyPair->getExternalOrderBidSpread(), PriceInterface::BC_SCALE), 100, PriceInterface::BC_SCALE);
            $this->liquidityRate = $this->currencyPair->toPrecisionQuoted(bcsub($this->rate, $rateSpread, PriceInterface::BC_SCALE));;
        }elseif($this->type === self::TYPE_SELL){
            $rateSpread = bcdiv(bcmul($this->rate, $this->currencyPair->getExternalOrderAskSpread(), PriceInterface::BC_SCALE), 100, PriceInterface::BC_SCALE);
            $this->liquidityRate = $this->currencyPair->toPrecisionQuoted(bcadd($this->rate, $rateSpread, PriceInterface::BC_SCALE));;
        }

        if($this->currencyPair->isTetherBalancer()){
            $this->liquidityRate = bcmul($this->liquidityRate, $this->currencyPair->getTetherBalancerAsk(), PriceInterface::BC_SCALE);
        }elseif($this->currencyPair->isEuroBalancer()){
            $this->liquidityRate = bcmul($this->liquidityRate, $this->currencyPair->getEuroBalancerAsk(), PriceInterface::BC_SCALE);
        }

        $amountSpread = bcdiv(bcmul($this->amount, $this->currencyPair->getExternalOrderAmountSpread(), PriceInterface::BC_SCALE), 100, PriceInterface::BC_SCALE);
        $this->liquidityAmount = $this->currencyPair->toTradePrecision(bcsub($this->amount, $amountSpread, PriceInterface::BC_SCALE));

//        if(!($this->currencyPair->isWalutomatLiquidity())){
//            $comp = bccomp($this->liquidityAmount, 10, PriceInterface::BC_SCALE);
//            if($comp === 1){
//                // Divide by 10
//
////                $this->liquidityAmount = $this->currencyPair->toTradePrecision(bcdiv($this->liquidityAmount, 10, PriceInterface::BC_SCALE));
//            }
//        }

        $comp = bccomp($this->liquidityAmount, 0, PriceInterface::BC_SCALE);
        if($comp === 0){
            $this->liquidityAmount = $this->currencyPair->toTradePrecision(1);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return CurrencyPair
     */
    public function getCurrencyPair(): CurrencyPair
    {
        return $this->currencyPair;
    }

    /**
     * @param CurrencyPair $currencyPair
     */
    public function setCurrencyPair(CurrencyPair $currencyPair): void
    {
        $this->currencyPair = $currencyPair;
    }

    /**
     * @return mixed
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param mixed $rate
     */
    public function setRate($rate): void
    {
        $this->rate = $rate;
    }

    /**
     * @return mixed
     */
    public function getLiquidityRate()
    {
        return $this->liquidityRate;
    }

    /**
     * @param mixed $liquidityRate
     */
    public function setLiquidityRate($liquidityRate): void
    {
        $this->liquidityRate = $liquidityRate;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getLiquidityAmount()
    {
        return $this->liquidityAmount;
    }

    /**
     * @param mixed $liquidityAmount
     */
    public function setLiquidityAmount($liquidityAmount): void
    {
        $this->liquidityAmount = $liquidityAmount;
    }

    /**
     * @return bool
     */
    public function isRemoved(): bool
    {
        return $this->removed;
    }

    /**
     * @param bool $removed
     */
    public function setRemoved(bool $removed): void
    {
        $this->removed = $removed;
    }
}
