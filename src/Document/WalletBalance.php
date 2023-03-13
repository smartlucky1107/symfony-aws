<?php

namespace App\Document;

use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WalletTransfer\WalletTransferItem;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class WalletBalance
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
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    protected $walletId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $tradeId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $orderId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $depositId;

    /**
     * @var int|null
     *
     * @MongoDB\Field(type="int")
     */
    protected $withdrawalId;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $balanceBefore;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $balanceAfter;

    /**
     * WalletBalance constructor.
     * @param int $walletId
     * @param string $balance
     * @throws \Exception
     */
    public function __construct(int $walletId, string $balance)
    {
        $this->walletId = $walletId;

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
        $this->setBalanceBefore($balance);
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
            'walletId'      => $this->walletId,
            'tradeId'       => $this->tradeId,
            'orderId'       => $this->orderId,
            'depositId'     => $this->depositId,
            'withdrawalId'  => $this->withdrawalId,
            'balanceBefore' => $this->balanceBefore,
            'balanceAfter'  => $this->balanceAfter,
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
     * @return int
     */
    public function getWalletId(): int
    {
        return $this->walletId;
    }

    /**
     * @param int $walletId
     */
    public function setWalletId(int $walletId): void
    {
        $this->walletId = $walletId;
    }

    /**
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    /**
     * @param int|null $orderId
     */
    public function setOrderId(?int $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int|null
     */
    public function getTradeId(): ?int
    {
        return $this->tradeId;
    }

    /**
     * @param int|null $tradeId
     */
    public function setTradeId(?int $tradeId): void
    {
        $this->tradeId = $tradeId;
    }

    /**
     * @return int|null
     */
    public function getDepositId(): ?int
    {
        return $this->depositId;
    }

    /**
     * @param int|null $depositId
     */
    public function setDepositId(?int $depositId): void
    {
        $this->depositId = $depositId;
    }

    /**
     * @return int|null
     */
    public function getWithdrawalId(): ?int
    {
        return $this->withdrawalId;
    }

    /**
     * @param int|null $withdrawalId
     */
    public function setWithdrawalId(?int $withdrawalId): void
    {
        $this->withdrawalId = $withdrawalId;
    }

    /**
     * @return mixed
     */
    public function getBalanceBefore()
    {
        return $this->balanceBefore;
    }

    /**
     * @param mixed $balanceBefore
     */
    public function setBalanceBefore($balanceBefore): void
    {
        $this->balanceBefore = $balanceBefore;
    }

    /**
     * @return mixed
     */
    public function getBalanceAfter()
    {
        return $this->balanceAfter;
    }

    /**
     * @param mixed $balanceAfter
     */
    public function setBalanceAfter($balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }
}
