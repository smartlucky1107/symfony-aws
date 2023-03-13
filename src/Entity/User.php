<?php

namespace App\Entity;

use App\Entity\OrderBook\Order;
use App\Entity\POS\Workspace;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Configuration\VoterRole;
use App\Security\VoterRoleInterface;
use App\Entity\Wallet\Wallet;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields="email", message="This email is already in use")
 * @UniqueEntity(fields="uuid", message="This UUID is already in use")
 */
class User implements UserInterface
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'email'     => 'email',
        'firstName' => 'firstName',
        'lastName'  => 'lastName'
    ];

    const VERIFICATION_NULL             = 0;
    const VERIFICATION_TIER1_APPROVED   = 2;

    const VERIFICATION_TIER2_APPROVED   = 5;
    const VERIFICATION_TIER2_DECLINED   = 6;

    const VERIFICATION_TIER3_APPROVED   = 8;
    const VERIFICATION_TIER3_DECLINED   = 9;

    const VERIFICATION_STATUSES = [
        self::VERIFICATION_NULL,

        self::VERIFICATION_TIER1_APPROVED,

        self::VERIFICATION_TIER2_APPROVED,
        self::VERIFICATION_TIER2_DECLINED,

        self::VERIFICATION_TIER3_APPROVED,
        self::VERIFICATION_TIER3_DECLINED,
    ];
    const VERIFICATION_STATUS_NAMES = [
        self::VERIFICATION_NULL             => 'Not verified',

        self::VERIFICATION_TIER1_APPROVED   => 'Tier 1 approved',

        self::VERIFICATION_TIER2_APPROVED   => 'Tier 2 approved',
        self::VERIFICATION_TIER2_DECLINED   => 'Tier 2 declined',

        self::VERIFICATION_TIER3_APPROVED   => 'Tier 3 approved',
        self::VERIFICATION_TIER3_DECLINED   => 'Tier 3 declined',
    ];

    const VIRTUAL_WALLET_NOT_DECIDED    = NULL;
    const VIRTUAL_WALLET_INSTANT        = 1;
    const VIRTUAL_WALLET_NOT_INSTANT    = 2;
    const ALLOWED_VIRTUAL_WALLET_STATUSES = [
        self::VIRTUAL_WALLET_NOT_DECIDED,
        self::VIRTUAL_WALLET_INSTANT,
        self::VIRTUAL_WALLET_NOT_INSTANT,
    ];

    const TAG_FIAT_TRADE_SUSPENDED          = 'FIAT_TRADE_SUSPENDED';
    const TAG_FIAT_WITHDRAWAL_SUSPENDED     = 'FIAT_WITHDRAWAL_SUSPENDED';
    const TAG_FIAT_INTERNAL_TRANSFER_SUSPENDED     = 'FIAT_INTERNAL_TRANSFER_SUSPENDED';
    const TAG_FIAT_DEPOSIT_SUSPENDED        = 'FIAT_DEPOSIT_SUSPENDED';

    const TAG_CRYPTO_TRADE_SUSPENDED        = 'CRYPTO_TRADE_SUSPENDED';
    const TAG_CRYPTO_WITHDRAWAL_SUSPENDED   = 'CRYPTO_WITHDRAWAL_SUSPENDED';
    const TAG_CRYPTO_INTERNAL_TRANSFER_SUSPENDED   = 'CRYPTO_INTERNAL_TRANSFER_SUSPENDED';
    const TAG_CRYPTO_DEPOSIT_SUSPENDED      = 'CRYPTO_DEPOSIT_SUSPENDED';

    const TAGS = [
        self::TAG_FIAT_TRADE_SUSPENDED,
        self::TAG_FIAT_WITHDRAWAL_SUSPENDED,
        self::TAG_FIAT_INTERNAL_TRANSFER_SUSPENDED,
        self::TAG_FIAT_DEPOSIT_SUSPENDED,
        self::TAG_CRYPTO_TRADE_SUSPENDED,
        self::TAG_CRYPTO_WITHDRAWAL_SUSPENDED,
        self::TAG_CRYPTO_INTERNAL_TRANSFER_SUSPENDED,
        self::TAG_CRYPTO_DEPOSIT_SUSPENDED,
    ];

    const PEP_0 = 0;
    const PEP_1 = 1;
    const PEP_2 = 2;
    const PEP_3 = 3;
    const PEP_4 = 4;

    const ALLOWED_PEPS = [
        self::PEP_0,
        self::PEP_1,
        self::PEP_2,
        self::PEP_3,
        self::PEP_4
    ];

    const TYPE_PERSONAL = 1;
    const TYPE_PERSONAL_BUSINESS = 2;
    const TYPE_BUSINESS = 3;
    const ALLOWED_TYPES = [
        self::TYPE_PERSONAL,
        self::TYPE_PERSONAL_BUSINESS,
        self::TYPE_BUSINESS,
    ];

    const BUSINESS_TYPE_SP_JAWNA        = 1;
    const BUSINESS_TYPE_SP_PARTNERSKA   = 2;
    const BUSINESS_TYPE_SP_KOMANTYTOWA  = 3;
    const BUSINESS_TYPE_SP_KOMANDYTOWO_AKCYJNA  = 4;
    const BUSINESS_TYPE_SP_ZOO          = 5;
    const BUSINESS_TYPE_SP_AKCYJNA      = 6;
    const BUSINESS_TYPE_SP_PROSTA_AKCYJNA       = 7;
    const BUSINESS_TYPE_SP_CYWILNA      = 8;
    const BUSINESS_TYPE_INNE            = 9;
    const ALLOWED_BUSINESS_TYPES = [
        self::BUSINESS_TYPE_SP_JAWNA,
        self::BUSINESS_TYPE_SP_PARTNERSKA,
        self::BUSINESS_TYPE_SP_KOMANTYTOWA,
        self::BUSINESS_TYPE_SP_KOMANDYTOWO_AKCYJNA,
        self::BUSINESS_TYPE_SP_ZOO,
        self::BUSINESS_TYPE_SP_AKCYJNA,
        self::BUSINESS_TYPE_SP_PROSTA_AKCYJNA,
        self::BUSINESS_TYPE_SP_CYWILNA,
        self::BUSINESS_TYPE_INNE,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="uuid", unique=true, nullable=true)
     */
    private $uuid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     */
    private $createdAt;

    /**
     * @Assert\NotBlank(
     *     message = "The email should not be blank.",
     * )
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $email;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=128)
     */
    private $password;
    private $passwordPlain;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=128)
     */
    private $salt;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=128)
     */
    private $confirmationToken;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $passwordRequestedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $locale;

    /**
     * @var string|null
     *
     * @Assert\Regex(
     *     message = "First name is not a valid.",
     *     pattern="/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]+$/i"
     * )
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @Assert\Regex(
     *     message = "Last name is not a valid.",
     *     pattern="/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]+$/i"
     * )
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $lastName;

    /**
     * @var string|null
     *
     * @Assert\Regex(
     *     message = "Company name is not a valid.",
     *     pattern="/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ. -]+$/i"
     * )
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $companyName;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $phone;

    /**
     * @var string|null
     *
     * @Assert\Length(
     *      min = 11,
     *      max = 11,
     *      minMessage = "PESEL cannot be shorter than {{ limit }} characters",
     *      maxMessage = "PESEL cannot be longer than {{ limit }} characters",
     * )
     *
     * @ORM\Column(type="string", length=11, nullable=true)
     */
    private $pesel;

    /**
     * @var string|null
     *
     * @Assert\Length(
     *      min = 10,
     *      max = 10,
     *      minMessage = "NIP cannot be shorter than {{ limit }} characters",
     *      maxMessage = "NIP cannot be longer than {{ limit }} characters",
     * )
     *
     * @ORM\Column(type="string", length=11, nullable=true)
     */
    private $nip;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $identityExpirationDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Wallet\Wallet", mappedBy="user", orphanRemoval=true)
     */
    private $wallets;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderBook\Order", mappedBy="user", orphanRemoval=true)
     */
    private $orders;

