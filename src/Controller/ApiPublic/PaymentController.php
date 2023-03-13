<?php

namespace App\Controller\ApiPublic;

use App\DataTransformer\PaymentCardTransformer;
use App\Entity\Payment\PaywallTransactionInterface;
use App\Entity\PaymentCallback;
use App\Entity\PaymentCard;
use App\Entity\PaymentCardRegistration;
use App\Entity\CheckoutOrder;
use App\Exception\AppException;
use App\Manager\Payment\PaywallManager;
use App\Manager\Payment\Przelewy24Manager;
use App\Manager\PaymentCallbackManager;
use App\Manager\PaymentCardManager;
use App\Manager\CheckoutOrderManager;
use App\Repository\PaymentCardRegistrationRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends FOSRestController
{
    /** @var PaymentCallbackManager */
    private $paymentCallbackManager;

    /**
     * PaymentController constructor.
     * @param PaymentCallbackManager $paymentCallbackManager
     */
    public function __construct(PaymentCallbackManager $paymentCallbackManager)
    {
        $this->paymentCallbackManager = $paymentCallbackManager;
    }

    /**
     * @Rest\Post("/payment/przelewy24-callback", options={"expose"=true})
     *
     * @param Request $request
     * @param Przelewy24Manager $przelewy24Manager
     * @param CheckoutOrderManager $checkoutOrderManager
     * @return Response
     */
    public function postPrzelewy24Callback(Request $request, Przelewy24Manager $przelewy24Manager, CheckoutOrderManager $checkoutOrderManager) : Response
    {
        try{
            $data = $request->request->all();
            if(empty($data)) throw new AppException('Empty data');

            try{
                $this->paymentCallbackManager->create(PaymentCallback::TYPE_PRZELEWY_24, $data);
            }catch (\Exception $exception){
                // do nothing - TODO or send SMS/E-mail?
            }

            $przelewy24Manager->verifySign($data);
            $przelewy24Manager->verifyTransaction($data);

            /** @var CheckoutOrder $checkoutOrder */
            $checkoutOrder = $checkoutOrderManager->load($data['sessionId']);
            if($checkoutOrder->getStatus() !== CheckoutOrder::STATUS_PAYMENT_INIT) throw new AppException('Status does not allow to finish the payment.');
            if($checkoutOrder->isExpired()) {
                // TODO reject the order - write a command

                $checkoutOrderManager->reject($checkoutOrder);
                throw new AppException('Order is expired');
            }

            $checkoutOrderManager->setPaid($checkoutOrder);
            // TODO

            return new Response('OK');
        }catch (\Exception $exception){
            return new Response('ERROR');
        }
    }

    /**
     * @Rest\Post("/payment/paywall-transaction-callback", options={"expose"=true})
     *
     * @param Request $request
     * @param PaywallManager $paywallManager
     * @param CheckoutOrderManager $checkoutOrderManager
     * @return Response
     * @throws AppException
     */
    public function postPaywallTransactionCallback(Request $request, PaywallManager $paywallManager, CheckoutOrderManager $checkoutOrderManager)
    {
        try{
            $data = $request->request->all();
            if(empty($data)) throw new AppException('Empty data');

            $signature = (string) $request->headers->get('Signature');
            if(empty($signature)) throw new AppException('Empty signature');

            try{
                $this->paymentCallbackManager->create(PaymentCallback::TYPE_PAYWALL_TRANSACTION, $data, $signature);
            }catch (\Exception $exception){
                // do nothing - TODO or send SMS/E-mail?
            }

//            $przelewy24Manager->verifySign($data);
//            $przelewy24Manager->verifyTransaction($data);

            // TODO verify the signature

            /** @var CheckoutOrder $checkoutOrder */
            $checkoutOrder = $checkoutOrderManager->load($data['transactionId']);
            if($checkoutOrder->getStatus() !== CheckoutOrder::STATUS_PAYMENT_INIT) throw new AppException('Status does not allow to finish the payment.');
            if($checkoutOrder->isExpired()) {
                // TODO reject the order - write a command

                $checkoutOrderManager->reject($checkoutOrder);
                return new Response('OK');
            }

            switch ($data['status']){
                case PaywallTransactionInterface::STATUS_NEW:
                    // do nothing
                    break;
                case PaywallTransactionInterface::STATUS_PENDING:
                    // do nothing
                    break;
                case PaywallTransactionInterface::STATUS_WAITING_ON_3DS_CONFIRMATION:
                    // TODO set Checkout order status as PAYMENT PROCESSING???
                    break;
                case PaywallTransactionInterface::STATUS_SETTLED:
                    $checkoutOrderManager->setPaid($checkoutOrder);
                    // TODO przypisać wartości jakie zosatły real paid otrzymane z procesora, jako osobną kolumna

                    break;
                case PaywallTransactionInterface::STATUS_REJECTED:
                    // TODO reject the order - write a command

                    $checkoutOrderManager->reject($checkoutOrder);

                    break;
                case PaywallTransactionInterface::STATUS_ERROR:
                    // do nothing
                    break;
                default:
                    throw new AppException('Invalid Paywall status');
            }

            return new Response('OK');
        }catch (\Exception $exception){
            throw new AppException('ERROR');
        }
    }

    /**
     * @Rest\Post("/payment/paywall-register-callback", options={"expose"=true})
     *
     * @param Request $request
     * @param PaywallManager $paywallManager
     * @param PaymentCardTransformer $paymentCardTransformer
     * @param PaymentCardManager $paymentCardManager
     * @return Response
     * @throws AppException
     */
    public function postPaywallRegisterCallback(Request $request, PaywallManager $paywallManager, PaymentCardTransformer $paymentCardTransformer, PaymentCardManager $paymentCardManager) : Response
    {
        try{
            $data = $request->request->all();
            if(empty($data)) throw new AppException('Empty data');

            $signature = (string) $request->headers->get('Signature');
            if(empty($signature)) throw new AppException('Empty signature');

            try{
                $this->paymentCallbackManager->create(PaymentCallback::TYPE_PAYWALL_CARD, $data, $signature);
            }catch (\Exception $exception){
                // do nothing - TODO or send SMS/E-mail?
            }

//            $przelewy24Manager->verifySign($data);
//            $przelewy24Manager->verifyTransaction($data);

            // TODO verify the signature

            /** @var PaymentCardRegistration $paymentCardRegistration */
            $paymentCardRegistration = $paymentCardManager->loadRegistration($data['registrationId']);
            if($paymentCardRegistration->getStatus() === PaymentCardRegistration::STATUS_VERIFIED) return new Response('OK');
            if($paymentCardRegistration->getStatus() === PaymentCardRegistration::STATUS_REJECTED) return new Response('OK');
            if($data['status'] === PaymentCardRegistration::STATUS_VERIFIED){
                if($paymentCardRegistration->getUser()->getUuid() !== $data['userId']) throw new AppException('Invalid user ID');

                /** @var PaymentCard $paymentCard */
                $paymentCard = $paymentCardTransformer->transformFromPaywall($paymentCardRegistration->getUser(), $data);
                $paymentCardTransformer->validate($paymentCard);

                $paymentCard = $paymentCardManager->saveCard($paymentCard);
                if($paymentCard instanceof PaymentCard){
                    $paymentCardRegistration->setStatus(PaymentCardRegistration::STATUS_VERIFIED);
                    $paymentCardManager->updateRegistration($paymentCardRegistration);
                }
            } elseif ($data['status'] === PaymentCardRegistration::STATUS_REJECTED){
                $paymentCardRegistration->setStatus(PaymentCardRegistration::STATUS_REJECTED);
                $paymentCardManager->updateRegistration($paymentCardRegistration);
            } elseif ($data['status'] === PaymentCardRegistration::STATUS_PENDING){
                if(isset($data['card'])){
                    if(isset($data['card']['bin'])){
                        $paymentCardRegistration->setFirst6Digits($data['card']['bin']);
                    }
                    if(isset($data['card']['last4'])){
                        $paymentCardRegistration->setLast4Digits($data['card']['last4']);
                    }

                    $paymentCardManager->updateRegistration($paymentCardRegistration);
                }
            } else {
                return new Response('OK');
            }

            return new Response('OK');
        }catch (\Exception $exception){
            throw new AppException('ERROR');
        }
    }
}
