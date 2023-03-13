<?php

namespace App\Model\Analysis;

use App\Model\PriceInterface;

class WalletAnalysis
{
    const STATUS_NEW = 1;
    const STATUS_VALID = 1;
    const STATUS_INVALID = 2;

    /** @var string */
    public $inputBalance;

    /** @var string */
    public $inputBalanceBlocked;

    /** @var string */
    public $outputBalance;

    /** @var string */
    public $outputBalanceBlocked;

    /** @var int */
    public $status;

    /**
     * WalletAnalysis constructor.
     * @param string $inputBalance
     * @param string $inputBalanceBlocked
     */
    public function __construct(string $inputBalance, string $inputBalanceBlocked)
    {
        $this->inputBalance = $inputBalance;
        $this->inputBalanceBlocked = $inputBalanceBlocked;

        $this->setStatus(self::STATUS_NEW);
    }

    /**
     * @return WalletAnalysis
     */
    public function analyze() : WalletAnalysis
    {
        $balanceComp = bccomp($this->inputBalance, $this->outputBalance, PriceInterface::BC_SCALE);
        $balanceBlockedComp = bccomp($this->inputBalanceBlocked, $this->outputBalanceBlocked, PriceInterface::BC_SCALE);

        if($balanceComp === 0 && $balanceBlockedComp === 0){
            $this->setStatus(self::STATUS_VALID);
        }else{
            $this->setStatus(self::STATUS_INVALID);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        if($this->status === self::STATUS_VALID) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getInputBalance(): string
    {
        return $this->inputBalance;
    }

    /**
     * @return string
     */
    public function getInputBalanceBlocked(): string
    {
        return $this->inputBalanceBlocked;
    }

    /**
     * @return string
     */
    public function getOutputBalance(): string
    {
        return $this->outputBalance;
    }

    /**
     * @param string $outputBalance
     */
    public function setOutputBalance(string $outputBalance): void
    {
        $this->outputBalance = $outputBalance;
    }

    /**
     * @return string
     */
    public function getOutputBalanceBlocked(): string
    {
        return $this->outputBalanceBlocked;
    }

    /**
     * @param string $outputBalanceBlocked
     */
    public function setOutputBalanceBlocked(string $outputBalanceBlocked): void
    {
        $this->outputBalanceBlocked = $outputBalanceBlocked;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}