### Address
#

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $building;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $apartment;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $state;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $postalCode;

    /**
     * @var Country|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=true)
     */
    private $country;


### Business Address
#

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessStreet;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessBuilding;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessApartment;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessCity;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessState;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $businessPostalCode;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessCountry;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $businessPKD;

#######
# > Statements
####

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $statementUserDataConfirmed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $statementRegulationsConfirmed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $statementPolicyPrivacyConfirmed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $statementMarketingConfirmed = false;

# < Statements
####

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $emailConfirmed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $phoneConfirmed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $tradingEnabled = false;

    /**
     * @var \Doctrine\Common\Collections\Collection|VoterRole[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Configuration\VoterRole")
     * @ORM\JoinTable(name="users_voter_roles",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="voter_role_id", referencedColumnName="id")}
     *      )
     */
    private $voterRoles;

    /**
     * @ORM\Column(type="json")
     */
    private $roles;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=false)
     */
    private $tags;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $wsHash;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $marketOrderAllowed = true;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $recentOrderAt;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private $verificationStatus;

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
     * @var ReferralLink|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ReferralLink")
     * @ORM\JoinColumn(name="referral_link_id", referencedColumnName="id", nullable=true)
     */
    private $referredBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirmationRemindedAt;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $pep;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $pepName;

    /**
     * @var Workspace|null
     *
     * @ORM\OneToOne(targetEntity="App\Entity\POS\Workspace", mappedBy="user", cascade={"persist", "remove"})
     */
    private $workspace;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $phoneConfirmationCode;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $phoneConfirmationCodeRequestedAt;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $phoneWrongConfirmations;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $regulationsChangesConfirmed = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $regulationsChangesConfirmedAt;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getAllowedTypes")
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @var int|null
     *
     * @Assert\Choice(callback="getAllowedBusinessTypes")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $businessType;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tier1ApprovedAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tier2ApprovedAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tier3ApprovedAt;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private $virtualWalletStatus;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $iAmlPepInfo;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->wallets = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->voterRoles = new ArrayCollection();

        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        $this->setCreatedAt(new \DateTime('now'));
        $this->setEmailConfirmed(false);
        $this->setPhoneConfirmed(false);
        $this->setConfirmationToken($this->generateConfirmationToken());
        $this->setLocale(null);

        $this->setStatementUserDataConfirmed(false);
        $this->setStatementRegulationsConfirmed(false);
        $this->setStatementPolicyPrivacyConfirmed(false);
        $this->setStatementMarketingConfirmed(false);

        $this->setTradingEnabled(false);

        $this->setPasswordRequestedAt(null);
        $this->setRoles(['ROLE_USER']);
        $this->setWsHash($this->generateConfirmationToken());
        $this->setMarketOrderAllowed(true);
        $this->setRecentOrderAt(null);
        $this->setVerificationStatus(self::VERIFICATION_NULL);
        $this->setIdentityExpirationDate(null);
        $this->setTags([]);

        $this->setGAuthEnabled(false);
        $this->setGAuthSecret(null);

        $this->setConfirmationRemindedAt(null);
        $this->setPep(self::PEP_0);

        $this->setPhoneConfirmationCode($this->generatePhoneConfirmationCode());

        $this->setRegulationsChangesConfirmed(true);
        $this->setRegulationsChangesConfirmedAt(new \DateTime('now'));

        $this->setPhoneWrongConfirmations(0);
        $this->setPasswordRequestedAt(null);

        $this->setType(self::TYPE_PERSONAL);

        $this->setUuid(Uuid::uuid4()->toString());
        $this->setPhoneConfirmed(true);
    }

    /**
     * Get allowed types as simple array.
     *
     * @return array
     */
    public static function getAllowedTypes(){
        return self::ALLOWED_TYPES;
    }

    /**
     * Verify if specified type is a valid type for the entity
     *
     * @param int|null $type
     * @return bool
     */
    public static function isTypeAllowed(?int $type) : bool
    {
        if(in_array($type, self::ALLOWED_TYPES)){
            return true;
        }

        return false;
    }

    /**
     * Get allowed business types as simple array.
     *
     * @return array
     */
    public static function getAllowedBusinessTypes(){
        return self::ALLOWED_BUSINESS_TYPES;
    }

    /**
     * Verify if specified business type is a valid type for the entity
     *
     * @param int|null $businessType
     * @return bool
     */
    public static function isBusinessTypeAllowed(?int $businessType) : bool
    {
        if(in_array($businessType, self::ALLOWED_BUSINESS_TYPES)){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getEmailEncrypted() : string
    {
        $firstLatter = substr($this->email, 0, 1);
        $stars = str_repeat('*', strpos($this->email, '@')-1);
        $domain = substr($this->email, strpos($this->email, '@'), strlen($this->email));

        $encrypted = $firstLatter.$stars.$domain;

        return $encrypted;
    }

    /**
     * @param string $plainPassword
     * @return bool
     */
    static public function isPasswordStrong(string $plainPassword) : bool
    {
        $uppercase = preg_match('@[A-Z]@', $plainPassword);
        $lowercase = preg_match('@[a-z]@', $plainPassword);
        $number    = preg_match('@[0-9]@', $plainPassword);
        $specialChars = preg_match('@[^\w]@', $plainPassword);

        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($plainPassword) < 8) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * @return string
     */
    public function verificationStatusName() : string
    {
        if(isset(self::VERIFICATION_STATUS_NAMES[$this->verificationStatus])){
            return self::VERIFICATION_STATUS_NAMES[$this->verificationStatus];
        }

        return '';
    }

    /**
     * Verify if password request is expired - created more then 24h ago
     *
     * @return bool
     * @throws \Exception
     */
    public function isPasswordRequestExpired(){
        $date = new \DateTime('now');
        $date->modify('-24 hours');

        if($this->passwordRequestedAt < $date){
            return true;
        }

        return false;
    }

    /**
     * Verify if confirmation code request is expired - created more than 1 minute ago
     *
     * @return bool
     * @throws \Exception
     */
    public function isPhoneConfirmationCodeRequestExpired(){
        if(is_null($this->phoneConfirmationCodeRequestedAt)) return false;

        $date = new \DateTime('now');
        $date->modify('-1 minute');

        if($this->phoneConfirmationCodeRequestedAt < $date){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isPhoneConfirmationCodeRequestAllowed(){
        if(is_null($this->phoneConfirmationCodeRequestedAt)) return true;

        $dateNow = new \DateTime('now');

        /** @var \DateTime $newCodeAllowedAt */
        $newCodeAllowedAt = new \DateTime($this->phoneConfirmationCodeRequestedAt->format('Y-m-d H:i:s'));
        $newCodeAllowedAt->modify('+1 minute');

        if($dateNow < $newCodeAllowedAt){
            return false;
        }

        return true;
    }

    /**
     * @param int|null $status
     * @return bool
     */
    public function isVerificationStatusAllowed(int $status = null){
        if(in_array($status, self::VERIFICATION_STATUSES)){
            return true;
        }

        return false;
    }

    /**
     * @param int|null $status
     * @return bool
     */
    public function isVirtualWalletStatusAllowed(int $status = null){
        if(in_array($status, self::ALLOWED_VIRTUAL_WALLET_STATUSES)){
            return true;
        }

        return false;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function isTagAllowed(string $tag){
        if(in_array($tag, self::TAGS)){
            return true;
        }

        return false;
    }

    /**
     * @param int $pep
     * @return bool
     */
    public function isPEPAllowed(int $pep){
        if(in_array($pep, self::ALLOWED_PEPS)){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isNewOrderAllowed() : bool
    {
        if($this->getRecentOrderAt()){
            $now = new \DateTime('now');
            $now->modify('-3 seconds');

            if($now > $this->getRecentOrderAt()){
                return true;
            }else{
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DateTime $dateOfBirth
     * @return bool
     * @throws \Exception
     */
    private function isDateOfBirthValid(\DateTime $dateOfBirth) : bool
    {
        $nowDate = new \DateTime('now');
        $nowDate->modify('-18 years');

        if($nowDate > $dateOfBirth){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function generateConfirmationToken() : string
    {
        return md5(uniqid() . rand(1, 10000));
    }

    /**
     * @return string
     */
    public function generatePhoneConfirmationCode() : string
    {
        return (string) rand(10000, 90000);
    }

    /**
     * @param $userId
     * @return string
     */
    static public function generateWsHash($userId) : string
    {
        $string = (string) $userId . '_' . 'WS';

        return md5($string);
    }

    /**
     * @param ExecutionContextInterface $context
     */
    private function validateAddress(ExecutionContextInterface $context)
    {
        if($this->getId()){
            if(empty($this->street)){
                $context->buildViolation(_('Street should not be empty'))->atPath('street')->addViolation();
            }
            if(empty($this->building)){
                $context->buildViolation(_('Building should not be empty'))->atPath('building')->addViolation();
            }
//            if(empty($this->apartment)){
//                $context->buildViolation(_('Apartment should not be empty'))->atPath('apartment')->addViolation();
//            }
            if(empty($this->city)){
                $context->buildViolation(_('City should not be empty'))->atPath('city')->addViolation();
            }
//            if(empty($this->state)){
//                $context->buildViolation(_('State should not be empty'))->atPath('state')->addViolation();
//            }
            if(empty($this->postalCode)){
                $context->buildViolation(_('Postal code should not be empty'))->atPath('postalCode')->addViolation();
            }

        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @throws \Exception
     */
    private function validatePersonal(ExecutionContextInterface $context)
    {
        $this->validateAddress($context);

        if($this->getId()){
            if(empty($this->dateOfBirth)){
//                $context->buildViolation(_('Date of birth should not be empty'))->atPath('dateOfBirth')->addViolation();
            }

            if($this->dateOfBirth instanceof \DateTime){
//                if(!$this->isDateOfBirthValid($this->dateOfBirth)){
//                    $context->buildViolation(_('In order to use our services you have to be at least 18 years old.'))->atPath('dateOfBirth')->addViolation();
//                }
            }

            if($this->getCountry()->getId() === 25){
//                if(empty($this->pesel)){
//                    $context->buildViolation(_('PESEL should not be empty'))->atPath('pesel')->addViolation();
//                }
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     */
    private function validatePersonalBusiness(ExecutionContextInterface $context)
    {
        $this->validateAddress($context);
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function validateBusiness(ExecutionContextInterface $context)
    {
        $this->validateAddress($context);

        if(empty($this->nip)){
            $context->buildViolation(_('NIP should not be empty'))->atPath('nip')->addViolation();
        }
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     * @return bool
     * @throws \Exception
     */
    public function validate(ExecutionContextInterface $context)
    {
        if($this->passwordPlain){
            if(!self::isPasswordStrong($this->passwordPlain)){
                $context->buildViolation(_('Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.'))->atPath('password')->addViolation();
            }
        }

        if(!$this->statementUserDataConfirmed){
            $context->buildViolation(_('User data statement should be confirmed'))->atPath('statementUserDataConfirmed')->addViolation();
        }
        if(!$this->statementRegulationsConfirmed){
            $context->buildViolation(_('Regulations statement should be confirmed'))->atPath('statementRegulationsConfirmed')->addViolation();
        }
        if(!$this->statementPolicyPrivacyConfirmed){
            $context->buildViolation(_('Policy privacy statement should be confirmed'))->atPath('statementPolicyPrivacyConfirmed')->addViolation();
        }

        if(!($this->country instanceof Country)){
            $context->buildViolation(_('Country should not be empty'))->atPath('country')->addViolation();
        }

        switch ($this->type){
            case self::TYPE_PERSONAL:
                $this->validatePersonal($context);

                break;
            case self::TYPE_PERSONAL_BUSINESS:
                $this->validatePersonalBusiness($context);

                break;
            case self::TYPE_BUSINESS:
                $this->validateBusiness($context);

                break;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isTier1Approved() : bool
    {
        if($this->isTier2Approved()) return true;

        if($this->verificationStatus === self::VERIFICATION_TIER2_DECLINED) return true;
        if($this->verificationStatus === self::VERIFICATION_TIER1_APPROVED) return true;

        return false;
    }

    /**
     * @return bool
     */
    public function isTier2Approved() : bool
    {
        if($this->isTier3Approved()) return true;

        if($this->verificationStatus === self::VERIFICATION_TIER3_DECLINED) return true;
        if($this->verificationStatus === self::VERIFICATION_TIER2_APPROVED) return true;

        return false;
    }

    /**
     * @return bool
     */
    public function isTier3Approved() : bool
    {
        if($this->verificationStatus === self::VERIFICATION_TIER3_APPROVED) return true;

        return false;
    }

    /**
     * Serialize and return public data of the object
     *
     * @param bool $extended
     * @return array
     */
    public function serialize(bool $extended = false) : array
    {
        $basicSerialized = $this->serializeBasic();
        $serialized = [
            'createdAt'     => $this->createdAt->format('Y-m-d H:i:s'),
            'phone'         => $this->phone,
            'dateOfBirth'   => $this->dateOfBirth ? $this->dateOfBirth->format('Y-m-d') : null,
            'isEmailConfirmed'      => $this->isEmailConfirmed(),
            'isPhoneConfirmed'      => $this->isPhoneConfirmed(),
            'isTradingEnabled'      => $this->isTradingEnabled(),
            'tier1ApprovedAt'       => $this->tier1ApprovedAt ? $this->tier1ApprovedAt->format('Y-m-d') : null,
            'tier2ApprovedAt'       => $this->tier2ApprovedAt ? $this->tier2ApprovedAt->format('Y-m-d') : null,
            'tier3ApprovedAt'       => $this->tier3ApprovedAt ? $this->tier3ApprovedAt->format('Y-m-d') : null,
            'virtualWalletStatus'   => $this->virtualWalletStatus,
            'isVirtualWalletAllowed'=> $this->isVirtualWalletAllowed(),
            'wsHash'                            => $this->wsHash,
            'tags'                              => $this->tags,
            'identityExpirationDate'            => ($this->identityExpirationDate instanceof \DateTime ? $this->identityExpirationDate->format('Y-m-d') : null),
            'isGAuthEnabled'                    => $this->isGAuthEnabled(),
        ];
        $addressSerialized = [
            'street'        => $this->street,
            'building'      => $this->building,
            'apartment'     => $this->apartment,
            'city'          => $this->city,
            'state'         => $this->state,
            'postalCode'    => $this->postalCode,
            'country'       => ($this->country instanceof Country ? $this->country->serialize() : null)
        ];
        $statementsSerialized = [
            'statementUserDataConfirmed'        => $this->statementUserDataConfirmed,
            'statementRegulationsConfirmed'     => $this->statementRegulationsConfirmed,
            'statementPolicyPrivacyConfirmed'   => $this->statementPolicyPrivacyConfirmed,
            'statementMarketingConfirmed'       => $this->statementMarketingConfirmed
        ];
        $verificationSerialized = [
            'isTier1Approved'                   => $this->isTier1Approved(),
            'isTier2Approved'                   => $this->isTier2Approved(),
            'isTier3Approved'                   => $this->isTier3Approved(),
            'verificationStatus'                => $this->verificationStatus,
            'verificationStatusName'            => $this->verificationStatusName(),
        ];

        if($extended){

        }

        return array_merge($basicSerialized, $serialized, $addressSerialized, $statementsSerialized, $verificationSerialized);
    }

    /**
     * @return bool
     */
    public function isVirtualWalletAllowed() : bool
    {
        switch ($this->virtualWalletStatus){
            case self::VIRTUAL_WALLET_NOT_DECIDED:
                return false;
            case self::VIRTUAL_WALLET_INSTANT:
                return true;
            case self::VIRTUAL_WALLET_NOT_INSTANT:
                if($this->tier2ApprovedAt instanceof \DateTime){
                    // TODO
                }else{
                    // TODO
                }

                break;
        }

        return false;
    }

    /**
     * Serialize and return public basic data of the object
     *
     * @return array
     */
    public function serializeBasic() : array
    {
        $data = [
            'id'            => $this->id,
            'email'         => $this->email,
            'fullName'      => $this->getFullName(),
            'locale'        => $this->locale,
            'type'          => $this->type,
        ];

        switch ($this->type) {
            case self::TYPE_PERSONAL:
                $userData = [
                    'firstName'     => $this->firstName,
                    'lastName'      => $this->lastName,
                ];

                break;

            case self::TYPE_PERSONAL_BUSINESS:
                $userData = [
                    'firstName'     => $this->firstName,
                    'lastName'      => $this->lastName,
                    'companyName'   => $this->companyName,
                    'nip'           => $this->nip,

                    'businessStreet'        => $this->businessStreet,
                    'businessBuilding'      => $this->businessBuilding,
                    'businessApartment'     => $this->businessApartment,
                    'businessCity'          => $this->businessCity,
                    'businessState'         => $this->businessState,
                    'businessPostalCode'    => $this->businessPostalCode,
                    'businessCountry'       => $this->businessCountry,

                    'businessPKD'           => $this->businessPKD,
                ];
                break;
            case self::TYPE_BUSINESS:
                $userData = [
                    'companyName'   => $this->companyName,
                    'nip'           => $this->nip,
                    'businessType'  => $this->businessType,

                    'businessStreet'        => $this->businessStreet,
                    'businessBuilding'      => $this->businessBuilding,
                    'businessApartment'     => $this->businessApartment,
                    'businessCity'          => $this->businessCity,
                    'businessState'         => $this->businessState,
                    'businessPostalCode'    => $this->businessPostalCode,
                    'businessCountry'       => $this->businessCountry,

                    'businessPKD'           => $this->businessPKD,
                ];
                break;
            default;
                $userData = [];
                break;
        }

        return array_merge($data, $userData);
    }

    /**
     * Return full name of the User
     *
     * @return string
     */
    public function getFullName() : string
    {
        switch ($this->type) {
            case self::TYPE_PERSONAL:
                return $this->firstName . ' ' . $this->lastName;
            case self::TYPE_PERSONAL_BUSINESS:
                return $this->companyName;
            case self::TYPE_BUSINESS:
                return $this->companyName;
            default;
                return '';
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @return bool
     */
    public function isAdmin() : bool
    {
        $roles = $this->getRoles();
        foreach($roles as $role){
            if($role === VoterRoleInterface::ROLE_ADMIN){
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isSuperAdmin() : bool
    {
        $roles = $this->getRoles();
        foreach($roles as $role){
            if($role === VoterRoleInterface::ROLE_SUPER_ADMIN){
                return true;
            }
        }

        return false;
    }

    public function eraseCredentials()
    {
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return User
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return User
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection|Wallet[]
     */
    public function getWallets(): Collection
    {
        return $this->wallets;
    }

    /**
     * @param Wallet $wallet
     * @return User
     */
    public function addWallet(Wallet $wallet): self
    {
        if (!$this->wallets->contains($wallet)) {
            $this->wallets[] = $wallet;
            $wallet->setUser($this);
        }

        return $this;
    }

    /**
     * @param Wallet $wallet
     * @return User
     */
    public function removeWallet(Wallet $wallet): self
    {
        // TODO - remove it

        if ($this->wallets->contains($wallet)) {
            $this->wallets->removeElement($wallet);
            // set the owning side to null (unless already changed)
            if ($wallet->getUser() === $this) {
                $wallet->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->contains($order)) {
            $this->orders->removeElement($order);
            // set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param mixed $street
     */
    public function setStreet($street): void
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getBuilding()
    {
        return $this->building;
    }

    /**
     * @param mixed $building
     */
    public function setBuilding($building): void
    {
        $this->building = $building;
    }

    /**
     * @return mixed
     */
    public function getApartment()
    {
        return $this->apartment;
    }

    /**
     * @param mixed $apartment
     */
    public function setApartment($apartment): void
    {
        $this->apartment = $apartment;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state): void
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param mixed $postalCode
     */
    public function setPostalCode($postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Country|null $country
     */
    public function setCountry(?Country $country): void
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * @param mixed $confirmationToken
     */
    public function setConfirmationToken($confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    /**
     * @return bool
     */
    public function isEmailConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    /**
     * @param bool $emailConfirmed
     */
    public function setEmailConfirmed(bool $emailConfirmed): void
    {
        $this->emailConfirmed = $emailConfirmed;
    }

    /**
     * @return bool
     */
    public function isPhoneConfirmed(): bool
    {
        return $this->phoneConfirmed;
    }

    /**
     * @param bool $phoneConfirmed
     */
    public function setPhoneConfirmed(bool $phoneConfirmed): void
    {
        $this->phoneConfirmed = $phoneConfirmed;
    }

    /**
     * @return Collection|VoterRole[]
     */
    public function getVoterRoles(): Collection
    {
        return $this->voterRoles;
    }

    /**
     * @param VoterRole $voterRole
     * @return User
     */
    public function addVoterRole(VoterRole $voterRole): self
    {
        if (!$this->voterRoles->contains($voterRole)) {
            $this->voterRoles[] = $voterRole;
        }

        return $this;
    }

    /**
     * @param VoterRole $voterRole
     * @return User
     */
    public function removeVoterRole(VoterRole $voterRole): self
    {
        if ($this->voterRoles->contains($voterRole)) {
            $this->voterRoles->removeElement($voterRole);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    /**
     * @param \DateTime|null $dateOfBirth
     */
    public function setDateOfBirth(?\DateTime $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return bool
     */
    public function isStatementUserDataConfirmed(): bool
    {
        return $this->statementUserDataConfirmed;
    }

    /**
     * @param bool $statementUserDataConfirmed
     */
    public function setStatementUserDataConfirmed(bool $statementUserDataConfirmed): void
    {
        $this->statementUserDataConfirmed = $statementUserDataConfirmed;
    }

    /**
     * @return bool
     */
    public function isStatementRegulationsConfirmed(): bool
    {
        return $this->statementRegulationsConfirmed;
    }

    /**
     * @param bool $statementRegulationsConfirmed
     */
    public function setStatementRegulationsConfirmed(bool $statementRegulationsConfirmed): void
    {
        $this->statementRegulationsConfirmed = $statementRegulationsConfirmed;
    }

    /**
     * @return bool
     */
    public function isStatementPolicyPrivacyConfirmed(): bool
    {
        return $this->statementPolicyPrivacyConfirmed;
    }

    /**
     * @param bool $statementPolicyPrivacyConfirmed
     */
    public function setStatementPolicyPrivacyConfirmed(bool $statementPolicyPrivacyConfirmed): void
    {
        $this->statementPolicyPrivacyConfirmed = $statementPolicyPrivacyConfirmed;
    }

    /**
     * @return bool
     */
    public function isStatementMarketingConfirmed(): bool
    {
        return $this->statementMarketingConfirmed;
    }

    /**
     * @param bool $statementMarketingConfirmed
     */
    public function setStatementMarketingConfirmed(bool $statementMarketingConfirmed): void
    {
        $this->statementMarketingConfirmed = $statementMarketingConfirmed;
    }

    /**
     * @return bool
     */
    public function isTradingEnabled(): bool
    {
        return $this->tradingEnabled;
    }

    /**
     * @param bool $tradingEnabled
     */
    public function setTradingEnabled(bool $tradingEnabled): void
    {
        $this->tradingEnabled = $tradingEnabled;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return \DateTime|null
     */
    public function getPasswordRequestedAt(): ?\DateTime
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param \DateTime|null $passwordRequestedAt
     */
    public function setPasswordRequestedAt(?\DateTime $passwordRequestedAt): void
    {
        $this->passwordRequestedAt = $passwordRequestedAt;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return string|null
     */
    public function getWsHash(): ?string
    {
        return $this->wsHash;
    }

    /**
     * @param string|null $wsHash
     */
    public function setWsHash(?string $wsHash): void
    {
        $this->wsHash = $wsHash;
    }

    /**
     * @return bool
     */
    public function isMarketOrderAllowed(): bool
    {
        return $this->marketOrderAllowed;
    }

    /**
     * @param bool $marketOrderAllowed
     */
    public function setMarketOrderAllowed(bool $marketOrderAllowed): void
    {
        $this->marketOrderAllowed = $marketOrderAllowed;
    }

    /**
     * @return \DateTime|null
     */
    public function getRecentOrderAt(): ?\DateTime
    {
        return $this->recentOrderAt;
    }

    /**
     * @param \DateTime|null $recentOrderAt
     */
    public function setRecentOrderAt(?\DateTime $recentOrderAt): void
    {
        $this->recentOrderAt = $recentOrderAt;
    }

    /**
     * @return int|null
     */
    public function getVerificationStatus(): ?int
    {
        return $this->verificationStatus;
    }

    /**
     * @param int|null $verificationStatus
     */
    public function setVerificationStatus(?int $verificationStatus): void
    {
        $this->verificationStatus = $verificationStatus;
    }

    /**
     * @return \DateTime|null
     */
    public function getIdentityExpirationDate(): ?\DateTime
    {
        return $this->identityExpirationDate;
    }

    /**
     * @param \DateTime|null $identityExpirationDate
     */
    public function setIdentityExpirationDate(?\DateTime $identityExpirationDate): void
    {
        $this->identityExpirationDate = $identityExpirationDate;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return mixed
     */
    public function getPasswordPlain()
    {
        return $this->passwordPlain;
    }

    /**
     * @param mixed $passwordPlain
     */
    public function setPasswordPlain($passwordPlain): void
    {
        $this->passwordPlain = $passwordPlain;
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
     * @return ReferralLink|null
     */
    public function getReferredBy(): ?ReferralLink
    {
        return $this->referredBy;
    }

    /**
     * @param ReferralLink|null $referredBy
     */
    public function setReferredBy(?ReferralLink $referredBy): void
    {
        $this->referredBy = $referredBy;
    }

    /**
     * @return \DateTime|null
     */
    public function getConfirmationRemindedAt(): ?\DateTime
    {
        return $this->confirmationRemindedAt;
    }

    /**
     * @param \DateTime|null $confirmationRemindedAt
     */
    public function setConfirmationRemindedAt(?\DateTime $confirmationRemindedAt): void
    {
        $this->confirmationRemindedAt = $confirmationRemindedAt;
    }

    /**
     * @return int
     */
    public function getPep(): int
    {
        return $this->pep;
    }

    /**
     * @param int $pep
     */
    public function setPep(int $pep): void
    {
        $this->pep = $pep;
    }

    /**
     * @return string|null
     */
    public function getPepName(): ?string
    {
        return $this->pepName;
    }

    /**
     * @param string|null $pepName
     */
    public function setPepName(?string $pepName): void
    {
        $this->pepName = $pepName;
    }

    /**
     * @return Workspace|null
     */
    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    /**
     * @param Workspace $workspace
     * @return User
     */
    public function setWorkspace(Workspace $workspace): self
    {
        $this->workspace = $workspace;

        // set the owning side of the relation if necessary
        if ($this !== $workspace->getUser()) {
            $workspace->setUser($this);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneConfirmationCode(): ?string
    {
        return $this->phoneConfirmationCode;
    }

    /**
     * @param string|null $phoneConfirmationCode
     */
    public function setPhoneConfirmationCode(?string $phoneConfirmationCode): void
    {
        $this->phoneConfirmationCode = $phoneConfirmationCode;
    }

    /**
     * @return bool
     */
    public function isRegulationsChangesConfirmed(): bool
    {
        return $this->regulationsChangesConfirmed;
    }

    /**
     * @param bool $regulationsChangesConfirmed
     */
    public function setRegulationsChangesConfirmed(bool $regulationsChangesConfirmed): void
    {
        $this->regulationsChangesConfirmed = $regulationsChangesConfirmed;
    }

    /**
     * @return \DateTime|null
     */
    public function getRegulationsChangesConfirmedAt(): ?\DateTime
    {
        return $this->regulationsChangesConfirmedAt;
    }

    /**
     * @param \DateTime|null $regulationsChangesConfirmedAt
     */
    public function setRegulationsChangesConfirmedAt(?\DateTime $regulationsChangesConfirmedAt): void
    {
        $this->regulationsChangesConfirmedAt = $regulationsChangesConfirmedAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getPhoneConfirmationCodeRequestedAt(): ?\DateTime
    {
        return $this->phoneConfirmationCodeRequestedAt;
    }

    /**
     * @param \DateTime|null $phoneConfirmationCodeRequestedAt
     */
    public function setPhoneConfirmationCodeRequestedAt(?\DateTime $phoneConfirmationCodeRequestedAt): void
    {
        $this->phoneConfirmationCodeRequestedAt = $phoneConfirmationCodeRequestedAt;
    }

    /**
     * @return int
     */
    public function getPhoneWrongConfirmations(): int
    {
        return $this->phoneWrongConfirmations;
    }

    /**
     * @param int $phoneWrongConfirmations
     */
    public function setPhoneWrongConfirmations(int $phoneWrongConfirmations): void
    {
        $this->phoneWrongConfirmations = $phoneWrongConfirmations;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string|null $uuid
     */
    public function setUuid(?string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string|null $companyName
     */
    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return int|null
     */
    public function getBusinessType(): ?int
    {
        return $this->businessType;
    }

    /**
     * @param int|null $businessType
     */
    public function setBusinessType(?int $businessType): void
    {
        $this->businessType = $businessType;
    }

    /**
     * @return string|null
     */
    public function getPesel(): ?string
    {
        return $this->pesel;
    }

    /**
     * @param string|null $pesel
     */
    public function setPesel(?string $pesel): void
    {
        $this->pesel = $pesel;
    }

    /**
     * @return string|null
     */
    public function getNip(): ?string
    {
        return $this->nip;
    }

    /**
     * @param string|null $nip
     */
    public function setNip(?string $nip): void
    {
        $this->nip = $nip;
    }

    /**
     * @return mixed
     */
    public function getBusinessStreet()
    {
        return $this->businessStreet;
    }

    /**
     * @param mixed $businessStreet
     */
    public function setBusinessStreet($businessStreet): void
    {
        $this->businessStreet = $businessStreet;
    }

    /**
     * @return mixed
     */
    public function getBusinessBuilding()
    {
        return $this->businessBuilding;
    }

    /**
     * @param mixed $businessBuilding
     */
    public function setBusinessBuilding($businessBuilding): void
    {
        $this->businessBuilding = $businessBuilding;
    }

    /**
     * @return mixed
     */
    public function getBusinessApartment()
    {
        return $this->businessApartment;
    }

    /**
     * @param mixed $businessApartment
     */
    public function setBusinessApartment($businessApartment): void
    {
        $this->businessApartment = $businessApartment;
    }

    /**
     * @return mixed
     */
    public function getBusinessCity()
    {
        return $this->businessCity;
    }

    /**
     * @param mixed $businessCity
     */
    public function setBusinessCity($businessCity): void
    {
        $this->businessCity = $businessCity;
    }

    /**
     * @return mixed
     */
    public function getBusinessState()
    {
        return $this->businessState;
    }

    /**
     * @param mixed $businessState
     */
    public function setBusinessState($businessState): void
    {
        $this->businessState = $businessState;
    }

    /**
     * @return mixed
     */
    public function getBusinessPostalCode()
    {
        return $this->businessPostalCode;
    }

    /**
     * @param mixed $businessPostalCode
     */
    public function setBusinessPostalCode($businessPostalCode): void
    {
        $this->businessPostalCode = $businessPostalCode;
    }

    /**
     * @return mixed
     */
    public function getBusinessCountry()
    {
        return $this->businessCountry;
    }

    /**
     * @param mixed $businessCountry
     */
    public function setBusinessCountry($businessCountry): void
    {
        $this->businessCountry = $businessCountry;
    }

    /**
     * @return mixed
     */
    public function getBusinessPKD()
    {
        return $this->businessPKD;
    }

    /**
     * @param mixed $businessPKD
     */
    public function setBusinessPKD($businessPKD): void
    {
        $this->businessPKD = $businessPKD;
    }

    /**
     * @return \DateTime|null
     */
    public function getTier1ApprovedAt(): ?\DateTime
    {
        return $this->tier1ApprovedAt;
    }

    /**
     * @param \DateTime|null $tier1ApprovedAt
     */
    public function setTier1ApprovedAt(?\DateTime $tier1ApprovedAt): void
    {
        $this->tier1ApprovedAt = $tier1ApprovedAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getTier2ApprovedAt(): ?\DateTime
    {
        return $this->tier2ApprovedAt;
    }

    /**
     * @param \DateTime|null $tier2ApprovedAt
     */
    public function setTier2ApprovedAt(?\DateTime $tier2ApprovedAt): void
    {
        $this->tier2ApprovedAt = $tier2ApprovedAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getTier3ApprovedAt(): ?\DateTime
    {
        return $this->tier3ApprovedAt;
    }

    /**
     * @param \DateTime|null $tier3ApprovedAt
     */
    public function setTier3ApprovedAt(?\DateTime $tier3ApprovedAt): void
    {
        $this->tier3ApprovedAt = $tier3ApprovedAt;
    }

    /**
     * @return int|null
     */
    public function getVirtualWalletStatus(): ?int
    {
        return $this->virtualWalletStatus;
    }

    /**
     * @param int|null $virtualWalletStatus
     */
    public function setVirtualWalletStatus(?int $virtualWalletStatus): void
    {
        $this->virtualWalletStatus = $virtualWalletStatus;
    }

    /**
     * @return array|null
     */
    public function getIAmlPepInfo(): ?array
    {
        return $this->iAmlPepInfo;
    }

    /**
     * @param array|null $iAmlPepInfo
     */
    public function setIAmlPepInfo(?array $iAmlPepInfo): void
    {
        $this->iAmlPepInfo = $iAmlPepInfo;
    }
}

