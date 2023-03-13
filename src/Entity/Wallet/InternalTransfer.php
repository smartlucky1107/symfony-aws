<?php

namespace App\Entity\Wallet;

use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Wallet\InternalTransferRepository")
 */
class InternalTransfer
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'status'    => 'status',
        'amount'    => 'amount',
    ];

    const STATUS_NEW                = 1;
    const STATUS_REQUEST            = 2;
    const STATUS_APPROVED           = 4;
    const STATUS_DECLINED           = 5;
    const STATUS_REJECTED           = 8;
    const STATUS_REVERTED           = 9;

    const STATUSES = [
        self::STATUS_NEW        => 'New',
        self::STATUS_REQUEST    => 'Request',
        self::STATUS_APPROVED   => 'Approved',
        self::STATUS_DECLINED   => 'Declined',
        self::STATUS_REJECTED   => 'Rejected',
        self::STATUS_REVERTED   => 'Reverted',
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
     * @var Wallet
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="wallet_id", referencedColumnName="id")
     */
    private $wallet;

    /**
     * @var Wallet
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="to_wallet_id", referencedColumnName="id")
     */
    private $toWallet;

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
     * InternalTransfer constructor.
     * @param Wallet $wallet
     * @param Wallet $toWallet
     * @param $amount
     * @param $fee
     * @throws \Exception
     */
    public function __construct(Wallet $wallet, Wallet $toWallet, $amount, $fee)
    {
        $this->wallet = $wallet;
        $this->toWallet = $toWallet;
        $this->amount = $amount;
        $this->fee = $fee;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus( self::STATUS_NEW);
        $this->setConfirmationHash($this->generateConfirmationHash());

        $expiredAt = new \DateTime('now');
        $expiredAt->modify('+5 minutes');
        $this->setConfirmationHashExpiredAt($expiredAt);
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
            'amount'        => $this->toPrecision($this->amount),
            'wallet'        => $this->wallet->serialize(),
            'isNew'         => $this->isNew(),
            'isRequest'     => $this->isRequest(),
            'isApproved'    => $this->isApproved(),
            'isDeclined'    => $this->isDeclined(),
            'isRejected'    => $this->isRejected(),
            'isReverted'    => $this->isReverted(),
            'fee'           => $this->toPrecision($this->fee)
        ];

        if($extended){
            $serialized['toWallet'] = $this->toWallet->serialize();
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
            'fee'           => $this->toPrecision($this->fee)
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
     * Verify if the InternalTransfer has new status
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
     * Verify if the InternalTransfer has request status
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
     * Verify if the InternalTransfer has rejected status
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
     * Verify if the InternalTransfer has reverted status
     *
     * @return bool
     */
    public function isReverted(){
        if($this->status === self::STATUS_REVERTED){
            return true;
        }

        return false;
    }

    /**
     * Verify if the InternalTransfer has approved status
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
     * Verify if the InternalTransfer has declined status
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
     * @return Wallet
     */
    public function getToWallet(): Wallet
    {
        return $this->toWallet;
    }

    /**
     * @param Wallet $toWallet
     */
    public function setToWallet(Wallet $toWallet): void
    {
        $this->toWallet = $toWallet;
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
}
