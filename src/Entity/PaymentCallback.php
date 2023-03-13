<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentCallbackRepository")
 */
class PaymentCallback
{
    const TYPE_PRZELEWY_24          = 1;
    const TYPE_PAYWALL_CARD         = 2;
    const TYPE_PAYWALL_TRANSACTION  = 3;
    const ALLOWED_TYPES = [
        self::TYPE_PRZELEWY_24,
        self::TYPE_PAYWALL_CARD,
        self::TYPE_PAYWALL_TRANSACTION,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedTypes")
     *
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $signature;

    /**
     * @ORM\Column(type="json")
     */
    private $response;

    /**
     * PaymentCallback constructor.
     * @param int $type
     * @throws \Exception
     */
    public function __construct(int $type)
    {
        $this->setCreatedAt(new \DateTime('now'));

        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public static function getAllowedTypes() : array
    {
        return self::ALLOWED_TYPES;
    }

    /**
     * Verify if specified type is a valid type for the entity
     *
     * @param int|null $type
     * @return bool
     */
    public static function isTypeAllowed(?int $type) : bool
    {
        if(in_array($type, self::ALLOWED_TYPES)){
            return true;
        }

        return false;
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
     * @return PaymentCallback
     */
    public function setType(int $type): PaymentCallback
    {
        $this->type = $type;
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
     * @return PaymentCallback
     */
    public function setCreatedAt(\DateTime $createdAt): PaymentCallback
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * @param string|null $signature
     * @return PaymentCallback
     */
    public function setSignature(?string $signature): PaymentCallback
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     * @return PaymentCallback
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }
}
