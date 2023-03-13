<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyRepository")
 */
class Currency
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'fullName'  => 'fullName',
        'shortName' => 'shortName',
        'type'      => 'type',
        'enabled'   => 'enabled',
        'fee'       => 'fee',
    ];

    const TYPE_FIAT     = 'fiat';
    const TYPE_BTC      = 'btc';
    const TYPE_BCH      = 'bch';
    const TYPE_BSV      = 'bsv';
    const TYPE_ETH      = 'eth';
    const TYPE_ERC20    = 'erc20';
    const TYPE_BEP20    = 'bep20';
    const TYPE_POLKADOT = 'polkadot';
    const TYPE_LITECOIN = 'litecoin';
    const TYPE_LISK     = 'lisk';
    const TYPE_DASH     = 'dash';
    const TYPE_TRON     = 'tron';

    const FEE_TYPE_FIXED = 1;
    const FEE_TYPE_PERCENTAGE = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      max = 128,
     *      maxMessage = "Currency full name cannot be longer than {{ limit }} characters"
     * )
     * @SWG\Property(description="Full name of the currency", example="Bitcoin")
     * @Serializer\Groups({"output"})
     */
    private $fullName;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      max = 10,
     *      maxMessage = "Currency short name cannot be longer than {{ limit }} characters"
     * )
     * @SWG\Property(description="Short name of the currency", example="BTC")
     * @Serializer\Groups({"output"})
     */
    private $shortName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedTypes")
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Assert\Length(
     *      max = 128,
     *      maxMessage = "Currency full name cannot be longer than {{ limit }} characters"
     * )
     */
    private $smartContractAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    private $enabled = true;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $roundPrecision;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $feeType;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $fee;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $minFee;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $minWithdrawal;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $sortIndex;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_deposit_allowed", type="boolean")
     */
    private $depositAllowed = true;

    /**
     * Currency constructor.
     * @param $fullName
     * @param $shortName
     * @param $type
     */
    public function __construct($fullName, $shortName, $type)
    {
        $this->fullName = $fullName;
        $this->shortName = $shortName;
        $this->type = $type;

        $this->setEnabled(true);
        $this->setRoundPrecision(2);
        $this->setFee(0);
        $this->setFeeType(self::FEE_TYPE_FIXED);
        $this->setMinFee(0);
        $this->setMinWithdrawal(0);
        $this->setSortIndex(0);
    }

    /**
     * @return bool
     */
    public function isCryptoType() : bool
    {
        if(
            $this->type === self::TYPE_BTC ||
            $this->type === self::TYPE_BCH ||
            $this->type === self::TYPE_BSV ||
            $this->type === self::TYPE_ETH ||
            $this->type === self::TYPE_ERC20 ||
            $this->type === self::TYPE_BEP20 ||
            $this->type === self::TYPE_POLKADOT ||
            $this->type === self::TYPE_LITECOIN ||
            $this->type === self::TYPE_LISK ||
            $this->type === self::TYPE_DASH ||
            $this->type === self::TYPE_TRON
        ){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFiatType() : bool
    {
        if($this->type === self::TYPE_FIAT){
            return true;
        }

        return false;
    }

    /**
     * Get allowed types as simple array.
     *
     * @return array
     */
    public static function getAllowedTypes(){
        return [
            self::TYPE_FIAT,
            self::TYPE_BTC,
            self::TYPE_BCH,
            self::TYPE_BSV,
            self::TYPE_ETH,
            self::TYPE_ERC20,
            self::TYPE_BEP20,
        ];
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'id'        => $this->id,
            'fullName'  => $this->fullName,
            'shortName' => $this->shortName,
//            'enabled'   => $this->enabled,  // TODO remove that at all because we have enabled/visible in currencyPair
            'type'      => $this->type,
            'roundPrecision'    => $this->roundPrecision,
            'isDepositAllowed'  => $this->isDepositAllowed(),
            'fee' => $this->fee,
        ];
    }

    /**
     * @return array
     */
    public function serializeForPrivateApi() : array
    {
        return [
            'id'        => $this->id,
            'fullName'  => $this->fullName,
            'shortName' => $this->shortName,
            'roundPrecision'    => $this->roundPrecision,
            'isDepositAllowed'  => $this->isDepositAllowed()
        ];
    }

    /**
     * Serialize and return public basic data of the object
     *
     * @return array
     */
    public function serializeBasic() : array
    {
        return [
            'fullName'  => $this->fullName,
            'shortName' => $this->shortName,
            'fee'       => $this->toPrecision($this->fee),
            'minWithdrawal' => $this->toPrecision($this->minWithdrawal),
            'isDepositAllowed'  => $this->isDepositAllowed()
        ];
    }

    /**
     * @return array
     */
    public function serializeForPOSApi() : array
    {
        return [
            'fullName'  => $this->fullName,
            'shortName' => $this->shortName,
            'roundPrecision'    => $this->roundPrecision
        ];
    }

    /**
     * @return array
     */
    public function serializeForPreOrder() : array
    {
        return $this->serializeForPOSApi();
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getRoundPrecision());
    }

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
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param mixed $fullName
     */
    public function setFullName($fullName): void
    {
        $this->fullName = $fullName;
    }

    /**
     * @return mixed
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @param mixed $shortName
     */
    public function setShortName($shortName): void
    {
        $this->shortName = $shortName;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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
     * @return mixed
     */
    public function getSmartContractAddress()
    {
        return $this->smartContractAddress;
    }

    /**
     * @param mixed $smartContractAddress
     */
    public function setSmartContractAddress($smartContractAddress): void
    {
        $this->smartContractAddress = $smartContractAddress;
    }

    /**
     * @return int
     */
    public function getRoundPrecision(): int
    {
        return $this->roundPrecision;
    }

    /**
     * @param int $roundPrecision
     */
    public function setRoundPrecision(int $roundPrecision): void
    {
        $this->roundPrecision = $roundPrecision;
    }

    /**
     * @return int
     */
    public function getFeeType(): int
    {
        return $this->feeType;
    }

    /**
     * @param int $feeType
     */
    public function setFeeType(int $feeType): void
    {
        $this->feeType = $feeType;
    }

    /**
     * @return string
     */
    public function getFee(): string
    {
        return $this->fee;
    }

    /**
     * @param string $fee
     */
    public function setFee(string $fee): void
    {
        $this->fee = $fee;
    }

    /**
     * @return string
     */
    public function getMinFee(): string
    {
        return $this->minFee;
    }

    /**
     * @param string $minFee
     */
    public function setMinFee(string $minFee): void
    {
        $this->minFee = $minFee;
    }

    /**
     * @return string
     */
    public function getMinWithdrawal(): string
    {
        return $this->minWithdrawal;
    }

    /**
     * @param string $minWithdrawal
     */
    public function setMinWithdrawal(string $minWithdrawal): void
    {
        $this->minWithdrawal = $minWithdrawal;
    }

    /**
     * @return int
     */
    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    /**
     * @param int $sortIndex
     */
    public function setSortIndex(int $sortIndex): void
    {
        $this->sortIndex = $sortIndex;
    }

    /**
     * @return bool
     */
    public function isDepositAllowed(): bool
    {
        return $this->depositAllowed;
    }

    /**
     * @param bool $depositAllowed
     */
    public function setDepositAllowed(bool $depositAllowed): void
    {
        $this->depositAllowed = $depositAllowed;
    }
}
