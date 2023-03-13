<?php

namespace App\Entity\Configuration;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Configuration\SystemTagRepository")
 */
class SystemTag
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'    => 'id',
        'type'  => 'type',
        'activated'  => 'activated',
    ];

    const TYPE_TRADING_DISABLED     = 'TRADING_DISABLED';
    const TYPE_DEPOSIT_DISABLED     = 'DEPOSIT_DISABLED';
    const TYPE_WITHDRAWAL_DISABLED  = 'WITHDRAWAL_DISABLED';
    const TYPE_REGISTER_DISABLED    = 'REGISTER_DISABLED';
    const TYPE_MARKET_DISABLED      = 'MARKET_DISABLED';
    const TYPE_LOGIN_DISABLED       = 'LOGIN_DISABLED';
    const TYPE_PASSWORD_RESETTING_DISABLED  = 'PASSWORD_RESETTING_DISABLED';
    const TYPE_POS_DISABLED         = 'POS_DISABLED';
    const TYPES = [
        self::TYPE_TRADING_DISABLED     => 'Trading disabled',
        self::TYPE_DEPOSIT_DISABLED     => 'Deposit disabled',
        self::TYPE_WITHDRAWAL_DISABLED  => 'Withdrawal disabled',
        self::TYPE_REGISTER_DISABLED    => 'Register disabled',
        self::TYPE_MARKET_DISABLED      => 'Market disabled',
        self::TYPE_LOGIN_DISABLED       => 'Login disabled',
        self::TYPE_PASSWORD_RESETTING_DISABLED  => 'Password resetting disabled',
        self::TYPE_POS_DISABLED         => 'POS disabled',
    ];
    const ALLOWED_TYPES = [
        self::TYPE_TRADING_DISABLED,
        self::TYPE_DEPOSIT_DISABLED,
        self::TYPE_WITHDRAWAL_DISABLED,
        self::TYPE_REGISTER_DISABLED,
        self::TYPE_MARKET_DISABLED,
        self::TYPE_LOGIN_DISABLED,
        self::TYPE_PASSWORD_RESETTING_DISABLED,
        self::TYPE_POS_DISABLED,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedTypes")
     */
    private $type;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $activated = false;

    /**
     * SystemTag constructor.
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;

        $this->setActivated(false);
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
            'type'      => $this->type,
            'typeName'  => $this->getTypeName(),
            'activated' => $this->activated
        ];
    }

    /**
     * @return string
     */
    public function getTypeName() : string
    {
        if (array_key_exists($this->type, self::TYPES)) {
            return self::TYPES[$this->type];
        }

        return '';
    }

    /**
     * Get allowed types as simple array.
     *
     * @return array
     */
    public static function getAllowedTypes(){
        return self::ALLOWED_TYPES;
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @param bool $activated
     */
    public function setActivated(bool $activated): void
    {
        $this->activated = $activated;
    }
}
