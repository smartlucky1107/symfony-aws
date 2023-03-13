<?php

namespace App\Controller\Api;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\PaymentProcessor;
use App\Entity\CheckoutOrder;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\IndacoinManager;
use App\Model\PriceInterface;
use App\Model\RapidOrder\RapidMarketOrder;
use App\Model\RapidOrder\RapidOrderWallet;
use App\Model\RapidOrder\RapidPlannedOrder;
use App\Repository\CurrencyPairRepository;
use App\Repository\PaymentProcessorRepository;
use App\Resolver\InstantPriceResolver;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Model\RapidOrder\RapidOrder;

class PreOrderController extends FOSRestController
{
    /**
     * Prepare PreOrder data
     *
     * @Rest\Post("/pre-order", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="PreOrder data",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="type",           type="string", description="Type of the Order", example="1", enum={1,2}),
     *         @SWG\Property(property="amount",         type="string", description="Amount of the base currency", example="0.005"),
     *         @SWG\Property(property="currencyPair",   type="string", description="Short name of the currency pair, eg. BTC-PLN", example="BTC-PLN"),
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="PreOrder data",
     *     @Model(type=RapidOrder::class, groups={"output"})
     * )
     * @SWG\Tag(name="Pre order")
     *
     * @param Request $request
     * @param CurrencyPairRepository $currencyPairRepository
     * @param InstantPriceResolver $instantPriceResolver
     * @param PaymentProcessorRepository $paymentProcessorRepository
     * @param IndacoinManager $indacoinManager
     * @return View
     * @throws AppException
     */
    public function preparePreOrder(Request $request, CurrencyPairRepository $currencyPairRepository, InstantPriceResolver $instantPriceResolver, PaymentProcessorRepository $paymentProcessorRepository, IndacoinManager $indacoinManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();
//        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
//        if(!$user->isTier3Approved()) throw new AppException('User is not allowed for trading');

        if(!$request->request->has('type')) throw new AppException('Type is required');
        if(!$request->request->has('amount')) throw new AppException('Amount is required');
        if(!$request->request->has('currencyPair')) throw new AppException('Currency pair is required');

        $type = (int) $request->request->get('type');

        $amount = (string) $request->request->get('amount', '');
        if(empty($amount)) throw new AppException('Amount is required');

        $currencyPairShortName = (string) $request->request->get('currencyPair', '');
        if(empty($currencyPairShortName)) throw new AppException('Currency pair is required');
        $currencyPairShortName = strtoupper($currencyPairShortName);

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $currencyPairRepository->findByShortName($currencyPairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency not found');

        if($type === Order::TYPE_BUY){
            $instantPrice = $instantPriceResolver->resolveSell($currencyPair, $amount);
        }elseif($type === Order::TYPE_SELL){
            $instantPrice = $instantPriceResolver->resolveBuy($currencyPair, $amount);
        }else{
            throw new AppException('Type not allowed');
        }
        if(is_null($instantPrice)) throw new AppException('Price cannot be calculated');

        $totalPrice = CheckoutOrder::calculateTotalPrice($instantPrice, $amount);

        $rapidOrderWallet = null;
        /** @var Wallet $userWallet */
        foreach($user->getWallets() as $userWallet){
            if($type === Order::TYPE_BUY && $userWallet->getCurrency()->getId() === $currencyPair->getQuotedCurrency()->getId()){
                /** @var RapidOrderWallet $rapidOrderWallet */
                $rapidOrderWallet = new RapidOrderWallet($userWallet->freeAmount());
                break;
            }elseif($type === Order::TYPE_SELL && $userWallet->getCurrency()->getId() === $currencyPair->getBaseCurrency()->getId()){
                /** @var RapidOrderWallet $rapidOrderWallet */
                $rapidOrderWallet = new RapidOrderWallet($userWallet->freeAmount());
                break;
            }
        }

        if(!($rapidOrderWallet instanceof RapidOrderWallet)) throw new AppException('Cannot calculate market order');

        $rapidMarketOrder = null;
        if($currencyPair->isMarketOrderAllowed()){
            /** @var RapidMarketOrder $rapidMarketOrder */
            $rapidMarketOrder = new RapidMarketOrder($amount, $totalPrice);
        }

        /** @var RapidOrder $rapidOrder */
        $rapidOrder = new RapidOrder($type, $amount, $currencyPair, $rapidOrderWallet, $rapidMarketOrder);

        $paymentProcessors = $paymentProcessorRepository->findAll();
        if($paymentProcessors){
            /** @var PaymentProcessor $paymentProcessor */
            foreach($paymentProcessors as $paymentProcessor){
                if($paymentProcessor->getId() === 2){
                    if(!$currencyPair->isIndacoinAllowed()) continue;

                    $checkoutFee = 0;
                    $paymentProcessorFee = 0;
//                    $totalPriceInda = $indacoinManager->calculateTotalPrice($amount, $currencyPair);
                    $totalPriceInda = $totalPrice;

                    $totalPaymentValue      = CheckoutOrder::calculateTotalPayment($totalPriceInda, $checkoutFee, $paymentProcessorFee);
                }else{
                    $checkoutFee            = CheckoutOrder::calculateCheckoutFee($totalPrice);
                    $paymentProcessorFee    = CheckoutOrder::calculatePaymentProcessorFee($totalPrice, $checkoutFee, $paymentProcessor->getFee());
                    $totalPaymentValue      = CheckoutOrder::calculateTotalPayment($totalPrice, $checkoutFee, $paymentProcessorFee);
                }

                /** @var RapidPlannedOrder $rapidPlannedOrder */
                $rapidPlannedOrder = new RapidPlannedOrder($amount, $totalPrice, $paymentProcessor, $totalPaymentValue);
                $rapidOrder->addPlannedOrder($rapidPlannedOrder);
            }
        }

        return $this->view($rapidOrder, Response::HTTP_OK);
    }
}

