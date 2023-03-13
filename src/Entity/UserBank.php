<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserBankRepository")
 */
class UserBank
{
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
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=512)
     */
    private $iban;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=512)
     */
    private $swift;

    /**
     * UserBank constructor.
     * @param User $user
     * @param string $iban
     * @param string $swift
     * @throws \Exception
     */
    public function __construct(User $user, string $iban, string $swift)
    {
        $this->setCreatedAt(new \DateTime('now'));

        $this->user = $user;
        $this->iban = $iban;
        $this->swift = $swift;
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        $serialized = [
            'id'         => $this->id,
            'user'       => $this->user->serializeBasic(),
            'iban'       => $this->iban,
            'swift'      => $this->swift,
        ];

        return $serialized;
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
    public function getIban(): string
    {
        return $this->iban;
    }

    /**
     * @param string $iban
     */
    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    /**
     * @return string
     */
    public function getSwift(): string
    {
        return $this->swift;
    }

    /**
     * @param string $swift
     */
    public function setSwift(string $swift): void
    {
        $this->swift = $swift;
    }
}
