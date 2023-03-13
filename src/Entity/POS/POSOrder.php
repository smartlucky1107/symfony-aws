<?php

namespace App\Entity\POS;

use App\Entity\CurrencyPair;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * @ORM\Entity(repositoryClass="App\Repository\POS\POSOrderRepository")
 * @ORM\Table(name="pos_order", indexes={
 *     @ORM\Index(name="search_idx1", columns={"status", "amount", "total_price"}),
 *     @ORM\Index(name="search_idx2", columns={"created_at"})
 * })
 */
class POSOrder
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'            => 'id',
        'status'        => 'status',
        'amount'        => 'amount',
    ];

    const STATUS_INIT       = 0;
    const STATUS_NEW        = 1;
    const STATUS_REJECTED   = 2;

    const STATUS_PROCESSING = 7;    // Processing in the Processor

    const STATUS_REDEEM_INTERNAL_INIT       = 11;
    const STATUS_REDEEM_INTERNAL_PROCESSING = 12;

    const STATUS_REDEEM_EXTERNAL_INIT       = 21;
    const STATUS_REDEEM_EXTERNAL_PROCESSING = 22;

    const STATUS_COMPLETED          = 100;
    const STATUS_REDEEM_COMPLETED   = 101;

    const ALLOWED_STATUSES = [
        self::STATUS_INIT,
        self::STATUS_NEW,
        self::STATUS_REJECTED,

        self::STATUS_PROCESSING,

        self::STATUS_REDEEM_INTERNAL_INIT,
        self::STATUS_REDEEM_INTERNAL_PROCESSING,

        self::STATUS_REDEEM_EXTERNAL_INIT,
        self::STATUS_REDEEM_EXTERNAL_PROCESSING,

        self::STATUS_COMPLETED,
        self::STATUS_REDEEM_COMPLETED,
    ];
    const STATUSES = [
        self::STATUS_INIT           => 'Created but not confirmed by Employee',
        self::STATUS_NEW            => 'Created and confirmed by Employee',
        self::STATUS_REJECTED       => 'Rejected by the system',

        self::STATUS_PROCESSING     => 'Processing',

        self::STATUS_REDEEM_INTERNAL_INIT           => 'Internal redeem started',
        self::STATUS_REDEEM_INTERNAL_PROCESSING     => 'Internal redeem processing',

        self::STATUS_REDEEM_EXTERNAL_INIT           => 'External redeem started',
        self::STATUS_REDEEM_EXTERNAL_PROCESSING     => 'External redeem processing',

        self::STATUS_COMPLETED                      => 'Completed',
        self::STATUS_REDEEM_COMPLETED               => 'Redeem completed',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @SWG\Property(description="ID of the object", example="1")
     * @Serializer\Groups({"output_redeem"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $signature;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $redeemHash;

    /**
     * @var int
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid type."
     * )
     * @Assert\GreaterThan(
     *     value = 100000
     * )
     * @ORM\Column(type="integer")
     */
    private $redeemCode;

    /**
     * @var int
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid type."
     * )
     * @Assert\GreaterThan(
     *     value = 100000
     * )
     * @ORM\Column(type="integer")
     */
    private $redeemTransferCode;

    /**
     * @var int
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid type."
     * )
     * @Assert\GreaterThan(
     *     value = 100000
     * )
     * @ORM\Column(type="integer")
     */
    private $confirmationCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var Workspace
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\POS\Workspace")
     * @ORM\JoinColumn(name="workspace_id", referencedColumnName="id")
     *
     * @SWG\Property(ref=@Model(type=Workspace::class))
     * @Serializer\Groups({"output_redeem"})
     */
    private $workspace;

    /**
     * @var Employee
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\POS\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="id")
     */
    private $employee;

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
     * @ORM\Column(type="integer")
     *
     * @SWG\Property(description="Status", example="1")
     * @Serializer\Groups({"output_redeem"})
     */
    private $status;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     *
     * @SWG\Property(description="Amount of base currenct", example="0.001")
     * @Serializer\Groups({"output_redeem"})
     */
    private $amount;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     *
     * @SWG\Property(description="Total price in quoted currency", example="4500")
     * @Serializer\Groups({"output_redeem"})
     */
    private $totalPrice;


//* @Assert\NotBlank(message = "Phone should not be blank.")
    /**
     * @var string|null
     *
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $initiationPrice;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $expiresAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sentAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $externalAddress;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\POS\POSReceipt", mappedBy="POSOrder", orphanRemoval=true)
     */
    private $receipts;

    /**
     * POSOrder constructor.
     * @param Employee $employee
     * @param CurrencyPair $currencyPair
     * @param string $amount
     * @param string $totalPrice
     * @param string $initiationPrice
     * @throws \Exception
     */
    public function __construct(Employee $employee, CurrencyPair $currencyPair, string $amount, string $totalPrice, string $initiationPrice)
    {
        $this->employee = $employee;
        $this->currencyPair = $currencyPair;
        $this->amount = $amount;
        $this->totalPrice = $totalPrice;
        $this->initiationPrice = $initiationPrice;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus(self::STATUS_INIT);
        $this->setWorkspace($employee->getWorkspace());
        $this->setSignature($this->generateSignature());
        $this->setConfirmationCode($this->generateRedeemCode());
        $this->setRedeemHash($this->generateRedeemHash());
        $this->setRedeemCode($this->generateRedeemCode());
        $this->setRedeemTransferCode($this->generateRedeemCode());
        $this->setExpiresAt((clone $this->createdAt)->modify('+1 minute'));
        $this->receipts = new ArrayCollection();
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
            'workspace'     => $this->workspace->serialize(),
            'employee'      => $this->employee->serialize(),
            'currencyPair'  => $this->currencyPair->serialize(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'amount'        => $this->toPrecision($this->amount),
            'totalPrice'    => $this->toPrecisionQuoted($this->totalPrice),
            'phone'         => $this->phone,
            'initiationPrice'=> $this->toPrecisionQuoted($this->initiationPrice),
            'expiresAt'     => $this->expiresAt->format('c'),
        ];

        if($extended){
        }

        return $serialized;
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
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'amount'        => $this->toPrecision($this->amount),
            'totalPrice'    => $this->totalPrice,
            'phone'         => $this->phone,
            'initiationPrice'=> $this->toPrecisionQuoted($this->initiationPrice),
            'employee'      => $this->employee->serializeBasic(),
        ];
    }

    /**
     * @return array
     */
    public function serializeForPOSApi() : array
    {
        return [
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->format('c'),
            'currencyPair'  => $this->currencyPair->serializeForPOSApi(),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'amount'        => $this->toPrecision($this->amount),
            'totalPrice'    => $this->toPrecisionQuoted($this->totalPrice),
            'phone'         => $this->phone,
            'initiationPrice'=> $this->toPrecisionQuoted($this->initiationPrice),
            'expiresAt'     => $this->expiresAt->format('c'),
        ];
    }

    /**
     * @return array
     */
    public function serializeForRedeem() : array
    {
        return [
            'createdAt'     => $this->createdAt->format('c'),
            'status'        => $this->status,
            'amount'        => $this->toPrecision($this->amount),
            'amountCurrency'        => $this->getCurrencyPair()->getBaseCurrency()->getShortName(),
            'priceCurrency'        => $this->getCurrencyPair()->getQuotedCurrency()->getShortName(),
            'totalPrice'    => $this->toPrecisionQuoted($this->totalPrice),
            'workspace'     => $this->getEmployee()->getWorkspace()->serializeForRedeem()
        ];
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
     * Verify if passed $user is allowed for the order
     *
     * @param User $user
     * @return bool
     */
    public function isAllowedForUser(User $user) : bool
    {
        if($this->getWorkspace()->getUser()->getId() === $user->getId()){
            return true;
        }

        return false;
    }

    /**
     * @param string $confirmationCode
     * @return bool
     */
    public function isConfirmationCodeValid(string $confirmationCode) : bool
    {
        if(!empty($this->confirmationCode) && (string) $this->confirmationCode === $confirmationCode){
            return true;
        }

        return false;
    }

    /**
     * @param string $redeemCode
     * @return bool
     */
    public function isRedeemCodeValid(string $redeemCode) : bool
    {
        if(!empty($this->redeemCode) && (string) $this->redeemCode === $redeemCode){
            return true;
        }

        return false;
    }

    /**
     * @param string $redeemTransferCode
     * @return bool
     */
    public function isRedeemTransferCodeValid(string $redeemTransferCode) : bool
    {
        if(!empty($this->redeemTransferCode) && (string) $this->redeemTransferCode === $redeemTransferCode){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isInit() : bool
    {
        if($this->status === self::STATUS_INIT){
            return true;
        }

        return false;
    }

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
     * Verify if Order in the class is completed
     *
     * @return bool
     */
    public function isCompleted() : bool
    {
        if($this->status === self::STATUS_COMPLETED){
            return true;
        }

        return false;
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
     * Generate signature.
     *
     * @return string
     */
    public function generateSignature(){
        return md5($this->amount . $this->totalPrice . $this->getWorkspace()->getId() . $this->getEmployee()->getId() . uniqid() . (string) $this->phone);
    }

    /**
     * Generate RedeemHash.
     *
     * @return string
     */
    public function generateRedeemHash(){
        return substr(md5(uniqid(). $this->signature . $this->totalPrice . $this->amount . (string) $this->phone . $this->getWorkspace()->getId() . $this->getEmployee()->getId() . uniqid()), 5, 7);
    }

    /**
     * Generate redeem code for SMS or E-mail
     *
     * @return int
     */
    public function generateRedeemCode()
    {
        return rand(100000, 1000000);
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
     * @return Workspace
     */
    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    /**
     * @param Workspace $workspace
     */
    public function setWorkspace(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    /**
     * @return Employee
     */
    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    /**
     * @param Employee $employee
     */
    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
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
     * @param string $totalPrice
     */
    public function setTotalPrice(string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
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
     * @return POSOrder
     */
    public function setSignature(string $signature): POSOrder
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedeemHash(): string
    {
        return $this->redeemHash;
    }

    /**
     * @param string $redeemHash
     */
    public function setRedeemHash(string $redeemHash): void
    {
        $this->redeemHash = $redeemHash;
    }

    /**
     * @return int
     */
    public function getRedeemCode(): int
    {
        return $this->redeemCode;
    }

    /**
     * @param int $redeemCode
     */
    public function setRedeemCode(int $redeemCode): void
    {
        $this->redeemCode = $redeemCode;
    }

    /**
     * @return int
     */
    public function getConfirmationCode(): int
    {
        return $this->confirmationCode;
    }

    /**
     * @param int $confirmationCode
     */
    public function setConfirmationCode(int $confirmationCode): void
    {
        $this->confirmationCode = $confirmationCode;
    }

    /**
     * @return int
     */
    public function getRedeemTransferCode(): int
    {
        return $this->redeemTransferCode;
    }

    /**
     * @param int $redeemTransferCode
     */
    public function setRedeemTransferCode(int $redeemTransferCode): void
    {
        $this->redeemTransferCode = $redeemTransferCode;
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
     * @return \DateTime|null
     */
    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTime|null $sentAt
     */
    public function setSentAt(?\DateTime $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    /**
     * @return string|null
     */
    public function getExternalAddress(): ?string
    {
        return $this->externalAddress;
    }

    /**
     * @param string|null $externalAddress
     */
    public function setExternalAddress(?string $externalAddress): void
    {
        $this->externalAddress = $externalAddress;
    }

    /**
     * @return Collection|POSReceipt[]
     */
    public function getReceipts(): Collection
    {
        return $this->receipts;
    }

    public function addReceipt(POSReceipt $receipt): self
    {
        if (!$this->receipts->contains($receipt)) {
            $this->receipts[] = $receipt;
            $receipt->setPOSOrder($this);
        }

        return $this;
    }

    public function removeReceipt(POSReceipt $receipt): self
    {
        if ($this->receipts->contains($receipt)) {
            $this->receipts->removeElement($receipt);
            // set the owning side to null (unless already changed)
            if ($receipt->getPOSOrder() === $this) {
                $receipt->setPOSOrder(null);
            }
        }

        return $this;
    }
}
