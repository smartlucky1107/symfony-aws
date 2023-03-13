<?php

namespace App\Entity\Wallet;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Wallet\WalletBankRepository")
 */
class WalletBank
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
     * @var Wallet
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet")
     * @ORM\JoinColumn(name="wallet_id", referencedColumnName="id")
     */
    private $wallet;

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
     * WalletBank constructor.
     * @param Wallet $wallet
     * @param string $iban
     * @param string $swift
     * @throws \Exception
     */
    public function __construct(Wallet $wallet, string $iban, string $swift)
    {
        $this->setCreatedAt(new \DateTime('now'));

        $this->wallet = $wallet;
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
            'wallet'     => $this->wallet->serializeBasic(),
            'iban'       => $this->iban,
            'swift'      => $this->swift,
        ];

        return $serialized;
    }

    /**
     * @param Wallet $wallet
     * @return bool
     */
    public function isWalletAllowed(Wallet $wallet) : bool
    {
        if($this->wallet->getId() === $wallet->getId()){
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
