<?php

namespace App\Document\Blockchain;

use App\Model\Blockchain\TxOutput;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class BitcoinTx implements TxInterface
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

    /**
     * @var bool
     *
     * @MongoDB\Field(type="bool")
     */
    protected $success;

####
# Custom item fields

    /**
     * @var string
     * @MongoDB\Field(type="string") @MongoDB\UniqueIndex(order="asc")
     */
    protected $txHash;

    /**
     * @var array
     * @MongoDB\Field(type="collection")
     */
    protected $txOutputs;

    /**
     * @var bool
     * @MongoDB\Field(type="bool")
     */
    protected $confirmed;

    /**
     * BitcoinTx constructor.
     * @param string $txHash
     * @param array $txOutputs
     * @throws \Exception
     */
    public function __construct(string $txHash, array $txOutputs)
    {
        $this->txHash = $txHash;
        $this->txOutputs = $txOutputs;

        $this->setConfirmed(false);

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
        $this->setProcessed(false);
        $this->setSuccess(false);

        // TODO
        // Run in Mongo database
        // createIndex({ "txHash": 1 }, { unique: true });
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
            'success'       => $this->success,
            'txHash'        => $this->txHash,
            'confirmed'     => $this->confirmed,
            'txOutputs'     => $this->txOutputs,
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
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return string
     */
    public function getTxHash(): string
    {
        return $this->txHash;
    }

    /**
     * @param string $txHash
     */
    public function setTxHash(string $txHash): void
    {
        $this->txHash = $txHash;
    }

    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     */
    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    /**
     * @return array
     */
    public function getTxOutputs(): array
    {
        return $this->txOutputs;
    }

    /**
     * @param array $txOutputs
     */
    public function setTxOutputs(array $txOutputs): void
    {
        $this->txOutputs = $txOutputs;
    }

    /**
     * @param TxOutput $txOutput
     */
    public function addTxOutput(TxOutput $txOutput): void
    {
        $this->txOutputs[] = $txOutput;
    }
}