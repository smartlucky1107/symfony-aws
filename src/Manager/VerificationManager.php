<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Verification;
use App\Exception\AppException;
use App\Repository\VerificationRepository;

class VerificationManager
{
    /** @var VerificationRepository */
    private $verificationRepository;

    /**
     * VerificationManager constructor.
     * @param VerificationRepository $verificationRepository
     */
    public function __construct(VerificationRepository $verificationRepository)
    {
        $this->verificationRepository = $verificationRepository;
    }

    /**
     * @return VerificationRepository
     */
    public function getVerificationRepository(): VerificationRepository
    {
        return $this->verificationRepository;
    }

    /**
     * @param User $user
     * @return Verification
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function newVerification(User $user) : Verification
    {
        $verification = new Verification($user);

        return $this->verificationRepository->save($verification);
    }

    /**
     * @param Verification $verification
     * @param string $transactionReference
     * @param string $redirectUrl
     * @return Verification
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateWithJumioInitiate(Verification $verification, string $transactionReference, string $redirectUrl) : Verification
    {
        $verification->setTransactionReference($transactionReference);
        $verification->setRedirectUrl($redirectUrl);

        return $this->verificationRepository->save($verification);
    }

    /**
     * @param Verification $verification
     * @param string $scanRef
     * @param string $redirectUrl
     * @return Verification
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateWithIdenfyInitiate(Verification $verification, string $scanRef, string $redirectUrl) : Verification
    {
        $verification->setTransactionReference($scanRef);
        $verification->setRedirectUrl($redirectUrl);

        return $this->verificationRepository->save($verification);
    }

    /**
     * @param Verification $verification
     * @param int $status
     * @return Verification
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateStatus(Verification $verification, int $status) : Verification
    {
        if(!$verification->isStatusAllowed($status)) throw new AppException('Status now allowed');
        if($verification->getStatus() !== Verification::STATUS_NEW)  throw new AppException('Status cannot be changed');

        $verification->setStatus($status);

        return $this->verificationRepository->save($verification);
    }

    /**
     * @param Verification $verification
     * @return Verification
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateExpiredStatus(Verification $verification) : Verification
    {
        if($verification->isExpired() && $verification->getStatus() === Verification::STATUS_NEW){
            $verification = $this->updateStatus($verification, Verification::STATUS_EXPIRED);
        }

        return $verification;
    }
}
