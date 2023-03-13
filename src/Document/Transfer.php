<?php

namespace App\Document;

use App\Exception\AppException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
/**
 * @MongoDB\Document
 */
class Transfer
{
    const TYPE_DEPOSIT                  = 1;
    const TYPE_WITHDRAWAL               = 2;
    const TYPE_TRANSFER_TO              = 3;
    const TYPE_TRANSFER_FROM            = 4;
    const TYPE_BLOCK                    = 5;
    const TYPE_RELEASE                  = 6;

    const TYPE_FEE_TRANSFER_TO          = 7;
    const TYPE_FEE_TRANSFER_FROM        = 8;
    const TYPE_INTERNAL_TRANSFER_TO     = 9;
    const TYPE_INTERNAL_TRANSFER_FROM   = 10;

    const TYPES = [
        self::TYPE_DEPOSIT                  => 'Deposit',
        self::TYPE_WITHDRAWAL               => 'Withdrawal',
        self::TYPE_TRANSFER_TO              => 'Transfer to the wallet',
        self::TYPE_TRANSFER_FROM            => 'Transfer from the wallet',
        self::TYPE_BLOCK                    => 'Block the amount',
        self::TYPE_RELEASE                  => 'Release the amount',

        self::TYPE_FEE_TRANSFER_TO          => 'Transfer FEE to the wallet',
        self::TYPE_FEE_TRANSFER_FROM        => 'Transfer FEE from the wallet',
        self::TYPE_INTERNAL_TRANSFER_TO     => 'Internal transfer to the wallet',
        self::TYPE_INTERNAL_TRANSFER_FROM   => 'Internal transfer from the wallet',
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
    protected $tradeId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $orderId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $depositId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $withdrawalId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $internalTransferId;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $checkoutOrderId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $offerOrderId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $bidOrderId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $walletId;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $amount;

    /**
     * Transfer constructor.
     * @param int $type
     * @param int $walletId
     * @param string $amount
     * @throws AppException
     */
    public function __construct(int $type, int $walletId, string $amount)
    {
        if(!$this->isTypeAllowed($type)) throw new AppException('Transfer type not allowed');

        $this->type = $type;
        $this->walletId = $walletId;
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
            'tradeId'       => $this->tradeId,
            'orderId'       => $this->orderId,
            'depositId'     => $this->depositId,
            'withdrawalId'  => $this->withdrawalId,
            'internalTransferId'    => $this->internalTransferId,
            'checkoutOrderId'       => $this->checkoutOrderId,
            'offerOrderId'  => $this->offerOrderId,
            'bidOrderId'    => $this->bidOrderId,
            'walletId'      => $this->walletId,
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
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return mixed
     */
    public function getDepositId()
    {
        return $this->depositId;
    }

    /**
     * @param mixed $depositId
     */
    public function setDepositId($depositId): void
    {
        $this->depositId = $depositId;
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
    public function getInternalTransferId()
    {
        return $this->internalTransferId;
    }

    /**
     * @param mixed $internalTransferId
     */
    public function setInternalTransferId($internalTransferId): void
    {
        $this->internalTransferId = $internalTransferId;
    }

    /**
     * @return mixed
     */
    public function getCheckoutOrderId()
    {
        return $this->checkoutOrderId;
    }

    /**
     * @param mixed $checkoutOrderId
     */
    public function setCheckoutOrderId($checkoutOrderId): void
    {
        $this->checkoutOrderId = $checkoutOrderId;
    }

    /**
     * @return mixed
     */
    public function getOfferOrderId()
    {
        return $this->offerOrderId;
    }

    /**
     * @param mixed $offerOrderId
     */
    public function setOfferOrderId($offerOrderId): void
    {
        $this->offerOrderId = $offerOrderId;
    }

    /**
     * @return mixed
     */
    public function getBidOrderId()
    {
        return $this->bidOrderId;
    }

    /**
     * @param mixed $bidOrderId
     */
    public function setBidOrderId($bidOrderId): void
    {
        $this->bidOrderId = $bidOrderId;
    }

    /**
     * @return mixed
     */
    public function getWalletId()
    {
        return $this->walletId;
    }

    /**
     * @param mixed $walletId
     */
    public function setWalletId($walletId): void
    {
        $this->walletId = $walletId;
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
