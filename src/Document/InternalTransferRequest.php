<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class InternalTransferRequest
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $createdAtTime;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="bool")
     */
    protected $processed;

####
# Custom item fields

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    protected $internalTransferId;

    /**
     * InternalTransferRequest constructor.
     * @param int $internalTransferId
     * @throws \Exception
     */
    public function __construct(int $internalTransferId)
    {
        $this->internalTransferId = $internalTransferId;

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
        $this->setProcessed(false);
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
            'createdAt'     => date('Y-m-d H:i:s', $this->createdAtTime),
            'processed'     => $this->processed,
            'internalTransferId'  => $this->internalTransferId,
        ];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCreatedAtTime()
    {
        return $this->createdAtTime;
    }

    /**
     * @param mixed $createdAtTime
     */
    public function setCreatedAtTime($createdAtTime): void
    {
        $this->createdAtTime = $createdAtTime;
    }

    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * @param bool $processed
     */
    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    /**
     * @return int
     */
    public function getInternalTransferId(): int
    {
        return $this->internalTransferId;
    }

    /**
     * @param int $internalTransferId
     */
    public function setInternalTransferId(int $internalTransferId): void
    {
        $this->internalTransferId = $internalTransferId;
    }
}
