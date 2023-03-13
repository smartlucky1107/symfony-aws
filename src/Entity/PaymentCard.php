<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentCardRepository")
 */
class PaymentCard
{
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
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

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
     * @ORM\Column(type="uuid", nullable=true, unique=true)
     */
    private $cardId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $first6Digits;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $last4Digits;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $expirationDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $userFirstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $userLastName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $binBank;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $binCard;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $binType;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * PaymentCard constructor.
     * @param User $user
     * @param string $cardId
     * @throws \Exception
     */
    public function __construct(User $user, string $cardId)
    {
        $this->setCreatedAt(new \DateTime('now'));

        $this->user = $user;
        $this->cardId = $cardId;
        $this->setEnabled(true);
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
            'user'          => $this->user->serializeBasic(),
            'first6Digits'  => $this->first6Digits,
            'last4Digits'   => $this->last4Digits,
            'binType'       => $this->binType,
            'binCard'       => $this->binCard,
            'enabled'       => $this->enabled,
        ];
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
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $updatedAt
     */
    public function setUpdatedAt(?\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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
    public function getCardId(): string
    {
        return $this->cardId;
    }

    /**
     * @param string $cardId
     */
    public function setCardId(string $cardId): void
    {
        $this->cardId = $cardId;
    }

    /**
     * @return int
     */
    public function getFirst6Digits(): int
    {
        return $this->first6Digits;
    }

    /**
     * @param int $first6Digits
     */
    public function setFirst6Digits(int $first6Digits): void
    {
        $this->first6Digits = $first6Digits;
    }

    /**
     * @return int
     */
    public function getLast4Digits(): int
    {
        return $this->last4Digits;
    }

    /**
     * @param int $last4Digits
     */
    public function setLast4Digits(int $last4Digits): void
    {
        $this->last4Digits = $last4Digits;
    }

    /**
     * @return string
     */
    public function getExpirationDate(): string
    {
        return $this->expirationDate;
    }

    /**
     * @param string $expirationDate
     */
    public function setExpirationDate(string $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return string
     */
    public function getUserFirstName(): string
    {
        return $this->userFirstName;
    }

    /**
     * @param string $userFirstName
     */
    public function setUserFirstName(string $userFirstName): void
    {
        $this->userFirstName = $userFirstName;
    }

    /**
     * @return string
     */
    public function getUserLastName(): string
    {
        return $this->userLastName;
    }

    /**
     * @param string $userLastName
     */
    public function setUserLastName(string $userLastName): void
    {
        $this->userLastName = $userLastName;
    }

    /**
     * @return string|null
     */
    public function getBinBank(): ?string
    {
        return $this->binBank;
    }

    /**
     * @param string|null $binBank
     * @return PaymentCard
     */
    public function setBinBank(?string $binBank): PaymentCard
    {
        $this->binBank = $binBank;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBinCard(): ?string
    {
        return $this->binCard;
    }

    /**
     * @param string|null $binCard
     * @return PaymentCard
     */
    public function setBinCard(?string $binCard): PaymentCard
    {
        $this->binCard = $binCard;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBinType(): ?string
    {
        return $this->binType;
    }

    /**
     * @param string|null $binType
     * @return PaymentCard
     */
    public function setBinType(?string $binType): PaymentCard
    {
        $this->binType = $binType;
        return $this;
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
     * @return PaymentCard
     */
    public function setEnabled(bool $enabled): PaymentCard
    {
        $this->enabled = $enabled;
        return $this;
    }
}
