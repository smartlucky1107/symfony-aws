<?php

namespace App\Entity\POS;

use App\Entity\Currency;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\POS\WorkspaceRepository")
 * @UniqueEntity(fields="name", message="This workspace name is already in use")
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"name"})})
 */
class Workspace
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'            => 'id',
        'name'          => 'name',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="workspace", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Workspace name should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Workspace name is not a valid.",
     *     pattern="/^[a-zA-Z0-9]+$/i"
     * )
     * @Assert\Length(
     *      min = 7,
     *      max = 256,
     *      minMessage = "Workspace name must be at least {{ limit }} characters",
     *      maxMessage = "Workspace name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=256)
     */
    private $name;

    /**
     * @var int
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid type."
     * )
     * @Assert\GreaterThan(
     *     value = 100000
     * )
     * @ORM\Column(type="integer")
     */
    private $pin;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $verified = false;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(name="default_quoted_currency_id", referencedColumnName="id")
     */
    private $defaultQuotedCurrency;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $gAuthEnabled = false;

    /**
     * @var string|null

     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $gAuthSecret;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Company name should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Company name is not a valid.",
     *     pattern="/^[a-zA-Z0-9., ]+$/i"
     * )
     * @Assert\Length(
     *      min = 3,
     *      max = 256,
     *      minMessage = "Company name must be at least {{ limit }} characters",
     *      maxMessage = "Company name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=256)
     *
     * @SWG\Property(description="Company name")
     * @Serializer\Groups({"output_redeem"})
     */
    private $companyName;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "NIP should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "NIP is not a valid.",
     *     pattern="/^[0-9]+$/i"
     * )
     * @Assert\Length(
     *      min = 10,
     *      max = 10,
     *      exactMessage = "NIP must be exactly {{ limit }} characters",
     *      minMessage = "NIP must be at least {{ limit }} characters",
     *      maxMessage = "NIP cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=10)
     *
     * @SWG\Property(description="Company NIP", example="9222922292")
     * @Serializer\Groups({"output_redeem"})
     */
    private $companyNip;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Company street should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Company street is not a valid.",
     *     pattern="/^[a-zA-Z0-9., ]+$/i"
     * )
     * @Assert\Length(
     *      min = 3,
     *      max = 256,
     *      minMessage = "Company street must be at least {{ limit }} characters",
     *      maxMessage = "Company street cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=256)
     *
     * @SWG\Property(description="Company Street", example="Lekarska 1")
     * @Serializer\Groups({"output_redeem"})
     */
    private $companyStreet;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Company city should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Company city is not a valid.",
     *     pattern="/^[a-zA-Z0-9., ]+$/i"
     * )
     * @Assert\Length(
     *      min = 3,
     *      max = 128,
     *      minMessage = "Company city must be at least {{ limit }} characters",
     *      maxMessage = "Company city cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=128)
     *
     * @SWG\Property(description="Company City", example="Kraków")
     * @Serializer\Groups({"output_redeem"})
     */
    private $companyCity;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Company postcode should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Company postcode is not a valid.",
     *     pattern="/^[0-9-]+$/i"
     * )
     * @Assert\Length(
     *      min = 5,
     *      max = 16,
     *      minMessage = "Company postcode must be at least {{ limit }} characters",
     *      maxMessage = "Company postcode cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=16)
     *
     * @SWG\Property(description="Company Postal code", example="31-202")
     * @Serializer\Groups({"output_redeem"})
     */
    private $companyPostcode;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Company country should not be blank.",
     * )
     * @Assert\Regex(
     *     message = "Company country is not a valid.",
     *     pattern="/^[a-zA-Z0-9., ]+$/i"
     * )
     * @Assert\Length(
     *      min = 3,
     *      max = 128,
     *      minMessage = "Company country must be at least {{ limit }} characters",
     *      maxMessage = "Company country cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(type="string", length=128)
     *
     * @SWG\Property(description="Company Country", example="Poland")
     * @Serializer\Groups({"output_redeem"})
     */
    private $companyCountry;

    /**
     * Workspace constructor.
     * @param User $user
     * @param string $name
     * @param int $pin
     * @param Currency $defaultQuotedCurrency
     */
    public function __construct(User $user, string $name, int $pin, Currency $defaultQuotedCurrency)
    {
        $this->user = $user;
        $this->name = strtolower($name);
        $this->pin = $pin;
        $this->defaultQuotedCurrency = $defaultQuotedCurrency;

        // TODO - gdzie ma być sprawdzana weryfikacja workspace? dodać tam wszędzie warunki niezbędne
        $this->setVerified(false);
    }

    /**
     * Serialize and return public data of the object
     *
     * @param bool $extended
     * @return array
     */
    public function serialize(bool $extended = false) : array
    {
        $serialized = [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'defaultQuotedCurrency' => $this->defaultQuotedCurrency->serializeForPOSApi(),
            'companyName'           => $this->companyName,
            'companyNip'            => $this->companyNip,
            'companyStreet'         => $this->companyStreet,
            'companyCity'           => $this->companyCity,
            'companyPostcode'       => $this->companyPostcode,
            'companyCountry'        => $this->companyCountry,
            'isVerified'            => $this->isVerified(),
        ];

        if($extended){
        }

        return $serialized;
    }

    /**
     * @return array
     */
    public function serializeForRedeem() : array
    {
        return [
            'companyName'           => $this->companyName,
            'companyNip'            => $this->companyNip,
            'companyStreet'         => $this->companyStreet,
            'companyCity'           => $this->companyCity,
            'companyPostcode'       => $this->companyPostcode,
            'companyCountry'        => $this->companyCountry,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getPin(): int
    {
        return $this->pin;
    }

    /**
     * @param int $pin
     */
    public function setPin(int $pin): void
    {
        $this->pin = $pin;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     */
    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }

    /**
     * @return Currency
     */
    public function getDefaultQuotedCurrency(): Currency
    {
        return $this->defaultQuotedCurrency;
    }

    /**
     * @param Currency $defaultQuotedCurrency
     */
    public function setDefaultQuotedCurrency(Currency $defaultQuotedCurrency): void
    {
        $this->defaultQuotedCurrency = $defaultQuotedCurrency;
    }

    /**
     * @return bool
     */
    public function isGAuthEnabled(): bool
    {
        return $this->gAuthEnabled;
    }

    /**
     * @param bool $gAuthEnabled
     */
    public function setGAuthEnabled(bool $gAuthEnabled): void
    {
        $this->gAuthEnabled = $gAuthEnabled;
    }

    /**
     * @return string|null
     */
    public function getGAuthSecret(): ?string
    {
        return $this->gAuthSecret;
    }

    /**
     * @param string|null $gAuthSecret
     */
    public function setGAuthSecret(?string $gAuthSecret): void
    {
        $this->gAuthSecret = $gAuthSecret;
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string
     */
    public function getCompanyNip(): string
    {
        return $this->companyNip;
    }

    /**
     * @param string $companyNip
     */
    public function setCompanyNip(string $companyNip): void
    {
        $this->companyNip = $companyNip;
    }

    /**
     * @return string
     */
    public function getCompanyStreet(): string
    {
        return $this->companyStreet;
    }

    /**
     * @param string $companyStreet
     */
    public function setCompanyStreet(string $companyStreet): void
    {
        $this->companyStreet = $companyStreet;
    }

    /**
     * @return string
     */
    public function getCompanyCity(): string
    {
        return $this->companyCity;
    }

    /**
     * @param string $companyCity
     */
    public function setCompanyCity(string $companyCity): void
    {
        $this->companyCity = $companyCity;
    }

    /**
     * @return string
     */
    public function getCompanyPostcode(): string
    {
        return $this->companyPostcode;
    }

    /**
     * @param string $companyPostcode
     */
    public function setCompanyPostcode(string $companyPostcode): void
    {
        $this->companyPostcode = $companyPostcode;
    }

    /**
     * @return string
     */
    public function getCompanyCountry(): string
    {
        return $this->companyCountry;
    }

    /**
     * @param string $companyCountry
     */
    public function setCompanyCountry(string $companyCountry): void
    {
        $this->companyCountry = $companyCountry;
    }
}
