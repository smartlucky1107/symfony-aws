<?php

namespace App\DataTransformer;

use App\Entity\CurrencyPair;
use App\Entity\PaymentCard;
use App\Entity\PaymentProcessor;
use App\Entity\CheckoutOrder;
use App\Entity\User;
use App\Exception\AppException;
use App\Repository\CurrencyPairRepository;
use App\Repository\PaymentCardRepository;
use App\Repository\PaymentProcessorRepository;
use App\Resolver\InstantPriceResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CheckoutOrderTransformer extends AppTransformer
{
    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var PaymentProcessorRepository */
    private $paymentProcessorRepository;

    /** @var InstantPriceResolver */
    private $instantPriceResolver;

    /** @var PaymentCardRepository */
    private $paymentCardRepository;

    /**
     * CheckoutOrderTransformer constructor.
     * @param CurrencyPairRepository $currencyPairRepository
     * @param PaymentProcessorRepository $paymentProcessorRepository
     * @param InstantPriceResolver $instantPriceResolver
     * @param PaymentCardRepository $paymentCardRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(CurrencyPairRepository $currencyPairRepository, PaymentProcessorRepository $paymentProcessorRepository, InstantPriceResolver $instantPriceResolver, PaymentCardRepository $paymentCardRepository, ValidatorInterface $validator)
    {
        $this->currencyPairRepository = $currencyPairRepository;
        $this->paymentProcessorRepository = $paymentProcessorRepository;
        $this->instantPriceResolver = $instantPriceResolver;
        $this->paymentCardRepository = $paymentCardRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param User $user
     * @param Request $request
     * @return CheckoutOrder
     * @throws AppException
     * @throws \Exception
     */
    public function transform(User $user, Request $request) : CheckoutOrder
    {
        if(!$request->request->has('type')) throw new AppException('Type is required');
        if(!$request->request->has('amount')) throw new AppException('Amount is required');
        if(!$request->request->has('currencyPair')) throw new AppException('Currency pair is required');
        if(!$request->request->has('paymentProcessor')) throw new AppException('Payment processor is required');

        $type = (int) $request->request->get('type');

        $amount = (string) $request->request->get('amount', '');
        if(empty($amount)) throw new AppException('Amount is required');

        $currencyPairShortName = (string) $request->request->get('currencyPair', '');
        if(empty($currencyPairShortName)) throw new AppException('Currency pair is required');
        $currencyPairShortName = strtoupper($currencyPairShortName);

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findByShortName($currencyPairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency not found');

        $instantPrice = $this->instantPriceResolver->resolveSell($currencyPair, $amount);
        if(is_null($instantPrice)) throw new AppException('Price cannot be calculated');

        $paymentProcessorId = $request->request->get('paymentProcessor');
        /** @var PaymentProcessor $paymentProcessor */
        $paymentProcessor = $this->paymentProcessorRepository->findOneBy(['id' => $paymentProcessorId, 'enabled' => true]);
        if(!($paymentProcessor instanceof PaymentProcessor)) throw new AppException('Payment processor is required');

        /** @var CheckoutOrder $checkoutOrder */
        $checkoutOrder = new CheckoutOrder($user, $currencyPair, $type, $amount, $instantPrice, $paymentProcessor->getFee());
        $checkoutOrder->setPaymentProcessor($paymentProcessor); // TODO should I add that info constructor?

        if(!$paymentProcessor->isValidPaymentAmount($checkoutOrder->getTotalPaymentValue())) throw new AppException('Payment amount is not valid for selected payment processor');

        if($paymentProcessor->isPaywallCardProcessor()){
            $cardId = $request->request->get('card');

            /** @var PaymentCard $paymentCard */
            $paymentCard = $this->paymentCardRepository->findOneBy(['id' => $cardId, 'user' => $user->getId(), 'enabled' => true]);
            if(!($paymentCard instanceof PaymentCard)) throw new AppException('Payment card is required');

            $checkoutOrder->setPaymentCard($paymentCard);
        }

        return $checkoutOrder;
    }
}
