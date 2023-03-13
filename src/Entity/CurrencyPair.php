<?php

namespace App\Entity;

use App\Entity\Liquidity\ExternalOrderInterface;
use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyPairRepository")
 */
class CurrencyPair
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'enabled'   => 'enabled',
    ];

    const EXTERNAL_ORDERBOOK_BITBAY = 'bitbay';
    const EXTERNAL_ORDERBOOK_BINANCE = 'binance';
    const EXTERNAL_ORDERBOOK_KRAKEN = 'kraken';
    const EXTERNAL_ORDERBOOK_WALUTOMAT = 'walutomat';

    const CATEGORY_CRYPTO_FIAT      = 1;
    const CATEGORY_CRYPTO_CRYPTO    = 2;
    const CATEGORY_PERSONAL         = 3;
    const CATEGORY_BRAND            = 4;
    const CATEGORY_STARTUP          = 5;
    const CATEGORY_FOREX            = 6;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="base_currency_id", referencedColumnName="id")
     *
     * @SWG\Property(ref=@Model(type=Currency::class, groups={"output"}))
     * @Serializer\Groups({"output"})
     */
    private $baseCurrency;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="quoted_currency_id", referencedColumnName="id")
     *
     * @SWG\Property(ref=@Model(type=Currency::class, groups={"output"}))
     * @Serializer\Groups({"output"})
     */
    private $quotedCurrency;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    private $enabled = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_visible", type="boolean")
     */
    private $visible = true;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $growth24h;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $price;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $price12Points;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $externalOrderbook;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $externalOrderbookSymbol;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $externalOrderMinAmount;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $externalOrderBidSpread;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $externalOrderAskSpread;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $externalOrderAmountSpread;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $lotSizeMinQty;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $lotSizeMaxQty;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $lotSizeStepSize;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $minNotional;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $externalAmountPrecision;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedCategories")
     */
    private $category;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @SWG\Property(description="Round precision", example="2")
     * @Serializer\Groups({"output"})
     */
    private $roundPrecision;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $minLimitPrice;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $maxLimitPrice;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_market_order_allowed", type="boolean")
     */
    private $marketOrderAllowed = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_pos_order_allowed", type="boolean")
     */
    private $posOrderAllowed = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_buy_allowed", type="boolean")
     */
    private $buyAllowed = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_sell_allowed", type="boolean")
     */
    private $sellAllowed = true;

####
## Tether Balancer
####

    /**
     * @var bool
     *
     * @ORM\Column(name="is_tether_balancer", type="boolean")
     */
    private $tetherBalancer = true;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $tetherBalancerBid;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $tetherBalancerAsk;

####
## Euro Balancer
####

    /**
     * @var bool
     *
     * @ORM\Column(name="is_euro_balancer", type="boolean")
     */
    private $euroBalancer = true;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $euroBalancerBid;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $euroBalancerAsk;

