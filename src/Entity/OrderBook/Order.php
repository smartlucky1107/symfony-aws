<?php

namespace App\Entity\OrderBook;

use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrderBook\OrderRepository")
 * @ORM\Table(name="the_order", indexes={
 *     @ORM\Index(name="search_idx1", columns={"type", "status", "limit_price"}),
 *     @ORM\Index(name="search_idx2", columns={"created_at"}),
 *     @ORM\Index(name="search_idx3", columns={"status"}),
 *     @ORM\Index(name="search_idx4", columns={"is_filled"})
 * })
 */
class Order
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'            => 'id',
        'type'          => 'type',
        'status'        => 'status',
        'amount'        => 'amount',
        'limitPrice'    => 'limitPrice',
        'amountFilled'  => 'amountFilled',
        'isFilled'      => 'isFilled'
    ];

    const TYPE_BUY = 1;
    const TYPE_SELL = 2;

    const TYPES = [
        self::TYPE_BUY      => 'Buy',
        self::TYPE_SELL     => 'Sell'
    ];

    const EXECUTION_INSTANT = 1; // instant order without limit price
    const EXECUTION_LIMIT   = 3; // limit order with limitPrice

    const EXECUTIONS = [
        self::EXECUTION_INSTANT,
        self::EXECUTION_LIMIT
    ];

    const STATUS_NEW = 1;
    const STATUS_PENDING = 2;
    const STATUS_FILLED = 3;
    const STATUS_REJECTED = 5;

    const STATUSES = [
        self::STATUS_NEW        => 'New',
        self::STATUS_PENDING    => 'Pending',
        self::STATUS_FILLED     => 'Filled',
        self::STATUS_REJECTED   => 'Rejected'
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="orders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @var Wallet
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="base_currency_wallet_id", referencedColumnName="id")
     */
    private $baseCurrencyWallet;

    /**
     * @var Wallet
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="quoted_currency_wallet_id", referencedColumnName="id")
     */
    private $quotedCurrencyWallet;

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
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amount;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $execution;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $limitPrice;

    /**
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amountFilled;

    /**
     * Amount blocked for Market Orders
     *
     * @ORM\Column(type="decimal", precision=36, scale=18, nullable=true)
     */
    private $amountBlocked;

    /**
     * @ORM\Column(name="is_filled", type="boolean")
     */
    private $isFilled = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $externalLiquidityOrder = false;

    /**
     * Order constructor.
     * @param User $user
     * @param Wallet $baseCurrencyWallet
     * @param Wallet $quotedCurrencyWallet
     * @param CurrencyPair $currencyPair
     * @param int $type
     * @param $amount
     * @param string|null $limitPrice
     * @throws \Exception
     */
    public function __construct(User $user, Wallet $baseCurrencyWallet, Wallet $quotedCurrencyWallet, CurrencyPair $currencyPair, int $type, $amount, string $limitPrice = null)
    {
        $this->user = $user;
        $this->baseCurrencyWallet = $baseCurrencyWallet;
        $this->quotedCurrencyWallet = $quotedCurrencyWallet;
        $this->currencyPair = $currencyPair;
        $this->type = $type;
        $this->amount = $amount;
        if($limitPrice){
            $this->limitPrice = $limitPrice;
            $this->setExecution(self::EXECUTION_LIMIT);
        }else{
            $this->setExecution(self::EXECUTION_INSTANT);
        }

        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus(self::STATUS_NEW);
        $this->setAmountFilled(0);
        $this->setIsFilled(false);
        $this->setAmountBlocked(null);
        $this->setExternalLiquidityOrder(false);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isUserAllowed(User $user) : bool
    {
        if($this->user->getId() === $user->getId()){
            return true;
        }

        return false;
    }

    /**
     * Get status name of the object
     *
     * @return string
     */
    public function getStatusName() : string
    {
        if(isset(self::STATUSES[$this->status])){
            return self::STATUSES[$this->status];
        }

        return '';
    }

    /**
     * Get type name of the object
     *
     * @return string
     */
    public function getTypeName() : string
    {
        if(isset(self::TYPES[$this->type])){
            return self::TYPES[$this->type];
        }

        return '';
    }

//    /**
//     * @return string
//     */
//    public function getTypeName() : string
//    {
//        if($this->isOffer()) return 'offer';
//        if($this->isBid()) return 'bid';
//
//        return '';
//    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->format('c'),
            'user'          => $this->user->serialize(),
            'baseCurrencyWallet' => $this->baseCurrencyWallet->serialize(),
            'quotedCurrencyWallet' => $this->quotedCurrencyWallet->serialize(),
            'currencyPair'  => $this->currencyPair->serialize(),
            'type'          => $this->type,
            'typeName'      => $this->getTypeName(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'amount'        => $this->toPrecision($this->amount),
            'totalValue'    => $this->getTotalValue(),
            'limitPrice'    => $this->limitPrice ? $this->toPrecisionQuoted($this->limitPrice) : $this->toPrecisionQuoted(0),
            'amountFilled'  => $this->toPrecision($this->amountFilled),
            'isFilled'      => $this->isFilled,
            'execution'     => $this->execution,
            'isBid'         => $this->isBid(),
            'isOffer'       => $this->isOffer(),
            'progress'      => $this->progress()
        ];
    }

    /**
     * Serialize and return public basic data of the object
     *
     * @return array
     */
    public function serializeBasic() : array
    {
        return [
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->format('c'),
            'currencyPair'  => $this->currencyPair->serialize(),
            'type'          => $this->type,
            'typeName'      => $this->getTypeName(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'amount'        => $this->toPrecision($this->amount),
            'limitPrice'    => $this->limitPrice ? $this->toPrecisionQuoted($this->limitPrice) : $this->toPrecisionQuoted(0),
            'amountFilled'  => $this->toPrecision($this->amountFilled),
            'isFilled'      => $this->isFilled,
        ];
    }

    /**
     * @return array
     */
    public function serializeForPrivateApi() : array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'typeName'      => $this->getTypeName(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'currencyPair'  => $this->currencyPair->serializeForPrivateApi(),
        ];
    }

    /**
     * @return bool
     */
    public function isCryptoCrypto() : bool
    {
        if($this->getCurrencyPair()->getBaseCurrency()->isCryptoType() && $this->getCurrencyPair()->getQuotedCurrency()->isCryptoType()){
            return true;
        }

        return false;
    }

    /**
     * Resolve release amount based on amountBlocked value in the Order for instant orders
     *
     * @param string $amount
     * @return string
     */
    public function resolveReleaseAmount(string $amount) : string
    {
        if(is_numeric($this->amountBlocked)){
            $comp = bccomp($amount, $this->amountBlocked, PriceInterface::BC_SCALE);
            if($comp === 1){
                return $this->amountBlocked;
            }else{
                return $amount;
            }
        }

        return (string) 0;
    }

    /**
     * @return string
     */
    public function getTotalValue()
    {
        return $this->toPrecisionQuoted(bcmul($this->amount, $this->limitPrice, PriceInterface::BC_SCALE));
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getCurrencyPair()->getBaseCurrency()->getRoundPrecision());
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecisionQuoted(string $value){
        return bcadd($value, 0, $this->getCurrencyPair()->getQuotedCurrency()->getRoundPrecision());
    }

    /**
     * Verify if order is quick - instant execution order
     *
     * @return bool
     */
    public function isInstantExecution() : bool
    {
        if($this->execution === self::EXECUTION_INSTANT){
            return true;
        }

        return false;
    }

    /**
     * Verify if passed $user is allowed for the order
     *
     * @param User $user
     * @return bool
     */
    public function isAllowedForUser(User $user) : bool
    {
        if($this->getUser()->getId() === $user->getId()){
            return true;
        }

        return false;
    }

    /**
     * Verify if Order in the class is partly filled
     *
     * @return bool
     */
    public function isPartlyFilled() : bool
    {
        if($this->isFilled === false && $this->amountFilled > 0){
            return true;
        }

        return false;
    }

    /**
     * Return percentage progress on the amount
     */
    public function progress()
    {
        if($this->amount > 0){
            //return $this->amountFilled / $this->amount * 100;
            return bcmul(bcdiv($this->amountFilled, $this->amount, PriceInterface::BC_SCALE), '100', PriceInterface::BC_SCALE);
        }

        return (string) 0;
    }

    /**
     * Get free amount of base currency
     */
    public function freeAmount()
    {
        //return $this->getAmount() - $this->getAmountFilled();
        return bcsub($this->getAmount(), $this->getAmountFilled(), PriceInterface::BC_SCALE);
    }

    /**
     * Get total amount of base currency including fees
     *
     * @return string
     */
    public function baseCurrencyTotal()
    {
        return $this->getAmount();

        //return $this->getAmount() + $this->getOfferFee();
        //return bcadd($this->getAmount(), $this->getOfferFee(), PriceInterface::BC_SCALE);
    }

    /**
     * Get total FREE amount of base currency including fees
     *
     * @return string
     */
    public function baseCurrencyFreeTotal()
    {
        return $this->freeAmount();

        //return $this->freeAmount() + ($this->freeAmount() * $this->getOfferFeeRate() / 100);

//        $a = bcmul($this->freeAmount(), $this->getOfferFeeRate(), PriceInterface::BC_SCALE);
//        $b = bcdiv($a, '100', PriceInterface::BC_SCALE);
//        $c = bcadd($a, $b, PriceInterface::BC_SCALE);
//
//        return $c;
    }

    /**
     * Get total amount of quoted currency
     *
     * @return string
     */
    public function quotedCurrencyTotal()
    {
        if($this->limitPrice){
            //return $this->getAmount() * $this->limitPrice;
            return bcmul($this->getAmount(), $this->limitPrice, PriceInterface::BC_SCALE);
        }

        return (string) 0;
    }

    /**
     * Get total FREE amount of quoted currency
     *
     * @return string
     */
    public function quotedCurrencyFreeTotal()
    {
        if($this->limitPrice){
            //return $this->freeAmount() * $this->limitPrice;
            return bcmul($this->freeAmount(), $this->limitPrice, PriceInterface::BC_SCALE);
        }

        return (string) 0;
    }

    /**
     * Get total amount of quoted currency calculated on passed $limitPrice
     * @param $limitPrice
     * @return string
     */
    public function quotedCurrencyTotalCalculate($limitPrice)
    {
        //return $this->getAmount() * $limitPrice;
        return bcmul($this->getAmount(), $limitPrice, PriceInterface::BC_SCALE);
    }

    /**
     * Get total FREE amount of quoted currency calculated on passed $limitPrice
     *
     * @param $limitPrice
     * @return string
     */
    public function quotedCurrencyFreeTotalCalculate($limitPrice)
    {
        //return $this->freeAmount() * $limitPrice;
        return bcmul($this->freeAmount(), $limitPrice, PriceInterface::BC_SCALE);
    }

    /**
     * Is the order a bid order - user wants to buy
     *
     * @return bool
     */
    public function isBid(){
        if($this->type === self::TYPE_BUY){
            return true;
        }

        return false;
    }

    /**
     * Is the order a offer order - user wants to sell
     *
     * @return bool
     */
    public function isOffer(){
        if($this->type === self::TYPE_SELL){
            return true;
        }

        return false;
    }

    /**
     * Get hedge order type for the order
     *
     * @return int
     */
    public function hedgeType() : int
    {
        if($this->isBid()){
            return Order::TYPE_SELL;
        }elseif($this->isOffer()){
            return Order::TYPE_BUY;
        }
    }

