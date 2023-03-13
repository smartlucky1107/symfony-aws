<?php

namespace App\Entity\POS;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass="App\Repository\POS\POSReceiptRepository")
 */
class POSReceipt
{
    const STATUS_NEW        = 1;
    const STATUS_PRINTING   = 2;
    const STATUS_PRINTED    = 3;
    const ALLOWED_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PRINTING,
        self::STATUS_PRINTED
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
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $printedAt;

    /**
     * @var POSOrder
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\POS\POSOrder", inversedBy="receipts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $POSOrder;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * POSReceipt constructor.
     * @param $POSOrder
     * @throws \Exception
     */
    public function __construct($POSOrder)
    {
        $this->POSOrder = $POSOrder;

        $this->setStatus(self::STATUS_NEW);
        $this->setCreatedAt(new \DateTime('now'));
        $this->setPrintedAt(null);
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
    public function getPrintedAt(): ?\DateTime
    {
        return $this->printedAt;
    }

    /**
     * @param \DateTime|null $printedAt
     */
    public function setPrintedAt(?\DateTime $printedAt): void
    {
        $this->printedAt = $printedAt;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return POSOrder
     */
    public function getPOSOrder(): POSOrder
    {
        return $this->POSOrder;
    }

    /**
     * @param POSOrder $POSOrder
     */
    public function setPOSOrder(POSOrder $POSOrder): void
    {
        $this->POSOrder = $POSOrder;
    }
}
