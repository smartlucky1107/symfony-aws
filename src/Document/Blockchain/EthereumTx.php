<?php

namespace App\Document\Blockchain;

use App\Exception\AppException;
use App\Model\PriceInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class EthereumTx implements TxInterface
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
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $address;

    /**
     * @var string|null
     * @MongoDB\Field(type="string")
     */
    protected $smartContractAddress;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $value;

    /**
     * @var bool
     * @MongoDB\Field(type="bool")
     */
    protected $confirmed;

    /**
     * EthereumTx constructor.
     * @param string $txHash
     * @param string $address
     * @param string $value
     * @param string|null $smartContractAddress
     * @throws AppException
     */
    public function __construct(string $txHash, string $address, string $value, ?string $smartContractAddress = null)
    {
        $this->txHash = $txHash;
        $this->address = $address;

        if(is_numeric($value)){
            $this->value = bcadd($value, '0', PriceInterface::BC_SCALE);
        }else{
            throw new AppException('Value is invalid');
        }

        $this->smartContractAddress = $smartContractAddress;

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
            'address'       => $this->address,
            'smartContractAddress'  => $this->smartContractAddress,
            'value'         => $this->value,
            'confirmed'     => $this->confirmed,
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
     * @return string|null
     */
    public function getSmartContractAddress(): ?string
    {
        return $this->smartContractAddress;
    }

    /**
     * @param string|null $smartContractAddress
     */
    public function setSmartContractAddress(?string $smartContractAddress): void
    {
        $this->smartContractAddress = $smartContractAddress;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
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
}
