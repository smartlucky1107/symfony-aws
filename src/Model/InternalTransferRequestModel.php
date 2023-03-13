<?php

namespace App\Model;

class InternalTransferRequestModel
{
    /** @var string */
    public $internalTransferRequestId;

    /** @var int */
    public $internalTransferId;

    /**
     * InternalTransferRequestModel constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['internalTransferRequestId'])) $this->setInternalTransferRequestId($data['internalTransferRequestId']);
        if(isset($data['internalTransferId'])) $this->setInternalTransferId($data['internalTransferId']);
    }

    /**
     * Verify the model
     *
     * @return bool
     */
    public function isValid(){
        if($this->internalTransferRequestId && $this->internalTransferId){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getInternalTransferRequestId(): string
    {
        return $this->internalTransferRequestId;
    }

    /**
     * @param string $internalTransferRequestId
     */
    public function setInternalTransferRequestId(string $internalTransferRequestId): void
    {
        $this->internalTransferRequestId = $internalTransferRequestId;
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
