<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GiifReportRepository")
 */
class GiifReport
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'            => 'id',
        'totalAmount'   => 'totalAmount',
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
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $deposits;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $withdrawals;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $totalAmount;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $reported = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\GiifReportTransaction", mappedBy="giifReport", orphanRemoval=true)
     */
    private $giifReportTransactions;

    /**
     * GiifReport constructor.
     * @param User $user
     * @param string $totalAmount
     * @throws \Exception
     */
    public function __construct(User $user, string $totalAmount)
    {
        $this->giifReportTransactions = new ArrayCollection();
        $this->user = $user;
        $this->totalAmount = $totalAmount;

        $this->setCreatedAt(new \DateTime('now'));
        $this->setDeposits([]);
        $this->setWithdrawals([]);
        $this->setReported(false);
    }

    /**
     * Serialize and return public data of the object
     *
     * @param bool $extended
     * @return array
     */
    public function serialize(bool $extended = false) : array
    {
        $transactions = [];
        if($this->giifReportTransactions){
            /** @var GiifReportTransaction $giifReportTransaction */
            foreach ($this->giifReportTransactions as $giifReportTransaction){
                $transactions[] = $giifReportTransaction->serialize();
            }
        }

        $serialized = [
            'id'            => $this->id,
            'createdAt'     => $this->createdAt->format('c'),
            'isReported'    => $this->isReported(),
            'totalAmount'   => $this->totalAmount,
            'user'          => $this->user->serialize(),
            'transactions'  => $transactions
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
     * @return array
     */
    public function getDeposits(): array
    {
        return $this->deposits;
    }

    /**
     * @param array $deposits
     */
    public function setDeposits(array $deposits): void
    {
        $this->deposits = $deposits;
    }

    /**
     * @return array
     */
    public function getWithdrawals(): array
    {
        return $this->withdrawals;
    }

    /**
     * @param array $withdrawals
     */
    public function setWithdrawals(array $withdrawals): void
    {
        $this->withdrawals = $withdrawals;
    }

    /**
     * @return string
     */
    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    /**
     * @param string $totalAmount
     */
    public function setTotalAmount(string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return bool
     */
    public function isReported(): bool
    {
        return $this->reported;
    }

    /**
     * @param bool $reported
     */
    public function setReported(bool $reported): void
    {
        $this->reported = $reported;
    }

    /**
     * @return Collection|GiifReportTransaction[]
     */
    public function getGiifReportTransactions(): Collection
    {
        return $this->giifReportTransactions;
    }

    /**
     * @param GiifReportTransaction $giifReportTransaction
     * @return GiifReport
     */
    public function addGiifReportTransaction(GiifReportTransaction $giifReportTransaction): self
    {
        if (!$this->giifReportTransactions->contains($giifReportTransaction)) {
            $this->giifReportTransactions[] = $giifReportTransaction;
            $giifReportTransaction->setGiifReport($this);
        }

        return $this;
    }

    /**
     * @param GiifReportTransaction $giifReportTransaction
     * @return GiifReport
     */
    public function removeGiifReportTransaction(GiifReportTransaction $giifReportTransaction): self
    {
        if ($this->giifReportTransactions->contains($giifReportTransaction)) {
            $this->giifReportTransactions->removeElement($giifReportTransaction);
            // set the owning side to null (unless already changed)
            if ($giifReportTransaction->getGiifReport() === $this) {
                $giifReportTransaction->setGiifReport(null);
            }
        }

        return $this;
    }
}
