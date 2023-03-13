<?php

namespace App\Entity\Wallet;

use App\Entity\Currency;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Wallet\AffiliateRewardRepository")
 */
class AffiliateReward
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
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="affiliate_user_id", referencedColumnName="id", nullable=false)
     */
    private $affiliateUser;

    /**
     * @var Trade
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\OrderBook\Trade")
     * @ORM\JoinColumn(name="trade_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $trade;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amount;

    /**
     * @var Currency
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     */
    private $currency;

    /**
     * AffiliateReward constructor.
     * @param User $user
     * @param User $affiliateUser
     * @param Trade $trade
     * @param $amount
     * @param Currency $currency
     * @throws \Exception
     */
    public function __construct(User $user, User $affiliateUser, Trade $trade, $amount, Currency $currency)
    {
        $this->user = $user;
        $this->affiliateUser = $affiliateUser;
        $this->trade = $trade;
        $this->amount = $amount;
        $this->currency = $currency;

        $this->setCreatedAt(new \DateTime('now'));
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
     * @return User
     */
    public function getAffiliateUser(): User
    {
        return $this->affiliateUser;
    }

    /**
     * @param User $affiliateUser
     */
    public function setAffiliateUser(User $affiliateUser): void
    {
        $this->affiliateUser = $affiliateUser;
    }

    /**
     * @return Trade
     */
    public function getTrade(): Trade
    {
        return $this->trade;
    }

    /**
     * @param Trade $trade
     */
    public function setTrade(Trade $trade): void
    {
        $this->trade = $trade;
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
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }
}
