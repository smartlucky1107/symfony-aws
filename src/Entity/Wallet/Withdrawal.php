<?php

namespace App\Entity\Wallet;

use App\Entity\User;
use App\Entity\UserBank;
use App\Entity\Wallet\WalletBank;
use App\Entity\Wallet\Wallet;
use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Wallet\WithdrawalRepository")
 */
class Withdrawal
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'status'    => 'status',
        'amount'    => 'amount',
        'address'   => 'address',
        'approvedAt'=> 'approvedAt'
    ];

    const STATUS_NEW                = 1;
    const STATUS_REQUEST            = 2;
    const STATUS_EXTERNAL_APPROVAL  = 3;
    const STATUS_APPROVED           = 4;
    const STATUS_DECLINED           = 5;
    const STATUS_REJECTED           = 8;

    const STATUSES = [
        self::STATUS_NEW        => 'New',
        self::STATUS_REQUEST    => 'Request',
        self::STATUS_EXTERNAL_APPROVAL    => 'External approval',
        self::STATUS_APPROVED   => 'Approved',
        self::STATUS_DECLINED   => 'Declined',
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
     * @Assert\NotBlank
     * @ORM\Column(type="datetime")
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
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="approved_by_user_id", referencedColumnName="id", nullable=true)
     */
    private $approvedByUser;

    /**
     * @var Wallet
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="wallet_id", referencedColumnName="id")
     */
    private $wallet;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amount;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $fee;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=512)
     */
    private $address;

    /**
     * @var UserBank|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserBank")
     * @ORM\JoinColumn(name="user_bank_id", referencedColumnName="id", nullable=true)
     */
    private $userBank;

    /**
     * @var WalletBank|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\WalletBank")
     * @ORM\JoinColumn(name="wallet_bank_id", referencedColumnName="id", nullable=true)
     */
    private $walletBank;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $confirmationHash;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirmationHashExpiredAt;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $giifReportAssigned = false;

    /**
     * Withdrawal constructor.
     * @param Wallet $wallet
     * @param string $amount
     * @param string $fee
     * @param string $address
     * @throws \Exception
     */
    public function __construct(Wallet $wallet, string $amount, string $fee, string $address)
    {
        $this->wallet = $wallet;
        $this->amount = $amount;
        $this->fee = $fee;
        $this->address = $address;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus( self::STATUS_NEW);
        $this->setUserBank(null);
        $this->setWalletBank(null);
        $this->setConfirmationHash($this->generateConfirmationHash());

        $expiredAt = new \DateTime('now');
        $expiredAt->modify('+5 minutes');
        $this->setConfirmationHashExpiredAt($expiredAt);
        $this->setGiifReportAssigned(false);
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
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->format('c'),
            'status'        => $this->status,
            'statusName'    => $this->getStatusName(),
            'approvedAt'    => $this->approvedAt ? $this->approvedAt->format('c') : null,
            'amount'        => $this->toPrecision($this->amount),
            'address'       => $this->address,
            'wallet'        => $this->wallet->serialize(),
            'isNew'         => $this->isNew(),
            'isRequest'     => $this->isRequest(),
            'isExternalApproval' => $this->isExternalApproval(),
            'isApproved'    => $this->isApproved(),
            'isDeclined'    => $this->isDeclined(),
            'isRejected'    => $this->isRejected(),
            'fee'           => $this->toPrecision($this->fee),
        ];

        if($extended){
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
            'id'            => $this->id,
            'amount'        => $this->toPrecision($this->amount),
            'address'       => $this->address,
            'fee'           => $this->toPrecision($this->fee),
            'walletId'      => $this->wallet->getId(),
        ];
    }

    /**
     * @return array
     */
    public function serializeForTransferApp() : array
    {
        /** @var Wallet $withdrawalWallet */
        $withdrawalWallet = $this->getWallet();

        return [
            'id'            => $this->id,
            'amount'        => $this->toPrecision($this->amount),
            'address'       => $this->address,
            'walletId'      => $withdrawalWallet->getId(),
            'walletType'    => $withdrawalWallet->getCurrency()->getType(),
            'walletSmartContract' => $withdrawalWallet->getCurrency()->getSmartContractAddress(),
            'confirmationHash' => $this->confirmationHash,
        ];
    }

    /**
     * @return string
     */
    public function generateConfirmationHash() : string
    {
        return substr((string) md5(uniqid() . rand(10000, 1000000)), 0, 10);
    }

    /**
     * @param string $confirmationHash
     * @return bool
     */
    public function isConfirmationHashValid(string $confirmationHash) : bool
    {
        if($this->confirmationHash === $confirmationHash){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isConfirmationHashExpired() : bool
    {
        $nowDate = new \DateTime('now');
        if($this->confirmationHashExpiredAt < $nowDate){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTotalAmount()
    {
        return bcadd($this->amount, $this->fee, PriceInterface::BC_SCALE);
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getWallet()->getCurrency()->getRoundPrecision());
    }

    /**
     * Verify if the withdrawal has new status
     *
     * @return bool
     */
    public function isNew(){
        if($this->status === self::STATUS_NEW){
            return true;
        }

        return false;
    }

    /**
     * Verify if the withdrawal has request status
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
     * Verify if the withdrawal has rejected status
     *
     * @return bool
     */
    public function isRejected(){
        if($this->status === self::STATUS_REJECTED){
            return true;
        }

        return false;
    }

    /**
     * Verify if the withdrawal has approved status
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
     * Verify if the withdrawal has external approval status
     *
     * @return bool
     */
    public function isExternalApproval(){
        if($this->status === self::STATUS_EXTERNAL_APPROVAL){
            return true;
        }

        return false;
    }

    /**
     * Verify if the withdrawal has declined status
     *
     * @return bool
     */
    public function isDeclined(){
        if($this->status === self::STATUS_DECLINED){
            return true;
        }

        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
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
     * @return mixed
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param mixed $fee
     */
    public function setFee($fee): void
    {
        $this->fee = $fee;
    }

    /**
     * @return UserBank|null
     */
    public function getUserBank(): ?UserBank
    {
        return $this->userBank;
    }

    /**
     * @param UserBank|null $userBank
     */
    public function setUserBank(?UserBank $userBank): void
    {
        $this->userBank = $userBank;
    }

    /**
     * @return WalletBank|null
     */
    public function getWalletBank(): ?WalletBank
    {
        return $this->walletBank;
    }

    /**
     * @param WalletBank|null $walletBank
     */
    public function setWalletBank(?WalletBank $walletBank): void
    {
        $this->walletBank = $walletBank;
    }

    /**
     * @return string|null
     */
    public function getConfirmationHash(): ?string
    {
        return $this->confirmationHash;
    }

    /**
     * @param string|null $confirmationHash
     */
    public function setConfirmationHash(?string $confirmationHash): void
    {
        $this->confirmationHash = $confirmationHash;
    }

    /**
     * @return \DateTime|null
     */
    public function getConfirmationHashExpiredAt(): ?\DateTime
    {
        return $this->confirmationHashExpiredAt;
    }

    /**
     * @param \DateTime|null $confirmationHashExpiredAt
     */
    public function setConfirmationHashExpiredAt(?\DateTime $confirmationHashExpiredAt): void
    {
        $this->confirmationHashExpiredAt = $confirmationHashExpiredAt;
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
