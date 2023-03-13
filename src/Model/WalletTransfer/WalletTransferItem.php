<?php

namespace App\Model\WalletTransfer;

class WalletTransferItem implements WalletTransferInterface
{
    /** @var string */
    public $type;

    /** @var int */
    public $walletId;

    /** @var string */
    public $amount;

    /**
     * WalletTransferItem constructor.
     * @param string $type
     * @param int $walletId
     * @param string $amount
     */
    public function __construct(string $type, int $walletId, string $amount)
    {
        $this->type = $type;
        $this->walletId = $walletId;
        $this->amount = $amount;
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->type && $this->walletId && $this->amount){
            return true;
        }

        return false;
    }

    /**
     * @param $type
     * @return bool
     */
    public function isTypeAllowed($type){
        foreach(self::TYPES as $typeItem){
            if($typeItem === $type){
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
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
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }
}