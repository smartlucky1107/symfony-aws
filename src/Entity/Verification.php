<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VerificationRepository")
 */
class Verification
{
    const STATUS_NEW        = 1;
    const STATUS_SUCCESS    = 2;
    const STATUS_EXPIRED    = 3;
    const STATUS_ERROR      = 4;
    const STATUSES = [
        self::STATUS_NEW        => 'New',
        self::STATUS_SUCCESS    => 'Success',
        self::STATUS_EXPIRED    => 'Expired',
        self::STATUS_ERROR      => 'Error',
    ];
    const ALLOWED_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_SUCCESS,
        self::STATUS_EXPIRED,
        self::STATUS_ERROR,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var User
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $transactionReference = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $redirectUrl = null;

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
     * Verification constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        $this->setStatus(self::STATUS_NEW);
        $this->setCreatedAt(new \DateTime('now'));
        $this->setExpiresAt((clone $this->createdAt)->modify('+10 minutes'));
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        $status = $this->status;
        if($status === Verification::STATUS_ERROR){
            $status = Verification::STATUS_EXPIRED;
        }

        return [
            'id'            => $this->id,
            'status'        => $status,
            'statusName'    => $this->getStatusName(),
            'userReference' => $this->getUserReference(),
            'customerInternalReference' => $this->getCustomerInternalReference(),
            'transactionReference' => $this->transactionReference,
            'redirectUrl' => $this->redirectUrl
        ];
    }

    /**
     * @return bool
     */
    public function isExpired() : bool
    {
        $nowDate = new \DateTime('now');
        if($this->expiresAt < $nowDate) return true;

        return false;
    }

    /**
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
     * @param int $status
     * @return bool
     */
    public function isStatusAllowed(int $status){
        if(in_array($status, self::ALLOWED_STATUSES)){
            return true;
        }

        return false;
    }

    /**
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
     * @return string
     */
    public function getUserReference() : string {
        return 'user_' . ((string) $this->getUser()->getId());
    }

    /**
     * @return string
     */
    public function getCustomerInternalReference() : string {
        return 'verification_' . ((string) $this->getId());
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return string|null
     */
    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    /**
     * @param string|null $transactionReference
     */
    public function setTransactionReference(?string $transactionReference): void
    {
        $this->transactionReference = $transactionReference;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string|null $redirectUrl
     */
    public function setRedirectUrl(?string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
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
     * @return Verification
     */
    public function setCreatedAt(\DateTime $createdAt): Verification
    {
        $this->createdAt = $createdAt;
        return $this;
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
     * @return Verification
     */
    public function setExpiresAt(\DateTime $expiresAt): Verification
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }
}
