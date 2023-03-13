<?php

namespace App\Entity\Liquidity;

use App\Entity\Currency;
use App\Exception\AppException;
use App\Model\PriceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Liquidity\ExternalMarketWalletRepository")
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"external_market"})})
 */
class ExternalMarketWallet implements ExternalMarketInterface
{
    const EXTERNAL_MARKETS = [
        self::EXTERNAL_MARKET_BITBAY  => 'Bitbay',
        self::EXTERNAL_MARKET_BINANCE  => 'Binance',
        self::EXTERNAL_MARKET_KRAKEN => 'Kraken',
        self::EXTERNAL_MARKET_WALUTOMAT  => 'Walutomat'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Currency
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     */
    private $currency;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $externalMarket;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $balance;

    /**
     * ExternalMarketWallet constructor.
     * @param Currency $currency
     * @param $externalMarket
     * @param $balance
     */
    public function __construct(Currency $currency, $externalMarket, $balance)
    {
        $this->currency = $currency;
        $this->externalMarket = $externalMarket;
        $this->balance = $balance;
    }

    /**
     * @param int $externalMarketId
     * @throws AppException
     */
    static public function verifyExternalMarketId(int $externalMarketId) : void
    {
        if($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_BITBAY){

        }elseif($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_BINANCE){

        }elseif($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_KRAKEN){

        }elseif($externalMarketId === ExternalMarketInterface::EXTERNAL_MARKET_WALUTOMAT){

        }else{
            throw new AppException('External market not allowed');
        }
    }

    /**
     * Verify if transfer is allowed from the wallet
     *
     * @param $amount
     * @return bool
     */
    public function isTransferAllowed($amount){
        $comp = bccomp($this->balance, $amount, PriceInterface::BC_SCALE);
        if($comp === 0 || $comp === 1){
            return true;
        }

//        if($this->freeAmount() >= $amount){
//            return true;
//        }
        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getExternalMarket()
    {
        return $this->externalMarket;
    }

    /**
     * @param mixed $externalMarket
     */
    public function setExternalMarket($externalMarket): void
    {
        $this->externalMarket = $externalMarket;
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance): void
    {
        $this->balance = $balance;
    }
}
