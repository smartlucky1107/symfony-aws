<?php

namespace App\DataTransformer;

use App\Entity\Country;
use App\Entity\ReferralLink;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\EmailInterface;
use App\Repository\CountryRepository;
use App\Repository\ReferralLinkRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTransformer extends AppTransformer
{
    /** @var CountryRepository */
    private $countryRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var ReferralLinkRepository */
    private $referralLinkRepository;

    /** @var UserPasswordEncoderInterface */
    private $encoder;

    /**
     * UserTransformer constructor.
     * @param CountryRepository $countryRepository
     * @param UserRepository $userRepository
     * @param ReferralLinkRepository $referralLinkRepository
     * @param UserPasswordEncoderInterface $encoder
     * @param ValidatorInterface $validator
     */
    public function __construct(CountryRepository $countryRepository, UserRepository $userRepository, ReferralLinkRepository $referralLinkRepository, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator)
    {
        $this->countryRepository = $countryRepository;
        $this->userRepository = $userRepository;
        $this->referralLinkRepository = $referralLinkRepository;
        $this->encoder = $encoder;

        parent::__construct($validator);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return User
     */
    private function transformAddress(Request $request, User $user) : User
    {
        $user->setStreet((string)$request->request->get('street', ''));
        $user->setBuilding((string)$request->request->get('building', ''));
        $user->setApartment((string)$request->request->get('apartment', ''));
        $user->setCity((string)$request->request->get('city', ''));
        $user->setState((string)$request->request->get('state', ''));
        $user->setPostalCode((string)$request->request->get('postalCode', ''));

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return User
     */
    private function transformBusinessAddress(Request $request, User $user) : User
    {
        $user->setBusinessStreet((string)$request->request->get('businessStreet', ''));
        $user->setBusinessBuilding((string)$request->request->get('businessBuilding', ''));
        $user->setBusinessApartment((string)$request->request->get('businessApartment', ''));
        $user->setBusinessCity((string)$request->request->get('businessCity', ''));
        $user->setBusinessState((string)$request->request->get('businessState', ''));
        $user->setBusinessPostalCode((string)$request->request->get('businessPostalCode', ''));
        $user->setBusinessCountry((string)$request->request->get('businessCountry', ''));

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @param bool $adminMode
     * @return User
     * @throws AppException
     */
    public function transformExisting(Request $request, User $user, bool $adminMode = false): User
    {
        if(!$adminMode){
            if($user->getVerificationStatus() !== User::VERIFICATION_NULL) throw new AppException('User verification status does not allowed to change data');
        }

        if ($request->request->has('countryId')) {
            $countryId = (int)$request->request->get('countryId');
            /** @var Country $country */
            $country = $this->countryRepository->find($countryId);

            $user->setCountry($country);
        }

        // resolve data by user type
        switch ($user->getType()){
            case User::TYPE_PERSONAL:
                $user = $this->transformAddress($request, $user);

                $user = $this->transformBusinessAddress($request, $user);
                $user->setBusinessPKD((string)$request->get('pkd', ''));

                $user->setFirstName((string)$request->get('firstName', ''));
                $user->setLastName((string)$request->get('lastName', ''));
                $user->setDateOfBirth(new \DateTime((string)$request->request->get('dateOfBirth', '')));
                $user->setPesel((string)$request->request->get('pesel', ''));

                if ($request->get('identityExpirationDate')) {
                    /** @var \DateTime $identityExpirationDate */
                    $identityExpirationDate = new \DateTime($request->get('identityExpirationDate'));
                    $user->setIdentityExpirationDate($identityExpirationDate);
                }

                break;
            case  User::TYPE_PERSONAL_BUSINESS:
                $user = $this->transformAddress($request, $user);

                $user = $this->transformBusinessAddress($request, $user);
                $user->setBusinessPKD((string)$request->get('pkd', ''));

                $user->setFirstName((string)$request->get('firstName', ''));
                $user->setLastName((string)$request->get('lastName', ''));
                $user->setCompanyName((string)$request->get('companyName', ''));

                $user->setDateOfBirth(new \DateTime((string)$request->request->get('dateOfBirth', '')));
                $user->setPesel((string)$request->request->get('pesel', ''));

                if ($request->get('identityExpirationDate')) {
                    /** @var \DateTime $identityExpirationDate */
                    $identityExpirationDate = new \DateTime($request->get('identityExpirationDate'));
                    $user->setIdentityExpirationDate($identityExpirationDate);
                }

                break;
            case User::TYPE_BUSINESS:
                $user = $this->transformAddress($request, $user);
                $user->setCompanyName((string)$request->get('companyName', ''));

                break;
        }

        // find duplicates and block it
//        if($this->userRepository->duplicateExists($user)){
//            throw new AppException('User already exists');
//        }

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return User
     * @throws AppException
     */
    public function transformPersonal(Request $request, User $user): User
    {
        $firstName = (string) $request->get('firstName', '');
        if(empty($firstName)) throw new AppException('First name is required');

        $lastName = (string) $request->get('lastName', '');
        if(empty($lastName)) throw new AppException('Last name is required');

        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return User
     * @throws AppException
     */
    public function transformPersonalBusiness(Request $request, User $user): User
    {
        $firstName = (string) $request->get('firstName', '');
        if(empty($firstName)) throw new AppException('First name is required');

        $lastName = (string) $request->get('lastName', '');
        if(empty($lastName)) throw new AppException('Last name is required');

        $companyName = (string) $request->get('companyName', '');
        if(empty($companyName)) throw new AppException('Company name is required');

        $nip = (string) $request->get('nip', '');
        if(empty($nip)) throw new AppException('NIP is required');

        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setCompanyName($companyName);
        $user->setNip($nip);

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return User
     * @throws AppException
     */
    public function transformBusiness(Request $request, User $user): User
    {
        $companyName = (string) $request->get('companyName', '');
        if(empty($companyName)) throw new AppException('Company name is required');

        $user->setCompanyName($companyName);

        $nip = (string) $request->get('nip', '');
        if(empty($nip)) throw new AppException('NIP is required');

        $user->setNip($nip);

        $businessType = $request->get('businessType');
        if(is_null($businessType)) throw new AppException('Business type is required');
        $businessType = (int) $businessType;

        if(!User::isBusinessTypeAllowed($businessType)) throw new AppException('Business type is invalid');

        return $user;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return User
     */
    public function transformPassword(Request $request, User $user) : User
    {
        switch ($user->getType()){
            case User::TYPE_PERSONAL:
                $password = (string) $request->get('password', '');

                break;
            case  User::TYPE_PERSONAL_BUSINESS:
                $password = (string) $request->get('password', '');

                break;
            case User::TYPE_BUSINESS:
                $password = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
                $password .= strtolower(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
                $password .= substr(str_shuffle('!@#$%&-_'), 0, 2);
                $password .= substr(str_shuffle('0123456789'), 0, 3);

                break;
            default:
                $password = '';

                break;
        }

        if($password){
            $user->setPassword($this->encoder->encodePassword($user, $password));
            $user->setPasswordPlain($password);
        }else{
            $user->setPassword(null);
        }

        return $user;
    }

    /**
     * Transform $request parameters to new User object or existing $user when passed.
     *
     * @param Request $request
     * @param User|null $user
     * @param bool $adminMode
     * @return User
     * @throws AppException
     */
    public function transform(Request $request, User $user = null, bool $adminMode = false) : User
    {
        if($user instanceof User) return $this->transformExisting($request, $user, $adminMode);

        $type = $request->get('type');
        if(is_null($type)) throw new AppException('User type is required');
        $type = (int) $type;

        if(!User::isTypeAllowed($type)) throw new AppException('User type is invalid');

        /** @var User $user */
        $user = new User();
        $user->setType($type);

        // authorization data
        $user = $this->transformPassword($request, $user);

        $user->setEmail((string) $request->get('email', ''));
        $user->setLocale($request->getLocale());

        if($request->request->has('countryId')){
            $countryId = (int) $request->request->get('countryId');
        }else{
            $countryId = 25; // POLAND
        }
        /** @var Country $country */
        $country = $this->countryRepository->find($countryId);
        if($country instanceof Country) $user->setCountry($country);

        // resolve data by user type
        switch ($type){
            case User::TYPE_PERSONAL:
                $user = $this->transformPersonal($request, $user);

                break;
            case  User::TYPE_PERSONAL_BUSINESS:
                $user = $this->transformPersonalBusiness($request, $user);

                break;
            case User::TYPE_BUSINESS:
                $user = $this->transformBusiness($request, $user);

                break;
        }

        // statements
        $user->setStatementUserDataConfirmed((bool) $request->request->get('statementUserData'));
        $user->setStatementRegulationsConfirmed((bool) $request->request->get('statementRegulations'));
        $user->setStatementPolicyPrivacyConfirmed((bool) $request->request->get('statementPolicyPrivacy'));
        $user->setStatementMarketingConfirmed((bool) $request->request->get('statementMarketing', false));  // not required

        // statements
        $ref = (string) $request->request->get('ref', '');
        /** @var ReferralLink $referralLink */
        $referralLink = $this->referralLinkRepository->findOneBy(['link' => $ref]);
        if($referralLink instanceof ReferralLink)   $user->setReferredBy($referralLink);

        if($request->request->has('pep')){
            $pep = $request->request->get('pep');
            if($user->isPEPAllowed($pep)){
                $user->setPep($pep);
            }
        }

        if($request->request->has('pepName')){
            $user->setPepName($request->request->get('pepName'));
        }

        return $user;
    }
}
