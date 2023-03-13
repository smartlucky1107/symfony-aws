<?php

namespace App\Entity;

use App\Entity\OrderBook\Order;
use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CheckoutOrderRepository")
 * @ORM\Table(name="checkout_order", indexes={
 *     @ORM\Index(name="search_idx1", columns={"status", "amount", "total_price"}),
 *     @ORM\Index(name="search_idx2", columns={"created_at"})
 * })
 */
class CheckoutOrder
{
    const DEFAULT_SORT_FIELD = 'createdAt';
    const ALLOWED_SORT_FIELDS = [
        'id'            => 'id',
        'type'          => 'type',
        'status'        => 'status',
        'amount'        => 'amount',
        'createdAt'     => 'createdAt',
    ];

    const TYPE_BUY = 1;
    const TYPE_SELL = 2;
    const TYPES = [
        self::TYPE_BUY      => 'Buy',
        self::TYPE_SELL     => 'Sell'
    ];

    const ALLOWED_TYPES = [
        self::TYPE_BUY,
        self::TYPE_SELL
    ];

//    const STATUS_NEW                = 1;    // Created by user as new
    const STATUS_REJECTED           = 2;    // Rejected
    const STATUS_PENDING            = 3;    // Set by processor as pending
    const STATUS_PAYMENT_INIT       = 4;    // Redirected to payment processor
//    const STATUS_PAYMENT_PROCESSING = 5;    // Returned from payment processor
    const STATUS_PAYMENT_SUCCESS    = 6;    // Payment received with success
    const STATUS_PROCESSING         = 7;    // Processing in the Processor
//    const STATUS_DEPOSIT_CREATED    = 8;    // After successful payment system will create approved deposit the the CheckoutUser account - it needs to be in processor
    const STATUS_COMPLETED          = 100;  //

    const STATUSES = [
//        self::STATUS_NEW                => 'New',
        self::STATUS_REJECTED           => 'Rejected',
        self::STATUS_PENDING            => 'Pending',
        self::STATUS_PAYMENT_INIT       => 'Payment initialized',
//        self::STATUS_PAYMENT_PROCESSING => 'Payment processing',
        self::STATUS_PAYMENT_SUCCESS    => 'Payment success',
        self::STATUS_PROCESSING         => 'Processing',
//        self::STATUS_DEPOSIT_CREATED    => 'Deposit created',
        self::STATUS_COMPLETED          => 'Completed',
    ];

    const ALLOWED_STATUSES = [
//        self::STATUS_NEW,
        self::STATUS_REJECTED,
        self::STATUS_PENDING,
        self::STATUS_PAYMENT_INIT,
//        self::STATUS_PAYMENT_PROCESSING,
        self::STATUS_PAYMENT_SUCCESS,
        self::STATUS_PROCESSING,
//        self::STATUS_DEPOSIT_CREATED,
        self::STATUS_COMPLETED,
    ];

    const CHECKOUT_FEE_RATE = 3;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     *
     * @SWG\Property(description="ID of the object", example="00000000-0000-0000-0000-08002788901c")
     * @Serializer\Groups({"output"})
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $expiresAt;

    /**
     * @var User
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var CurrencyPair
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\CurrencyPair")
     * @ORM\JoinColumn(name="currency_pair_id", referencedColumnName="id")
     */
    private $currencyPair;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getAllowedTypes")
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getAllowedStatuses")
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amount;

    /**
     * amount * initiationPrice
     *
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $totalPrice;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $checkoutFee;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $paymentProcessorFee;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $totalPaymentValue;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $signature;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $initiationPrice;

    /**
     * @var PaymentProcessor
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\PaymentProcessor")
     * @ORM\JoinColumn(nullable=false)
     *
     * @SWG\Property(ref=@Model(type=PaymentProcessor::class))
     * @Serializer\Groups({"output"})
     */
    private $paymentProcessor;

    /**
     * @var PaymentCard|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\PaymentCard")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    private $paymentCard = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $paymentUrl = null;

    /**
     * CheckoutOrder constructor.
     * @param User $user
     * @param CurrencyPair $currencyPair
     * @param int $type
     * @param string $amount
     * @param string $initiationPrice
     * @param string $paymentFeeRate
     * @throws \Exception
     */
    public function __construct(User $user, CurrencyPair $currencyPair, int $type, string $amount, string $initiationPrice, string $paymentFeeRate)
    {
        $this->user = $user;
        $this->currencyPair = $currencyPair;
        $this->type = $type;
        $this->amount = $amount;
        $this->initiationPrice = $initiationPrice;

        $this->totalPrice   = self::calculateTotalPrice($this->initiationPrice, $this->amount);
        $this->checkoutFee  =  self::calculateCheckoutFee($this->totalPrice);
        $this->paymentProcessorFee  = self::calculatePaymentProcessorFee($this->totalPrice, $this->checkoutFee, $paymentFeeRate);
        $this->totalPaymentValue    = self::calculateTotalPayment($this->totalPrice, $this->checkoutFee, $this->paymentProcessorFee);

        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus(self::STATUS_PENDING);
        $this->setSignature($this->generateSignature());
        $this->setExpiresAt((clone $this->createdAt)->modify('+7 minutes'));
    }

    /**
     * @param string $initiationPrice
     * @param string $amount
     * @return string
     */
    static public function calculateTotalPrice(string $initiationPrice, string $amount) : string
    {
        return bcmul($initiationPrice, $amount, PriceInterface::BC_SCALE);
    }

