<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Wallet\Wallet;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AddressRepository")
 */
class Address
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
     */
    private $createdAt;

    /**
     * @var Wallet
     * @ORM\ManyToOne(targetEntity="App\Entity\Wallet\Wallet", inversedBy="addresses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $wallet;

    /**
     * @var string
     * @ORM\Column(type="string", length=512)
     */
    private $address;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled = false;

    /**
     * Address constructor.
     * @param Wallet $wallet
     * @param string $address
     * @throws \Exception
     */
    public function __construct(Wallet $wallet, string $address)
    {
        $this->wallet = $wallet;
        $this->address = $address;

        $this->setCreatedAt(new \DateTime('now'));
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
            'address'       => $this->address
        ];
    }

    /**
     * Returns the address in hex format 0x0000000000....
     *
     * @return string
     */
    public function toHex(){
        return '0x'.$this->address;
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
