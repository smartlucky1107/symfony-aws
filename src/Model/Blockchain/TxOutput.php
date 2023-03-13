<?php

namespace App\Model\Blockchain;

use App\Model\PriceInterface;

class TxOutput
{
    /** @var string */
    public $address;

    /** @var string */
    public $value;

    /**
     * TxOutput constructor.
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if(isset($data['address'])) $this->setAddress($data['address']);
        if(isset($data['value'])){
            if(is_numeric($data['value'])){
                $value =  bcadd($data['value'], '0', PriceInterface::BC_SCALE);
                $this->setValue($value);
            }
        }
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
        if($this->address && $this->value){
            return true;
        }

        return false;
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
}