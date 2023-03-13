<?php

namespace App\DataTransformer;

use App\Entity\PaymentCard;
use App\Entity\PaymentCardRegistration;
use App\Entity\User;
use App\Exception\AppException;
use App\Repository\PaymentProcessorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PaymentCardTransformer extends AppTransformer
{
    /** @var PaymentProcessorRepository */
    private $paymentProcessorRepository;

    /**
     * PaymentCardTransformer constructor.
     * @param PaymentProcessorRepository $paymentProcessorRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(PaymentProcessorRepository $paymentProcessorRepository, ValidatorInterface $validator)
    {
        $this->paymentProcessorRepository = $paymentProcessorRepository;

        parent::__construct($validator);
    }

    /**
     * Transform array parameters to new object
     *
     * @param User $user
     * @param $data
     * @return PaymentCard
     * @throws AppException
     * @throws \Exception
     */
    public function transformFromPaywall(User $user, $data) : PaymentCard
    {
//        if(!$request->request->has('type')) throw new AppException('Type is required');
//        if(!$request->request->has('amount')) throw new AppException('Amount is required');
//        if(!$request->request->has('currencyPair')) throw new AppException('Currency pair is required');
//        if(!$request->request->has('paymentProcessor')) throw new AppException('Payment processor is required');

        if($data['status'] !== PaymentCardRegistration::STATUS_VERIFIED) throw new AppException('Registration status should be verified');

        $cardData = $data['card'];

        /** @var PaymentCard $paymentCard */
        $paymentCard = new PaymentCard($user, $data['cardId']);
        $paymentCard->setFirst6Digits($cardData['bin']);
        $paymentCard->setLast4Digits($cardData['last4']);
        $paymentCard->setExpirationDate($cardData['expirationDate']);

        // Card user info
        $paymentCard->setUserFirstName($cardData['userDetails']['firstName']);
        $paymentCard->setUserLastName($cardData['userDetails']['lastName']);

        // Card BIN info
        $paymentCard->setBinBank($cardData['binDetails']['bank']);
        $paymentCard->setBinCard($cardData['binDetails']['card']);
        $paymentCard->setBinType($cardData['binDetails']['type']);

        return $paymentCard;
    }
}
