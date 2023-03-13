<?php

namespace App\Model;

class WithdrawalRequestModel
{
    /** @var string */
    public $withdrawalRequestId;

    /** @var int */
    public $withdrawalId;

    /**
     * WithdrawalRequestModel constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['withdrawalRequestId'])) $this->setWithdrawalRequestId($data['withdrawalRequestId']);
        if(isset($data['withdrawalId'])) $this->setWithdrawalId($data['withdrawalId']);
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->withdrawalRequestId && $this->withdrawalId){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getWithdrawalRequestId(): string
    {
        return $this->withdrawalRequestId;
    }

    /**
     * @param string $withdrawalRequestId
     */
    public function setWithdrawalRequestId(string $withdrawalRequestId): void
    {
        $this->withdrawalRequestId = $withdrawalRequestId;
    }

    /**
     * @return int
     */
    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }

    /**
     * @param int $withdrawalId
     */
    public function setWithdrawalId(int $withdrawalId): void
    {
        $this->withdrawalId = $withdrawalId;
    }
}