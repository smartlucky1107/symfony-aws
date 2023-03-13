<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentCardRegistrationRepository")
 */
class PaymentCardRegistration
{
    const STATUS_NEW        = 'NEW';
    const STATUS_PENDING    = 'PENDING';
    const STATUS_VERIFIED   = 'VERIFIED';
    const STATUS_REJECTED   = 'REJECTED';
    const ALLOWED_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
    ];

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
     * @var User
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    private $registrationId;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedStatuses")
     *
     * @ORM\Column(type="string", length=100)
     */
    private $status;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $first6Digits;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $last4Digits;

    /**
     * PaymentCardRegistration constructor.
     * @param User $user
     * @param string $registrationId
     * @throws \Exception
     */
    public function __construct(User $user, string $registrationId)
    {
        $this->setCreatedAt(new \DateTime('now'));
        $this->setStatus(self::STATUS_NEW);

        $this->user = $user;
        $this->registrationId = $registrationId;
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
            'status'        => $this->getStatus(),
            'first6Digits'  => $this->first6Digits,
            'last4Digits'   => $this->last4Digits,
        ];
    }

    /**
     * @return array
     */
    public static function getAllowedStatuses() : array
    {
        return self::ALLOWED_STATUSES;
    }

    /**
     * Decide if specified status is allowed fot the entity
     *
     * @param int|null $status
     * @return bool
     */
    public static function isStatusAllowed(?int $status) : bool
    {
        if(in_array($status, self::ALLOWED_STATUSES)){
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
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
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
     * @return string
     */
    public function getRegistrationId(): string
    {
        return $this->registrationId;
    }

    /**
     * @param string $registrationId
     */
    public function setRegistrationId(string $registrationId): void
    {
        $this->registrationId = $registrationId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null
     */
    public function getFirst6Digits(): ?int
    {
        return $this->first6Digits;
    }

    /**
     * @param int|null $first6Digits
     * @return PaymentCardRegistration
     */
    public function setFirst6Digits(?int $first6Digits): PaymentCardRegistration
    {
        $this->first6Digits = $first6Digits;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLast4Digits(): ?int
    {
        return $this->last4Digits;
    }

    /**
     * @param int|null $last4Digits
     * @return PaymentCardRegistration
     */
    public function setLast4Digits(?int $last4Digits): PaymentCardRegistration
    {
        $this->last4Digits = $last4Digits;
        return $this;
    }
}