####
## indacoin settings
####

    /**
     * @var bool
     *
     * @ORM\Column(name="is_indacoin_allowed", type="boolean")
     */
    private $indacoinAllowed = true;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $sortIndex;

    /**
     * CurrencyPair constructor.
     * @param Currency $baseCurrency
     * @param Currency $quotedCurrency
     */
    public function __construct(Currency $baseCurrency, Currency $quotedCurrency)
    {
        $this->baseCurrency = $baseCurrency;
        $this->quotedCurrency = $quotedCurrency;

        $this->setEnabled(true);
        $this->setVisible(true);
        $this->setPrice12Points([]);
        $this->setExternalOrderbook(null);
        $this->setExternalOrderbookSymbol(null);
        $this->setExternalOrderMinAmount(0);
        $this->setExternalOrderBidSpread(ExternalOrderInterface::BID_SPREAD);
        $this->setExternalOrderAskSpread(ExternalOrderInterface::ASK_SPREAD);
        $this->setExternalOrderAmountSpread(ExternalOrderInterface::AMOUNT_SPREAD);
        $this->setCategory(self::CATEGORY_STARTUP);

        $this->setLotSizeMinQty(0);
        $this->setLotSizeMinQty(100);
        $this->setLotSizeStepSize(0);
        $this->setMinNotional(0);
        $this->setExternalAmountPrecision(2);
        $this->setRoundPrecision(null);
        $this->setMinLimitPrice(null);
        $this->setMaxLimitPrice(null);
        $this->setSortIndex(0);
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'id'            => $this->id,
            'baseCurrency'  => $this->baseCurrency->serialize(),
            'quotedCurrency'=> $this->quotedCurrency->serialize(),
            'category'      => $this->category,
            'enabled'       => $this->enabled,
            'visible'       => $this->visible,
            'pairShortName' => $this->pairShortName(),
            'tradePrecision'=> $this->getTradePrecision(),
            'lotSizeMinQty' => $this->lotSizeMinQty,
            'lotSizeMaxQty' => $this->lotSizeMaxQty,
            'lotSizeStepSize' => $this->lotSizeStepSize,
            'minNotional'   => $this->minNotional,
            'roundPrecision' => $this->roundPrecision,
            'minLimitPrice' => $this->minLimitPrice,
            'maxLimitPrice' => $this->maxLimitPrice,
            '1hPoints'      => $this->getPrice12Points(),
            'isBuyAllowed'  => $this->isBuyAllowed(),
            'isSellAllowed'  => $this->isSellAllowed(),
        ];
    }

    /**
     * @return array
     */
    public function serializeForPrivateApi() : array
    {
        return [
            'id'            => $this->id,
            'visible'       => $this->visible,
            'baseCurrency'  => $this->baseCurrency->serializeForPrivateApi(),
            'quotedCurrency'=> $this->quotedCurrency->serializeForPrivateApi(),
            'pairShortName' => $this->pairShortName(),
            'tradePrecision'=> $this->getTradePrecision(),
            'growth24'      => $this->growth24h,
            'price'      => $this->price,
            'roundPrecision' => $this->roundPrecision,
            '1hPoints'      => $this->getPrice12Points(),
        ];
    }

    /**
     * @return array
     */
    public function serializeForPreOrder() : array
    {
        return [
            'visible'       => $this->visible,
            'baseCurrency'  => $this->baseCurrency->serializeForPrivateApi(),
            'quotedCurrency'=> $this->quotedCurrency->serializeForPrivateApi(),
            'roundPrecision' => $this->roundPrecision,
        ];
    }

    /**
     * @return array
     */
    public function serializeForPOSApi() : array
    {
        return [
            'baseCurrency'  => $this->baseCurrency->serializeForPOSApi(),
            'quotedCurrency'=> $this->quotedCurrency->serializeForPOSApi(),
            'roundPrecision' => $this->roundPrecision,
            'lotSizeMinQty' => $this->lotSizeMinQty,
            'lotSizeMaxQty' => $this->lotSizeMaxQty,
        ];
    }

    public static function getAllowedCategories(){
        return [
            self::CATEGORY_CRYPTO_FIAT,
            self::CATEGORY_CRYPTO_CRYPTO,
            self::CATEGORY_PERSONAL,
            self::CATEGORY_BRAND,
            self::CATEGORY_STARTUP,
            self::CATEGORY_FOREX,
        ];
    }

    /**
     * @return int
     */
    public function getTradePrecision() : int
    {
        if($this->isBinanceLiquidity()){
            return $this->externalAmountPrecision;
        }elseif($this->isKrakenLiquidity()){
            return $this->externalAmountPrecision;
        }

        return $this->baseCurrency->getRoundPrecision();
    }

    /**
     * @return bool
     */
    public function isExternalLiquidityEnabled() : bool
    {
        if(is_null($this->getExternalOrderbook()) || is_null($this->getExternalOrderbookSymbol())){
            return false;
        }

        return true;
    }

    /**
     * @param string|null $limitPrice
     * @return bool
     */
    public function isMinMaxLimitPriceValid(?string $limitPrice = null) : bool
    {
        if($this->minLimitPrice){
            $comp = bccomp($limitPrice, $this->minLimitPrice, PriceInterface::BC_SCALE);
            if($comp === -1) return false;
        }
        if($this->maxLimitPrice){
            $comp = bccomp($limitPrice, $this->maxLimitPrice, PriceInterface::BC_SCALE);
            if($comp === 1) return false;
        }

        return true;
    }

    /**
     * @param string $amount
     * @return bool
     */
    public function isExternalLotSizeValid(string $amount) : bool
    {
        $comp = bccomp($amount, $this->lotSizeMinQty, PriceInterface::BC_SCALE);
        if(!($comp === 1 || $comp === 0)){
            return false;
        }

        $comp = bccomp($amount, $this->lotSizeMaxQty, PriceInterface::BC_SCALE);
        if(!($comp === -1 || $comp === 0)){
            return false;
        }

        $sub = bcsub($amount, $this->lotSizeMinQty, PriceInterface::BC_SCALE);
        $mod = bcmod($sub, $this->lotSizeStepSize, PriceInterface::BC_SCALE);

        $comp = bccomp($mod, 0, PriceInterface::BC_SCALE);
        if($comp === 0){
            return true;
        }

        return false;
    }

    /**
     * @param string $amount
     * @param string $price
     * @return bool
     */
    public function isExternalMinNotionalValid(string $amount, string $price) : bool
    {
        $mul = bcmul($amount, $price, PriceInterface::BC_SCALE);

        $comp = bccomp($mul, $this->minNotional, PriceInterface::BC_SCALE);
        if($comp === 1){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isBitbayLiquidity() : bool
    {
        if($this->isExternalLiquidityEnabled()){
            if($this->getExternalOrderbook() === CurrencyPair::EXTERNAL_ORDERBOOK_BITBAY){
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isBinanceLiquidity() : bool
    {
        if($this->isExternalLiquidityEnabled()){
            if($this->getExternalOrderbook() === CurrencyPair::EXTERNAL_ORDERBOOK_BINANCE){
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isKrakenLiquidity() : bool
    {
        if($this->isExternalLiquidityEnabled()){
            if($this->getExternalOrderbook() === CurrencyPair::EXTERNAL_ORDERBOOK_KRAKEN){
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isWalutomatLiquidity() : bool
    {
        if($this->isExternalLiquidityEnabled()){
            if($this->getExternalOrderbook() === CurrencyPair::EXTERNAL_ORDERBOOK_WALUTOMAT){
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getBaseCurrency()->getRoundPrecision());
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecisionQuoted(string $value){
        return bcadd($value, 0, $this->getQuotedCurrency()->getRoundPrecision());
    }

    /**
     * @param string $value
     * @return string
     */
    public function toTradePrecision(string $value){
        return bcadd($value, 0, $this->getTradePrecision());
    }

    /**
     * Return pair short name
     *
     * @return string
     */
    public function pairShortName(){
        return strtoupper($this->getBaseCurrency()->getShortName()) . '-' . strtoupper($this->getQuotedCurrency()->getShortName());
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Currency
     */
    public function getBaseCurrency(): Currency
    {
        return $this->baseCurrency;
    }

    /**
     * @param Currency $baseCurrency
     */
    public function setBaseCurrency(Currency $baseCurrency): void
    {
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * @return Currency
     */
    public function getQuotedCurrency(): Currency
    {
        return $this->quotedCurrency;
    }

    /**
     * @param Currency $quotedCurrency
     */
    public function setQuotedCurrency(Currency $quotedCurrency): void
    {
        $this->quotedCurrency = $quotedCurrency;
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
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     */
    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
    }

    /**
     * @return float
     */
    public function getGrowth24h(): float
    {
        return $this->growth24h;
    }

    /**
     * @param float $growth24h
     */
    public function setGrowth24h(float $growth24h): void
    {
        $this->growth24h = $growth24h;
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
     * @return array
     */
    public function getPrice12Points(): array
    {
        return $this->price12Points;
    }

    /**
     * @param array $price12Points
     */
    public function setPrice12Points(array $price12Points): void
    {
        $this->price12Points = $price12Points;
    }

    /**
     * @return string|null
     */
    public function getExternalOrderbook(): ?string
    {
        return $this->externalOrderbook;
    }

    /**
     * @param string|null $externalOrderbook
     */
    public function setExternalOrderbook(?string $externalOrderbook): void
    {
        $this->externalOrderbook = $externalOrderbook;
    }

    /**
     * @return string|null
     */
    public function getExternalOrderbookSymbol(): ?string
    {
        return $this->externalOrderbookSymbol;
    }

    /**
     * @param string|null $externalOrderbookSymbol
     */
    public function setExternalOrderbookSymbol(?string $externalOrderbookSymbol): void
    {
        $this->externalOrderbookSymbol = $externalOrderbookSymbol;
    }

    /**
     * @return string
     */
    public function getExternalOrderMinAmount(): string
    {
        return $this->externalOrderMinAmount;
    }

    /**
     * @param string $externalOrderMinAmount
     */
    public function setExternalOrderMinAmount(string $externalOrderMinAmount): void
    {
        $this->externalOrderMinAmount = $externalOrderMinAmount;
    }

    /**
     * @return string
     */
    public function getExternalOrderBidSpread(): string
    {
        return $this->externalOrderBidSpread;
    }

    /**
     * @param string $externalOrderBidSpread
     */
    public function setExternalOrderBidSpread(string $externalOrderBidSpread): void
    {
        $this->externalOrderBidSpread = $externalOrderBidSpread;
    }

    /**
     * @return string
     */
    public function getExternalOrderAskSpread(): string
    {
        return $this->externalOrderAskSpread;
    }

    /**
     * @param string $externalOrderAskSpread
     */
    public function setExternalOrderAskSpread(string $externalOrderAskSpread): void
    {
        $this->externalOrderAskSpread = $externalOrderAskSpread;
    }

    /**
     * @return string
     */
    public function getExternalOrderAmountSpread(): string
    {
        return $this->externalOrderAmountSpread;
    }

    /**
     * @param string $externalOrderAmountSpread
     */
    public function setExternalOrderAmountSpread(string $externalOrderAmountSpread): void
    {
        $this->externalOrderAmountSpread = $externalOrderAmountSpread;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getLotSizeMinQty(): string
    {
        return $this->lotSizeMinQty;
    }

    /**
     * @param string $lotSizeMinQty
     */
    public function setLotSizeMinQty(string $lotSizeMinQty): void
    {
        $this->lotSizeMinQty = $lotSizeMinQty;
    }

    /**
     * @return string
     */
    public function getLotSizeMaxQty(): string
    {
        return $this->lotSizeMaxQty;
    }

    /**
     * @param string $lotSizeMaxQty
     */
    public function setLotSizeMaxQty(string $lotSizeMaxQty): void
    {
        $this->lotSizeMaxQty = $lotSizeMaxQty;
    }

    /**
     * @return string
     */
    public function getLotSizeStepSize(): string
    {
        return $this->lotSizeStepSize;
    }

    /**
     * @param string $lotSizeStepSize
     */
    public function setLotSizeStepSize(string $lotSizeStepSize): void
    {
        $this->lotSizeStepSize = $lotSizeStepSize;
    }

    /**
     * @return string
     */
    public function getMinNotional(): string
    {
        return $this->minNotional;
    }

    /**
     * @param string $minNotional
     */
    public function setMinNotional(string $minNotional): void
    {
        $this->minNotional = $minNotional;
    }

    /**
     * @return int
     */
    public function getExternalAmountPrecision(): int
    {
        return $this->externalAmountPrecision;
    }

    /**
     * @param int $externalAmountPrecision
     */
    public function setExternalAmountPrecision(int $externalAmountPrecision): void
    {
        $this->externalAmountPrecision = $externalAmountPrecision;
    }

    /**
     * @return int|null
     */
    public function getRoundPrecision(): ?int
    {
        return $this->roundPrecision;
    }

    /**
     * @param int|null $roundPrecision
     */
    public function setRoundPrecision(?int $roundPrecision): void
    {
        $this->roundPrecision = $roundPrecision;
    }

    /**
     * @return string|null
     */
    public function getMinLimitPrice(): ?string
    {
        return $this->minLimitPrice;
    }

    /**
     * @param string|null $minLimitPrice
     */
    public function setMinLimitPrice(?string $minLimitPrice): void
    {
        $this->minLimitPrice = $minLimitPrice;
    }

    /**
     * @return string|null
     */
    public function getMaxLimitPrice(): ?string
    {
        return $this->maxLimitPrice;
    }

    /**
     * @param string|null $maxLimitPrice
     */
    public function setMaxLimitPrice(?string $maxLimitPrice): void
    {
        $this->maxLimitPrice = $maxLimitPrice;
    }

    /**
     * @return bool
     */
    public function isMarketOrderAllowed(): bool
    {
        return $this->marketOrderAllowed;
    }

    /**
     * @param bool $marketOrderAllowed
     */
    public function setMarketOrderAllowed(bool $marketOrderAllowed): void
    {
        $this->marketOrderAllowed = $marketOrderAllowed;
    }

    /**
     * @return bool
     */
    public function isPosOrderAllowed(): bool
    {
        return $this->posOrderAllowed;
    }

    /**
     * @param bool $posOrderAllowed
     */
    public function setPosOrderAllowed(bool $posOrderAllowed): void
    {
        $this->posOrderAllowed = $posOrderAllowed;
    }

    /**
     * @return bool
     */
    public function isBuyAllowed(): bool
    {
        return $this->buyAllowed;
    }

    /**
     * @param bool $buyAllowed
     */
    public function setBuyAllowed(bool $buyAllowed): void
    {
        $this->buyAllowed = $buyAllowed;
    }

    /**
     * @return bool
     */
    public function isSellAllowed(): bool
    {
        return $this->sellAllowed;
    }

    /**
     * @param bool $sellAllowed
     */
    public function setSellAllowed(bool $sellAllowed): void
    {
        $this->sellAllowed = $sellAllowed;
    }

    /**
     * @return bool
     */
    public function isTetherBalancer(): bool
    {
        return $this->tetherBalancer;
    }

    /**
     * @param bool $tetherBalancer
     */
    public function setTetherBalancer(bool $tetherBalancer): void
    {
        $this->tetherBalancer = $tetherBalancer;
    }

    /**
     * @return string|null
     */
    public function getTetherBalancerBid(): ?string
    {
        return $this->tetherBalancerBid;
    }

    /**
     * @param string|null $tetherBalancerBid
     */
    public function setTetherBalancerBid(?string $tetherBalancerBid): void
    {
        $this->tetherBalancerBid = $tetherBalancerBid;
    }

    /**
     * @return string|null
     */
    public function getTetherBalancerAsk(): ?string
    {
        return $this->tetherBalancerAsk;
    }

    /**
     * @param string|null $tetherBalancerAsk
     */
    public function setTetherBalancerAsk(?string $tetherBalancerAsk): void
    {
        $this->tetherBalancerAsk = $tetherBalancerAsk;
    }

    /**
     * @return bool
     */
    public function isEuroBalancer(): bool
    {
        return $this->euroBalancer;
    }

    /**
     * @param bool $euroBalancer
     */
    public function setEuroBalancer(bool $euroBalancer): void
    {
        $this->euroBalancer = $euroBalancer;
    }

    /**
     * @return string|null
     */
    public function getEuroBalancerBid(): ?string
    {
        return $this->euroBalancerBid;
    }

    /**
     * @param string|null $euroBalancerBid
     */
    public function setEuroBalancerBid(?string $euroBalancerBid): void
    {
        $this->euroBalancerBid = $euroBalancerBid;
    }

    /**
     * @return string|null
     */
    public function getEuroBalancerAsk(): ?string
    {
        return $this->euroBalancerAsk;
    }

    /**
     * @param string|null $euroBalancerAsk
     */
    public function setEuroBalancerAsk(?string $euroBalancerAsk): void
    {
        $this->euroBalancerAsk = $euroBalancerAsk;
    }

    /**
     * @return bool
     */
    public function isIndacoinAllowed(): bool
    {
        return $this->indacoinAllowed;
    }

    /**
     * @param bool $indacoinAllowed
     */
    public function setIndacoinAllowed(bool $indacoinAllowed): void
    {
        $this->indacoinAllowed = $indacoinAllowed;
    }

    /**
     * @return int
     */
    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    /**
     * @param int $sortIndex
     * @return CurrencyPair
     */
    public function setSortIndex(int $sortIndex): CurrencyPair
    {
        $this->sortIndex = $sortIndex;
        return $this;
    }
}
