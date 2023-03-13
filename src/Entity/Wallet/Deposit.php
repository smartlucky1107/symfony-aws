<?php

namespace App\Entity\Wallet;

use App\Entity\Wallet\Wallet;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Wallet\DepositRepository")
 */
class Deposit
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'status'    => 'status',
        'createdAt' => 'createdAt',
        'approvedAt'=> 'approvedAt'
    ];

    const STATUS_REQUEST = 1;
    const STATUS_APPROVED = 2;
    const STATUS_DECLINED = 3;
    const STATUS_REVERTED = 4;

    const STATUSES = [
        self::STATUS_REQUEST    => 'Request',
        self::STATUS_APPROVED   => 'Approved',
        self::STATUS_DECLINED   => 'Declined',
        self::STATUS_REVERTED   => 'Reverted'
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
     * @Assert\NotBlank()
     */
    private $createdAt;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $approvedAt;

    /**
     * @var Wallet
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="wallet_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $wallet;

    /**
     * @ORM\Column(type="decimal", precision=36, scale=18)
     * @Assert\NotBlank()
     */
    private $amount;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="added_by_user_id", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank()
     */
    private $addedByUser;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="approved_by_user_id", referencedColumnName="id", nullable=true)
     */
    private $approvedByUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $bankTransactionDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $bankTransactionId;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $blockchainTransactionHash;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $blockchainAddress;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $giifReportAssigned = false;

    /**
     * Deposit constructor.
     * @param Wallet $wallet
     * @param $amount
     * @param User $addedByUser
     * @param string $bankTransactionDate
     * @param string $bankTransactionId
     * @throws \Exception
     */
    public function __construct(Wallet $wallet, $amount, User $addedByUser, string $bankTransactionDate, string $bankTransactionId)
    {
        $this->wallet = $wallet;
        $this->amount = $amount;
        $this->addedByUser = $addedByUser;
        $this->bankTransactionDate = $bankTransactionDate;
        $this->bankTransactionId = $bankTransactionId;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus( self::STATUS_REQUEST);
        $this->setGiifReportAssigned(false);
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getWallet()->getCurrency()->getRoundPrecision());
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
     * Serialize and return public data of the object
     *
     * @param bool $extended
     * @return array
     */
    public function serialize(bool $extended = false) : array
    {
        $serialized = [
            'id'                => $this->id,
            'status'            => $this->status,
            'statusName'        => $this->getStatusName(),
            'isRequest'         => $this->isRequest(),
            'isApproved'        => $this->isApproved(),
            'isDeclined'        => $this->isDeclined(),
            'isReverted'        => $this->isReverted(),
            'createdAt'         => $this->createdAt->format('c'),
            'approvedAt'        => $this->approvedAt ? $this->approvedAt->format('c') : null,
            'wallet'            => $this->wallet->serialize(),
            'amount'            => $this->toPrecision($this->amount),
            'bankTransactionDate'   => $this->bankTransactionDate,
            'bankTransactionId'     => $this->bankTransactionId,
            'blockchainTransactionHash'     => $this->blockchainTransactionHash,
            'blockchainAddress'     => $this->blockchainAddress,
        ];

        if($extended){
            $serialized['addedByUser'] = $this->addedByUser->serializeBasic();
            $serialized['approvedByUser'] = ($this->approvedByUser instanceof User ? $this->approvedByUser->serializeBasic() : null);
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
            'id'                => $this->id,
            'status'            => $this->status,
            'statusName'        => $this->getStatusName(),
            'amount'            => $this->toPrecision($this->amount),
        ];
    }

    /**
     * Verify if the deposit has request status
     *
     * @return bool
     */
    public function isRequest(){
        if($this->status === self::STATUS_REQUEST){
            return true;
        }

        return false;
    }

    /**
     * Verify is the deposit has approved status
     *
     * @return bool
     */
    public function isApproved(){
        if($this->status === self::STATUS_APPROVED){
            return true;
        }

        return false;
    }

    /**
     * Verify is the deposit has declined status
     *
     * @return bool
     */
    public function isDeclined(){
        if($this->status === self::STATUS_DECLINED){
            return true;
        }

        return false;
    }

    /**
     * Verify is the deposit has reverted status
     *
     * @return bool
     */
    public function isReverted(){
        if($this->status === self::STATUS_REVERTED){
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
     * @return \DateTime|null
     */
    public function getApprovedAt(): ?\DateTime
    {
        return $this->approvedAt;
    }

    /**
     * @param \DateTime|null $approvedAt
     */
    public function setApprovedAt(?\DateTime $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    /**
     * @return Wallet
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * @param Wallet $wallet
     */
    public function setWallet(Wallet $wallet): void
    {
        $this->wallet = $wallet;
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
     * @return User
     */
    public function getAddedByUser(): User
    {
        return $this->addedByUser;
    }

    /**
     * @param User $addedByUser
     */
    public function setAddedByUser(User $addedByUser): void
    {
        $this->addedByUser = $addedByUser;
    }

    /**
     * @return User|null
     */
    public function getApprovedByUser(): ?User
    {
        return $this->approvedByUser;
    }

    /**
     * @param User|null $approvedByUser
     */
    public function setApprovedByUser(?User $approvedByUser): void
    {
        $this->approvedByUser = $approvedByUser;
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
    public function getBankTransactionDate(): string
    {
        return $this->bankTransactionDate;
    }

    /**
     * @param string $bankTransactionDate
     */
    public function setBankTransactionDate(string $bankTransactionDate): void
    {
        $this->bankTransactionDate = $bankTransactionDate;
    }

    /**
     * @return string
     */
    public function getBankTransactionId(): string
    {
        return $this->bankTransactionId;
    }

    /**
     * @param string $bankTransactionId
     */
    public function setBankTransactionId(string $bankTransactionId): void
    {
        $this->bankTransactionId = $bankTransactionId;
    }

    /**
     * @return string|null
     */
    public function getBlockchainTransactionHash(): ?string
    {
        return $this->blockchainTransactionHash;
    }

    /**
     * @param string|null $blockchainTransactionHash
     */
    public function setBlockchainTransactionHash(?string $blockchainTransactionHash): void
    {
        $this->blockchainTransactionHash = $blockchainTransactionHash;
    }

    /**
     * @return string|null
     */
    public function getBlockchainAddress(): ?string
    {
        return $this->blockchainAddress;
    }

    /**
     * @param string|null $blockchainAddress
     */
    public function setBlockchainAddress(?string $blockchainAddress): void
    {
        $this->blockchainAddress = $blockchainAddress;
    }

    /**
     * @return bool
     */
    public function isGiifReportAssigned(): bool
    {
        return $this->giifReportAssigned;
    }

    /**
     * @param bool $giifReportAssigned
     */
    public function setGiifReportAssigned(bool $giifReportAssigned): void
    {
        $this->giifReportAssigned = $giifReportAssigned;
    }
}
