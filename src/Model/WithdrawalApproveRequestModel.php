<?php

namespace App\Model;

class WithdrawalApproveRequestModel
{
    /** @var string */
    public $withdrawalApproveRequestId;

    /** @var int */
    public $withdrawalId;

    /**
     * WithdrawalApproveRequestModel constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['withdrawalApproveRequestId'])) $this->setWithdrawalApproveRequestId($data['withdrawalApproveRequestId']);
        if(isset($data['withdrawalId'])) $this->setWithdrawalId($data['withdrawalId']);
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->withdrawalApproveRequestId && $this->withdrawalId){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getWithdrawalApproveRequestId(): string
    {
        return $this->withdrawalApproveRequestId;
    }

    /**
     * @param string $withdrawalApproveRequestId
     */
    public function setWithdrawalApproveRequestId(string $withdrawalApproveRequestId): void
    {
        $this->withdrawalApproveRequestId = $withdrawalApproveRequestId;
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
