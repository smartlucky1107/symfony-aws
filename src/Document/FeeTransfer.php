<?php

namespace App\Document;

use App\Exception\AppException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
/**
 * @MongoDB\Document
 */
class FeeTransfer
{
    const TYPE_TRADE_FEE        = 1;
    const TYPE_WITHDRAWAL_FEE   = 2;

    const TYPES = [
        self::TYPE_TRADE_FEE        => 'Trade fee',
        self::TYPE_WITHDRAWAL_FEE   => 'withdrawal fee'
    ];

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $createdAtTime;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $feeWalletId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $tradeId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $withdrawalId;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $amount;

    /**
     * FeeTransfer constructor.
     * @param int $type
     * @param int $feeWalletId
     * @param string $amount
     * @throws AppException
     */
    public function __construct(int $type, int $feeWalletId, string $amount)
    {
        if(!$this->isTypeAllowed($type)) throw new AppException('Transfer type not allowed');

        $this->type = $type;
        $this->feeWalletId = $feeWalletId;
        $this->amount = $amount;

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
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
            'type'          => $this->type,
            'typeName'      => $this->getTypeName(),
            'feeWalletId'   => $this->feeWalletId,
            'tradeId'       => $this->tradeId,
            'withdrawalId'  => $this->withdrawalId,
            'amount'        => $this->amount
        ];
    }

    /**
     * Get type name of the object
     *
     * @return string
     */
    public function getTypeName() : string
    {
        if(isset(self::TYPES[$this->type])){
            return self::TYPES[$this->type];
        }

        return '';
    }

    /**
     * Check if passed $type is allowed for Transfer
     *
     * @param int $type
     * @return bool
     */
    public function isTypeAllowed(int $type) : bool
    {
        if(isset(self::TYPES[$type])){
            return true;
        }else{
            return false;
        }
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getFeeWalletId()
    {
        return $this->feeWalletId;
    }

    /**
     * @param mixed $feeWalletId
     */
    public function setFeeWalletId($feeWalletId): void
    {
        $this->feeWalletId = $feeWalletId;
    }

    /**
     * @return mixed
     */
    public function getTradeId()
    {
        return $this->tradeId;
    }

    /**
     * @param mixed $tradeId
     */
    public function setTradeId($tradeId): void
    {
        $this->tradeId = $tradeId;
    }

    /**
     * @return mixed
     */
    public function getWithdrawalId()
    {
        return $this->withdrawalId;
    }

    /**
     * @param mixed $withdrawalId
     */
    public function setWithdrawalId($withdrawalId): void
    {
        $this->withdrawalId = $withdrawalId;
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
}