//    /**
//     * @return bool
//     */
//    public function isPending()
//    {
//        if($this->isFilled) return false;
//        if($this->amountFilled > 0) return false;
//
//        return true;
//    }

    /**
     * @return bool
     */
    public function isNew() : bool
    {
        if($this->status === self::STATUS_NEW){
            return true;
        }

        return false;
    }

    /**
     * Verify if Order in the class is still pending on the market
     *
     * @return bool
     */
    public function isPending() : bool
    {
        if($this->status === self::STATUS_PENDING){
            return true;
        }

        return false;
    }

    /**
     * Verify if Order in the class is rejected
     *
     * @return bool
     */
    public function isRejected() : bool
    {
        if($this->status === self::STATUS_REJECTED){
            return true;
        }

        return false;
    }

    /**
     * @param int $type
     * @return bool
     */
    public function isTypeAllowed(int $type){
        if(self::TYPE_BUY === $type || self::TYPE_SELL === $type){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isUserWalletsValid(){
        if($this->getUser()->getId() === $this->getQuotedCurrencyWallet()->getUser()->getId()){
            if($this->getUser()->getId() === $this->getBaseCurrencyWallet()->getUser()->getId()){
                return true;
            }
        }

        return false;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     *
     * @return bool
     */
    public function validate(ExecutionContextInterface $context)
    {
        if(!$this->isTypeAllowed($this->type)){
            $context->buildViolation(_('Order type now allowed'))->atPath('type')->addViolation();
        }
        if(!$this->isUserWalletsValid()){
            $context->buildViolation(_('User has no permissions to use selected wallets'))->atPath('user')->addViolation();
        }

        return true;
    }

    public function validateType()
    {

    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Wallet
     */
    public function getBaseCurrencyWallet(): Wallet
    {
        return $this->baseCurrencyWallet;
    }

    /**
     * @param Wallet $baseCurrencyWallet
     */
    public function setBaseCurrencyWallet(Wallet $baseCurrencyWallet): void
    {
        $this->baseCurrencyWallet = $baseCurrencyWallet;
    }

    /**
     * @return Wallet
     */
    public function getQuotedCurrencyWallet(): Wallet
    {
        return $this->quotedCurrencyWallet;
    }

    /**
     * @param Wallet $quotedCurrencyWallet
     */
    public function setQuotedCurrencyWallet(Wallet $quotedCurrencyWallet): void
    {
        $this->quotedCurrencyWallet = $quotedCurrencyWallet;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
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
    public function getAmountFilled()
    {
        return $this->amountFilled;
    }

    /**
     * @param mixed $amountFilled
     */
    public function setAmountFilled($amountFilled): void
    {
        $this->amountFilled = $amountFilled;
    }

    /**
     * @return mixed
     */
    public function getisFilled()
    {
        return $this->isFilled;
    }

    /**
     * @param mixed $isFilled
     */
    public function setIsFilled($isFilled): void
    {
        $this->isFilled = $isFilled;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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
     * @return mixed
     */
    public function getExecution()
    {
        return $this->execution;
    }

    /**
     * @param mixed $execution
     */
    public function setExecution($execution): void
    {
        $this->execution = $execution;
    }

    /**
     * @return string|null
     */
    public function getLimitPrice(): ?string
    {
        return $this->limitPrice;
    }

    /**
     * @param string|null $limitPrice
     */
    public function setLimitPrice(?string $limitPrice): void
    {
        $this->limitPrice = $limitPrice;
    }

    /**
     * @return mixed
     */
    public function getAmountBlocked()
    {
        return $this->amountBlocked;
    }

    /**
     * @param mixed $amountBlocked
     */
    public function setAmountBlocked($amountBlocked): void
    {
        $this->amountBlocked = $amountBlocked;
    }

    /**
     * @return bool
     */
    public function isExternalLiquidityOrder(): bool
    {
        return $this->externalLiquidityOrder;
    }

    /**
     * @param bool $externalLiquidityOrder
     */
    public function setExternalLiquidityOrder(bool $externalLiquidityOrder): void
    {
        $this->externalLiquidityOrder = $externalLiquidityOrder;
    }
}
