<?php

namespace App\Controller\ApiPublic;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Repository\CurrencyPairRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Resolver\InstantAmountResolver;
use App\Resolver\InstantPriceResolver;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class OrderController extends FOSRestController
{
    /**
     * Generate instant price calculation for order params
     *
     * @Rest\Post("/orders/instant-price")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Parameters for instant price calculation",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"pairShortName", "type", "amount"},
     *         @SWG\Property(property="pairShortName",  type="string",  description="Short name of the Currency Pair", example="BTC-PLN"),
     *         @SWG\Property(property="type",           type="integer", description="Type of the Order 1 BUT, 2 SELL", enum={1,2}),
     *         @SWG\Property(property="amount",         type="string",  description="Amount of the base currency", example="0.0005")
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns instant price details",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="pairShortName",  type="string",  description="Short name of the Currency Pair", example="BTC-PLN"),
     *         @SWG\Property(property="type",           type="integer", description="Type of the Order 1 BUT, 2 SELL", enum={1,2}),
     *         @SWG\Property(property="amount",         type="string",  description="Amount of the base currency", example="0.0005"),
     *         @SWG\Property(property="limitPrice",     type="string",  description="Limit price of The Order", example="45000")
     *     )
     * )
     * @SWG\Tag(name="Order")
     *
     * @param Request $request
     * @param CurrencyPairRepository $currencyPairRepository
     * @param InstantPriceResolver $instantPriceResolver
     * @return View
     * @throws AppException
     */
    public function postOrderInstantPrice(Request $request, CurrencyPairRepository $currencyPairRepository, InstantPriceResolver $instantPriceResolver) : View
    {
        $amount = $request->get('amount');
        $type = (int) $request->get('type');
        $pairShortName = (string) $request->get('pairShortName');
        if(!$pairShortName) throw new AppException('pairShortName cannot be empty');
        if($amount === 0) throw new AppException('amount must be greater than 0');

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $currencyPairRepository->findByShortName($pairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found.');

        if($type === Order::TYPE_BUY){
            $limitPrice = $instantPriceResolver->resolveSell($currencyPair, $amount);

//            $limitPrice = $orderRepository->findLiquidityLimitPrice($currencyPair,Order::TYPE_SELL, $amount);
//
//            if($currencyPair->isExternalLiquidityEnabled()){
//                $externalLimitPrice = $externalOrderRepository->findLiquidityLimitPrice($currencyPair, ExternalOrder::TYPE_SELL, $amount);
//
//
//                $comp = bccomp($externalLimitPrice, $limitPrice, PriceInterface::BC_SCALE);
//                if($comp === -1){
//                    $limitPrice = $externalLimitPrice;
//                }
//
//                if(is_null($limitPrice)) $limitPrice = $externalLimitPrice;
//            }
        }elseif($type === Order::TYPE_SELL){
            $limitPrice = $instantPriceResolver->resolveBuy($currencyPair, $amount);

//            $limitPrice = $orderRepository->findLiquidityLimitPrice($currencyPair,Order::TYPE_BUY, $amount);
//
//            if($currencyPair->isExternalLiquidityEnabled()){
//                $externalLimitPrice = $externalOrderRepository->findLiquidityLimitPrice($currencyPair, ExternalOrder::TYPE_BUY, $amount);
//
//                $comp = bccomp($externalLimitPrice, $limitPrice, PriceInterface::BC_SCALE);
//                if($comp === 1){
//                    $limitPrice = $externalLimitPrice;
//                }
//
//                if(is_null($limitPrice)) $limitPrice = $externalLimitPrice;
//            }
        }else{
            throw new AppException('Type not allowed');
        }

        $totalPrice = $limitPrice ? $currencyPair->toPrecisionQuoted(bcmul($limitPrice, $amount, PriceInterface::BC_SCALE)) : null;

        $result = [
            'pairShortName' => $pairShortName,
            'type'          => $type,
            'amount'        => $currencyPair->toPrecision($amount),
            'limitPrice'    => $limitPrice ? $currencyPair->toPrecisionQuoted($limitPrice) : null,
            'price'         => $totalPrice
        ];

        return $this->view($result, JsonResponse::HTTP_OK);
    }

    /**
     * Generate instant amount calculation for order params
     *
     * @Rest\Post("/orders/instant-amount")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Parameters for instant amount calculation",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"pairShortName", "type", "amount"},
     *         @SWG\Property(property="pairShortName",  type="string",  description="Short name of the Currency Pair", example="BTC-PLN"),
     *         @SWG\Property(property="type",           type="integer", description="Type of the Order 1 BUT, 2 SELL", enum={1,2}),
     *         @SWG\Property(property="totalPrice",     type="string",  description="Specified total price of the currency pair", example="45000")
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns instant amount details",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="pairShortName",  type="string",  description="Short name of the Currency Pair", example="BTC-PLN"),
     *         @SWG\Property(property="type",           type="integer", description="Type of the Order 1 BUT, 2 SELL", enum={1,2}),
     *         @SWG\Property(property="amount",         type="string",  description="Amount of the base currency", example="0.0005"),
     *         @SWG\Property(property="totalPrice",     type="string",  description="Total price of The Order", example="45000")
     *     )
     * )
     * @SWG\Tag(name="Order")
     *
     * @param Request $request
     * @param CurrencyPairRepository $currencyPairRepository
     * @param InstantAmountResolver $instantAmountResolver
     * @return View
     * @throws AppException
     */
    public function postOrderInstantAmount(Request $request, CurrencyPairRepository $currencyPairRepository, InstantAmountResolver $instantAmountResolver) : View
    {
        $totalPrice = (float) $request->get('totalPrice');
        $type = (int) $request->get('type');
        $pairShortName = (string) $request->get('pairShortName');
        if(!$pairShortName) throw new AppException('pairShortName cannot be empty');
        if($totalPrice === 0) throw new AppException('Price must be greater than 0');

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $currencyPairRepository->findByShortName($pairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found.');

        if($type === Order::TYPE_BUY){
            $amount = $instantAmountResolver->resolveSell($currencyPair, $totalPrice);
        }elseif($type === Order::TYPE_SELL){
            $amount = $instantAmountResolver->resolveBuy($currencyPair, $totalPrice);
        }else{
            throw new AppException('Type not allowed');
        }

        $result = [
            'pairShortName' => $pairShortName,
            'type'          => $type,
            'amount'        => $amount ? $currencyPair->toPrecision($amount) : null,
            'totalPrice'    => $totalPrice ? $currencyPair->toPrecisionQuoted($totalPrice) : null
        ];

        return $this->view($result, JsonResponse::HTTP_OK);
    }
}
