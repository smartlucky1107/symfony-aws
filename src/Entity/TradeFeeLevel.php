<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TradeFeeLevelRepository")
 */
class TradeFeeLevel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $tradingVolume;

    /**
     * @ORM\Column(type="float")
     */
    private $takerFee;

    /**
     * @ORM\Column(type="float")
     */
    private $makerFee;

    /**
     * @ORM\Column(type="float")
     */
    private $takerFeeCrypto;

    /**
     * @ORM\Column(type="float")
     */
    private $makerFeeCrypto;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTradingVolume()
    {
        return $this->tradingVolume;
    }

    /**
     * @param mixed $tradingVolume
     */
    public function setTradingVolume($tradingVolume): void
    {
        $this->tradingVolume = $tradingVolume;
    }

    /**
     * @return mixed
     */
    public function getTakerFee()
    {
        return $this->takerFee;
    }

    /**
     * @param mixed $takerFee
     */
    public function setTakerFee($takerFee): void
    {
        $this->takerFee = $takerFee;
    }

    /**
     * @return mixed
     */
    public function getMakerFee()
    {
        return $this->makerFee;
    }

    /**
     * @param mixed $makerFee
     */
    public function setMakerFee($makerFee): void
    {
        $this->makerFee = $makerFee;
    }

    /**
     * @return mixed
     */
    public function getTakerFeeCrypto()
    {
        return $this->takerFeeCrypto;
    }

    /**
     * @param mixed $takerFeeCrypto
     */
    public function setTakerFeeCrypto($takerFeeCrypto): void
    {
        $this->takerFeeCrypto = $takerFeeCrypto;
    }

    /**
     * @return mixed
     */
    public function getMakerFeeCrypto()
    {
        return $this->makerFeeCrypto;
    }

    /**
     * @param mixed $makerFeeCrypto
     */
    public function setMakerFeeCrypto($makerFeeCrypto): void
    {
        $this->makerFeeCrypto = $makerFeeCrypto;
    }
}