    /**
     * @param string $totalPrice
     * @return string
     */
    static public function calculateCheckoutFee(string $totalPrice) : string
    {
        return bcdiv(bcmul($totalPrice, self::CHECKOUT_FEE_RATE, PriceInterface::BC_SCALE), 100, PriceInterface::BC_SCALE);
    }

    /**
     * @param string $totalPrice
     * @param string $checkoutFee
     * @param string $paymentFeeRate
     * @return string
     */
    static public function calculatePaymentProcessorFee(string $totalPrice, string $checkoutFee, string $paymentFeeRate) : string
    {
        $total = bcadd($totalPrice, $checkoutFee, PriceInterface::BC_SCALE);

        return bcdiv(bcmul($total, $paymentFeeRate, PriceInterface::BC_SCALE), 100, PriceInterface::BC_SCALE);
    }

    /**
     * @param string $totalPrice
     * @param string $checkoutFee
     * @param string $paymentFee
     * @return string
     */
    static public function calculateTotalPayment(string $totalPrice, string $checkoutFee, string $paymentFee) : string
    {
        // TODO
        // TODO !!! zmienić to że by bylo automatycznie brane z quoted currency
        // TODO

        $total = bcadd($totalPrice, $checkoutFee, PriceInterface::BC_SCALE);

        return bcadd($total, $paymentFee, 2);
//        return bcadd($totalPrice, $paymentFee, PriceInterface::BC_SCALE);
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
            'currencyPair'  => $this->currencyPair->serialize(),
            'type'          => $this->type,
            'typeName'      => $this->getTypeName(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'amount'        => $this->toPrecision($this->amount),
            'initiationPrice'=> $this->toPrecisionQuoted($this->initiationPrice),
            'totalPaymentValue' => $this->totalPaymentValue ? $this->toPrecisionQuoted($this->totalPaymentValue) : null,
            'isBid'         => $this->isBid(),
            'isOffer'       => $this->isOffer(),
            'paymentUrl'    => $this->paymentUrl
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
            'initiationPrice'=> $this->toPrecisionQuoted($this->initiationPrice),
            'totalPaymentValue' => $this->totalPaymentValue ? $this->toPrecisionQuoted($this->totalPaymentValue) : null
        ];
    }

    /**
     * @return array
     */
    public function serializeForPrivateApi() : array
    {
        return [
            'id'            => $this->id,
            'currencyPair'  => $this->currencyPair->serializeForPrivateApi(),
            'type'          => $this->type,
            'typeName'      => $this->getTypeName(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'initiationPrice'=> $this->toPrecisionQuoted($this->initiationPrice),
        ];
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isExpired() : bool
    {
        $nowDate = new \DateTime('now');
        if($this->expiresAt < $nowDate) return true;

        return false;
    }

    /**
     * Get allowed types as simple array.
     *
     * @return array
     */
    public static function getAllowedTypes(){
        return self::ALLOWED_TYPES;
    }

    /**
     * Get allowed Statuses as simple array.
     *
     * @return array
     */
    public static function getAllowedStatuses(){
        return self::ALLOWED_STATUSES;
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

    /**
     * Generate trade signature.
     *
     * @return string
     */
    public function generateSignature(){
        return md5($this->amount . $this->totalPrice . $this->getUser()->getId(). uniqid() . (string) $this->initiationPrice);
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
     * @return string
     */
    public function getId(): string
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
     * @return \DateTime
     */
    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    /**
     * @param \DateTime $expiresAt
     */
    public function setExpiresAt(\DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
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
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
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
    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    /**
     * @return string
     */
    public function getCheckoutFee(): string
    {
        return $this->checkoutFee;
    }

    /**
     * @param string $checkoutFee
     */
    public function setCheckoutFee(string $checkoutFee): void
    {
        $this->checkoutFee = $checkoutFee;
    }

    /**
     * @return string
     */
    public function getPaymentProcessorFee(): string
    {
        return $this->paymentProcessorFee;
    }

    /**
     * @param string $paymentProcessorFee
     * @return CheckoutOrder
     */
    public function setPaymentProcessorFee(string $paymentProcessorFee): CheckoutOrder
    {
        $this->paymentProcessorFee = $paymentProcessorFee;
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
     * @return CheckoutOrder
     */
    public function setTotalPaymentValue(string $totalPaymentValue): CheckoutOrder
    {
        $this->totalPaymentValue = $totalPaymentValue;
        return $this;
    }

    /**
     * @param string $totalPrice
     */
    public function setTotalPrice(string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getInitiationPrice(): string
    {
        return $this->initiationPrice;
    }

    /**
     * @param string $initiationPrice
     */
    public function setInitiationPrice(string $initiationPrice): void
    {
        $this->initiationPrice = $initiationPrice;
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
     * @return CheckoutOrder
     */
    public function setPaymentProcessor(PaymentProcessor $paymentProcessor): CheckoutOrder
    {
        $this->paymentProcessor = $paymentProcessor;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    /**
     * @param string|null $paymentUrl
     * @return CheckoutOrder
     */
    public function setPaymentUrl(?string $paymentUrl): CheckoutOrder
    {
        $this->paymentUrl = $paymentUrl;
        return $this;
    }

    /**
     * @return PaymentCard|null
     */
    public function getPaymentCard(): ?PaymentCard
    {
        return $this->paymentCard;
    }

    /**
     * @param PaymentCard|null $paymentCard
     * @return CheckoutOrder
     */
    public function setPaymentCard(?PaymentCard $paymentCard): CheckoutOrder
    {
        $this->paymentCard = $paymentCard;
        return $this;
    }
}
