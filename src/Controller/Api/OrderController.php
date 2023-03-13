<?php

namespace App\Controller\Api;

use App\DataTransformer\OrderTransformer;
use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Entity\Liquidity\ExternalOrder;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\ListFilter\OrderListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\OrderManager;
use App\Model\PriceInterface;
use App\Repository\CurrencyPairRepository;
use App\Repository\Liquidity\ExternalOrderRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\OrderBook\TradeRepository;
use App\Resolver\InstantPriceResolver;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class OrderController extends FOSRestController
{
    /**
     * Create new order
     *
     * @Rest\Post("/orders", options={"expose"=true})
     *
     * @param Request $request
     * @return View
     */
    public function postOrder(Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\OrderController:makeOrder', [
            'queryParameters'  => $request->request->all(),
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get detailed information about specified Order
     *
     * @Rest\Get("/orders/{orderId}", requirements={"orderId"="\d+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="orderId",        in="path",      type="string", description="The ID of the Order" )
     * @SWG\Response(
     *     response=200,
     *     description="Returns a Order for a given id",
     *     @Model(type=Order::class, groups={"output"})
     * )
     * @SWG\Tag(name="Order")
     *
     * @param int $orderId
     * @param OrderManager $orderManager
     * @return View
     * @throws AppException
     */
    public function getOrder(int $orderId, OrderManager $orderManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');

        /** @var Order $order */
        $order = $orderManager->load($orderId);
        if(!$order->isUserAllowed($user)) throw new AppException('Order not found');
        // TODO change that into voters

        return $this->view(['order' => $order->serializeBasic()], Response::HTTP_OK);
    }

    /**
     * Get trades information about specified Order
     *
     * @Rest\Get("/orders/{orderId}/trades", requirements={"orderId"="\d+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="orderId",        in="path",      type="string", description="The ID of the Order" )
     * @SWG\Response(
     *     response=200,
     *     description="Returns a Order for a given id",
     *     @Model(type=Order::class, groups={"output"})
     * )
     * @SWG\Tag(name="Order")
     *
     * @param int $orderId
     * @param OrderManager $orderManager
     * @param TradeRepository $tradeRepository
     * @return View
     * @throws AppException
     */
    public function getOrderTrades(int $orderId, OrderManager $orderManager, TradeRepository $tradeRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');

        /** @var Order $order */
        $order = $orderManager->load($orderId);
        if(!$order->isUserAllowed($user)) throw new AppException('Order not found');

        $tradesResult = [
            'price' => null,
            'value' => null,
            'trades' => []
        ];

        $trades = $tradeRepository->findForOrderId($order->getId());
        if($trades ){
            $priceSum = 0;
            $valueSum = 0;

            /** @var Trade $trade */
            foreach ($trades as $trade){
                $priceSum = bcadd($priceSum, $trade->getPrice(), 2);
                $valueSum = bcadd($valueSum, $trade->getTotalValue(), $trade->getOrderSell()->getCurrencyPair()->getQuotedCurrency()->getRoundPrecision());

                $tradesResult['trades'][] = [
                    'amount'    => $trade->getAmount(),
                    'price'     => $trade->getPrice()
                ];

                $tradesResult['price'] = bcdiv($priceSum, count($trades), 2);
            }

            $tradesResult['value'] = $valueSum;
        }

        return $this->view($tradesResult, Response::HTTP_OK);
    }
}
