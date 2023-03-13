<?php

namespace App\Manager;

use App\Document\NotificationInterface;
use App\Entity\Configuration\VoterRole;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\SMS\SerwerSMSManager;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
    /** @var UserRepository */
    private $userRepository;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var WalletGenerator */
    private $walletGenerator;

    /** @var UserPasswordEncoderInterface */
    private $encoder;

    /** @var ReferralManager */
    private $referralManager;

    /** @var MailerLiteManager */
    private $mailerLiteManager;

    /** @var SerwerSMSManager */
    private $serwerSMSManager;

    /**
     * UserManager constructor.
     * @param UserRepository $userRepository
     * @param NotificationManager $notificationManager
     * @param WalletGenerator $walletGenerator
     * @param UserPasswordEncoderInterface $encoder
     * @param ReferralManager $referralManager
     * @param MailerLiteManager $mailerLiteManager
     * @param SerwerSMSManager $serwerSMSManager
     */
    public function __construct(UserRepository $userRepository, NotificationManager $notificationManager, WalletGenerator $walletGenerator, UserPasswordEncoderInterface $encoder, ReferralManager $referralManager, MailerLiteManager $mailerLiteManager, SerwerSMSManager $serwerSMSManager)
    {
        $this->userRepository = $userRepository;
        $this->notificationManager = $notificationManager;
        $this->walletGenerator = $walletGenerator;
        $this->encoder = $encoder;
        $this->referralManager = $referralManager;
        $this->mailerLiteManager = $mailerLiteManager;
        $this->serwerSMSManager = $serwerSMSManager;
    }

    /**
     * Load User to the class by $userId
     *
     * @param int $userId
     * @return User
     * @throws AppException
     */
    public function load(int $userId) : User
    {
        $user = $this->userRepository->find($userId);
        if(!($user instanceof User)) throw new AppException('error.user.not_found');

        return $user;
    }

    /**
     * @param string $email
     * @return User
     * @throws AppException
     */
    public function loadByEmail(string $email) : User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if(!($user instanceof User)) throw new AppException('error.user.not_found');

        return $user;
    }

    /**
     * Load user into the class by confirmation token
     *
     * @param string $email
     * @param string $confirmationToken
     * @return User
     * @throws AppException
     */
    public function loadByEmailConfirmationToken(string $email, string $confirmationToken) : User
    {
        $user = $this->userRepository->findOneBy(['email' => $email, 'confirmationToken' => $confirmationToken]);
        if(!($user instanceof User)) throw new AppException('error.user.not_found');

        return $user;
    }

    /**
     * Confirm email of the user loaded in the class
     *
     * @param User $user
     * @param string $email
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function confirmEmail(User $user, string $email) : User
    {
        if($user->isEmailConfirmed()) throw new AppException('error.user.already_confirmed');
        if($email !== $user->getEmail()) throw new AppException('error.user.email_doesnt_match');

        $user->setEmailConfirmed(true);
        $user->setWsHash(User::generateWsHash($user->getId()));
        $user->setTradingEnabled(true);

        $user = $this->userRepository->save($user);

        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_EMAIL_CONFIRMED, $user);
        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_EMAIL_CONFIRMED, ['user' => $user, 'id' => $user->getId()]);

//        try{
//            $this->mailerLiteManager->postUserToGroup($user, true);
//        }catch (\Exception $exception){
//            // do nothing
//        }

        return $user;
    }

    /**
     * @param User $user
     * @param string $confirmationCode
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function confirmPhone(User $user, string $confirmationCode) : User
    {
        if($user->isPhoneConfirmed()) throw new AppException('error.user.already_confirmed');
        if($user->getPhoneWrongConfirmations() > 5) throw new AppException('Number of requests exceeded. Please contact support.');

        if($user->getPhoneConfirmationCode() !== $confirmationCode) {
            $wrongConfirmations = $user->getPhoneWrongConfirmations();
            $wrongConfirmations++;
            $user->setPhoneWrongConfirmations($wrongConfirmations);

            $this->userRepository->save($user);

            throw new AppException('Confirmation code is not valid');
        }

        $user->setPhoneWrongConfirmations(0);
        $user->setPhoneConfirmed(true);
        $user = $this->userRepository->save($user);

        // TODO implement notifications
//        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_PHONE_CONFIRMED, $user);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendPhoneCode(User $user) : User
    {
        if($user->isPhoneConfirmed()) throw new AppException('error.user.already_confirmed');
        if($user->getPhoneWrongConfirmations() > 5) throw new AppException('Number of requests exceeded. Please contact support.');
        if(!$user->isPhoneConfirmationCodeRequestAllowed()) throw new AppException('You cannot request new code. Please wait and try again.');

        $code = $user->generatePhoneConfirmationCode();
        $user->setPhoneConfirmationCode($code);
        $user->setPhoneConfirmationCodeRequestedAt(new \DateTime('now'));

        $user = $this->userRepository->save($user);

        $this->serwerSMSManager->sendSMS($user->getPhone(), 'Kod weryfikacyjny do konta w portalu swapcoin.today. Kod SMS: ' . $user->getPhoneConfirmationCode());

        return $user;
    }

    /**
     * Request to reset user's password
     *
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function requestPassword(User $user){
        $token = $user->generateConfirmationToken();

        $user->setConfirmationToken($token);
        $user->setPasswordRequestedAt(new \DateTime('now'));

        $user = $this->userRepository->save($user);

        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_PASSWORD_REQUEST, $user);
        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_PASSWORD_REQUEST, ['user' => $user, 'id' => $user->getId()]);

        return $user;
    }

    /**
     * @param User $user
     * @param string $password
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function resetPassword(User $user, string $password) : User
    {
        if($user->isPasswordRequestExpired()) throw new AppException('Password request expired');
        if(!User::isPasswordStrong($password)){
            throw new AppException('Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.');
        }

        $user->setPassword($this->encoder->encodePassword($user, $password));

        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @param VoterRole $voterRole
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function voterRoleGrant(User $user, VoterRole $voterRole) : User
    {
        $user->addVoterRole($voterRole);
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @param VoterRole $voterRole
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function voterRoleDeny(User $user, VoterRole $voterRole) : User
    {
        $user->removeVoterRole($voterRole);
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function toggleEmailConfirmed(User $user) : User
    {
        if($user->isEmailConfirmed()){
            $user->setEmailConfirmed(false);
        }else{
            $user->setEmailConfirmed(true);
        }

        $user->setWsHash(User::generateWsHash($user->getId()));

        return $this->userRepository->save($user);
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function toggleTradingEnabled(User $user) : User
    {
        if($user->isTradingEnabled()){
            $user->setTradingEnabled(false);
        }else{
            $user->setTradingEnabled(true);
        }

        return $this->userRepository->save($user);
    }

    /**
     * Register user
     *
     * @param User $user
     * @param bool $sendNotification
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function register(User $user, bool $sendNotification = true) : User
    {
        $user = $this->userRepository->save($user);

        $this->walletGenerator->generateForUser($user);

        if($sendNotification){
            $this->notificationManager->create($user, NotificationInterface::TYPE_USER_REGISTERED, $user);
            $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_REGISTERED, ['user' => $user, 'id' => $user->getId()]);
        }

//        try{
//            $this->mailerLiteManager->postUserToGroup($user);
//        }catch (\Exception $exception){
//            // do nothing
//        }

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     */
    public function resendConfirmation(User $user) : User
    {
        if($user->isEmailConfirmed()) throw new AppException('E-mail already confirmed');

        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_REGISTERED, ['user' => $user, 'id' => $user->getId()]);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(User $user) : User
    {
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @param string $tag
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function assignTag(User $user, string $tag){
        if(!$user->isTagAllowed($tag)) throw new AppException('Tag not allowed');

        $tags = $user->getTags();
        if(!in_array($tag, $tags)){
            $tags[] = $tag;
            $user->setTags($tags);

            return $this->userRepository->save($user);
        }

        return $user;
    }

    /**
     * @param User $user
     * @param string $secret
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enableGAuth(User $user, string $secret) : User
    {
        $user->setGAuthEnabled(true);
        $user->setGAuthSecret($secret);

        return $this->userRepository->save($user);
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableGAuth(User $user) : User
    {
        $user->setGAuthEnabled(false);
        $user->setGAuthSecret(null);

        return $this->userRepository->save($user);
    }

    /**
     * @param User $user
     * @param string $tag
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unassignTag(User $user, string $tag){
        if(!$user->isTagAllowed($tag)) throw new AppException('Tag not allowed');

        $tags = $user->getTags();
        if(in_array($tag, $tags)){
            foreach($tags as $key => $tagValue){
                if($tagValue === $tag){
                    unset($tags[$key]);
                }
            }

            $newTags = [];
            foreach($tags as $tag){
                $newTags[] = $tag;
            }

            $user->setTags($newTags);

            return $this->userRepository->save($user);
        }

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeUser(User $user) : User
    {
        if(!$user->isEmailConfirmed() && !$user->isTradingEnabled() && !$user->isPhoneConfirmed()) throw new AppException('User already removed');

        $user->setEmailConfirmed(false);
        $user->setTradingEnabled(false);
        $user->setPhoneConfirmed(false);

        $user->setEmail(base64_encode($user->getEmail()));

        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approveTier1(User $user) : User
    {
        $user->setVerificationStatus(User::VERIFICATION_TIER1_APPROVED);
        $user->setTier1ApprovedAt(new \DateTime());

        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approveTier2(User $user) : User
    {
        $user->setVerificationStatus(User::VERIFICATION_TIER2_APPROVED);
        $user->setTier2ApprovedAt(new \DateTime());
        $user = $this->userRepository->save($user);

        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_TIER2_APPROVED, $user);
        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_TIER2_APPROVED, ['user' => $user, 'id' => $user->getId()]);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approveTier3(User $user) : User
    {
        $user->setVerificationStatus(User::VERIFICATION_TIER3_APPROVED);
        $user->setTier3ApprovedAt(new \DateTime());
        $user->setTradingEnabled(true);

        $user = $this->userRepository->save($user);

        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_TIER3_APPROVED, $user);
        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_TIER3_APPROVED, ['user' => $user, 'id' => $user->getId()]);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function resetTierVerification(User $user) : User
    {
        $user->setVerificationStatus(User::VERIFICATION_TIER2_DECLINED);
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declineTier2(User $user) : User
    {
        $user->setVerificationStatus(User::VERIFICATION_TIER2_DECLINED);
        $user->setTradingEnabled(false);

        $user = $this->userRepository->save($user);

        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_TIER2_DECLINED, $user);
        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_TIER2_DECLINED, ['user' => $user, 'id' => $user->getId()]);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declineTier3(User $user) : User
    {
        $user->setVerificationStatus(User::VERIFICATION_TIER3_DECLINED);
        $user->setTradingEnabled(false);

        $user = $this->userRepository->save($user);

        $this->notificationManager->create($user, NotificationInterface::TYPE_USER_TIER3_DECLINED, $user);
        $this->notificationManager->sendEmailNotification($user, NotificationInterface::TYPE_USER_TIER3_DECLINED, ['user' => $user, 'id' => $user->getId()]);

        return $this->resetTierVerification($user);
    }

    /**
     * @param User $user
     * @param int|null $status
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setVirtualWalletStatus(User $user, int $status = null) : User
    {
        if(!$user->isVirtualWalletStatusAllowed($status)) throw new AppException('Virtual wallet status not allowed');

        $user->setVirtualWalletStatus($status);
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @param int|null $status
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setVerificationStatus(User $user, int $status = null) : User
    {
        if(!$user->isVerificationStatusAllowed($status)) throw new AppException('Verification status not allowed');

        switch ($status){
            case User::VERIFICATION_TIER1_APPROVED:
                return $this->approveTier1($user);

                break;
            case User::VERIFICATION_TIER2_APPROVED:
                return $this->approveTier2($user);

                break;
            case User::VERIFICATION_TIER3_APPROVED:
                return $this->approveTier3($user);

                break;
            case User::VERIFICATION_TIER2_DECLINED:
                return $this->declineTier2($user);

                break;
            case User::VERIFICATION_TIER3_DECLINED:
                return $this->declineTier3($user);

                break;
            default:
                $user->setVerificationStatus($status);
                $user = $this->userRepository->save($user);

                break;
        }

//        try{
//            $this->mailerLiteManager->postUserToGroup($user, true);
//        }catch (\Exception $exception){
//            // do nothing
//        }

        return $user;
    }

    /**
     * @param User $user
     * @param string $locale
     * @return User
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateLocale(User $user, string $locale) : User
    {
        if(!($locale === 'en' || $locale === 'pl')) throw new AppException('Invalid locale');

        $user->setLocale($locale);

        return $this->userRepository->save($user);
    }

    /**
     * @param User $user
     * @param string $phone
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updatePhone(User $user, string $phone) : User
    {
        $user->setPhone($phone);
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @param \DateTime $time
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateRecentOrderTime(User $user, \DateTime $time) : User
    {
        $user->setRecentOrderAt($time);
        $user = $this->userRepository->save($user);

        return $user;
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }
}
