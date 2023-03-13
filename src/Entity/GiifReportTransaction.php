<?php

namespace App\Entity;

use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Withdrawal;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GiifReportTransactionRepository")
 */
class GiifReportTransaction
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
     * @var GiifReport
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\GiifReport", inversedBy="giifReportTransactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $giifReport;

    /**
     * @var Deposit|null
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Wallet\Deposit")
     * @ORM\JoinColumn(nullable=true)
     */
    private $deposit;

    /**
     * @var Withdrawal|null
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Wallet\Withdrawal")
     * @ORM\JoinColumn(nullable=true)
     */
    private $withdrawal;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amountPLN;

    /**
     * GiifReportTransaction constructor.
     * @param GiifReport $giifReport
     * @param string $amountPLN
     * @throws \Exception
     */
    public function __construct(GiifReport $giifReport, string $amountPLN)
    {
        $this->giifReport = $giifReport;
        $this->amountPLN = $amountPLN;

        $this->setCreatedAt(new \DateTime('now'));
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
            'deposit'       => ($this->deposit instanceof Deposit ? $this->deposit->serialize(): null),
            'withdrawal'    => ($this->withdrawal instanceof Withdrawal ? $this->withdrawal->serialize(): null),
            'amountPLN'     => $this->amountPLN
        ];

        if($extended){
        }

        return $serialized;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return GiifReport|null
     */
    public function getGiifReport(): ?GiifReport
    {
        return $this->giifReport;
    }

    /**
     * @param GiifReport|null $giifReport
     * @return GiifReportTransaction
     */
    public function setGiifReport(?GiifReport $giifReport): self
    {
        $this->giifReport = $giifReport;

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
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Deposit|null
     */
    public function getDeposit(): ?Deposit
    {
        return $this->deposit;
    }

    /**
     * @param Deposit|null $deposit
     */
    public function setDeposit(?Deposit $deposit): void
    {
        $this->deposit = $deposit;
    }

    /**
     * @return Withdrawal|null
     */
    public function getWithdrawal(): ?Withdrawal
    {
        return $this->withdrawal;
    }

    /**
     * @param Withdrawal|null $withdrawal
     */
    public function setWithdrawal(?Withdrawal $withdrawal): void
    {
        $this->withdrawal = $withdrawal;
    }

    /**
     * @return string
     */
    public function getAmountPLN(): string
    {
        return $this->amountPLN;
    }

    /**
     * @param string $amountPLN
     */
    public function setAmountPLN(string $amountPLN): void
    {
        $this->amountPLN = $amountPLN;
    }
}
