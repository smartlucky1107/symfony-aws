<?php

namespace App\Manager;

use App\Entity\PaymentCard;
use App\Entity\PaymentCardRegistration;
use App\Entity\User;
use App\Exception\AppException;
use App\Repository\PaymentCardRegistrationRepository;
use App\Repository\PaymentCardRepository;

class PaymentCardManager
{
    /** @var PaymentCardRepository */
    private $paymentCardRepository;

    /** @var PaymentCardRegistrationRepository */
    private $paymentCardRegistrationRepository;

    /**
     * PaymentCardManager constructor.
     * @param PaymentCardRepository $paymentCardRepository
     * @param PaymentCardRegistrationRepository $paymentCardRegistrationRepository
     */
    public function __construct(PaymentCardRepository $paymentCardRepository, PaymentCardRegistrationRepository $paymentCardRegistrationRepository)
    {
        $this->paymentCardRepository = $paymentCardRepository;
        $this->paymentCardRegistrationRepository = $paymentCardRegistrationRepository;
    }

    /**
     * @param string $registrationId
     * @return PaymentCardRegistration
     * @throws AppException
     */
    public function loadRegistration(string $registrationId) : PaymentCardRegistration
    {
        /** @var PaymentCardRegistration $paymentCardRegistration */
        $paymentCardRegistration = $this->paymentCardRegistrationRepository->findOneBy(['registrationId' => $registrationId]);
        if(!($paymentCardRegistration instanceof PaymentCardRegistration)) throw new AppException('error.payment_card_registration.not_found');

        return $paymentCardRegistration;
    }

    /**
     * @param string $cardId
     * @return PaymentCard
     * @throws AppException
     */
    public function loadCard(string $cardId) : PaymentCard
    {
        /** @var PaymentCard $paymentCard */
        $paymentCard = $this->paymentCardRepository->findOneBy(['id' => $cardId]);
        if(!($paymentCard instanceof PaymentCard)) throw new AppException('error.payment_card.not_found');

        return $paymentCard;
    }

    /**
     * @param PaymentCard $paymentCard
     * @return PaymentCard
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableCard(PaymentCard $paymentCard) : PaymentCard
    {
        $paymentCard->setEnabled(false);

        return $this->saveCard($paymentCard);
    }

    /**
     * @param PaymentCard $paymentCard
     * @return PaymentCard
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enableCard(PaymentCard $paymentCard) : PaymentCard
    {
        $paymentCard->setEnabled(true);

        return $this->saveCard($paymentCard);
    }

    /**
     * @param User $user
     * @param string $registrationId
     * @return PaymentCardRegistration
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createRegistration(User $user, string $registrationId) : PaymentCardRegistration
    {
        /** @var PaymentCardRegistration $paymentCardRegistration */
        $paymentCardRegistration = new PaymentCardRegistration($user, $registrationId);

        return $this->paymentCardRegistrationRepository->save($paymentCardRegistration);
    }

    /**
     * @param PaymentCardRegistration $paymentCardRegistration
     * @return PaymentCardRegistration
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateRegistration(PaymentCardRegistration $paymentCardRegistration) : PaymentCardRegistration
    {
        return $this->paymentCardRegistrationRepository->save($paymentCardRegistration);
    }

    /**
     * @param PaymentCard $paymentCard
     * @return PaymentCard
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveCard(PaymentCard $paymentCard) : PaymentCard
    {
        return $this->paymentCardRepository->save($paymentCard);
    }
}
