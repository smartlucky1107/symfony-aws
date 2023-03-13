<?php

namespace App\Controller\ApiCommon;

use App\DataTransformer\OrderTransformer;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\OrderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
    /**
     * @param $queryParameters
     * @param OrderTransformer $orderTransformer
     * @param OrderManager $orderManager
     * @return JsonResponse
     * @throws \Exception
     * @throws AppException
     */
    public function makeOrder($queryParameters, OrderTransformer $orderTransformer, OrderManager $orderManager) : JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed for trading');

        /** @var Order $order */
        $order = $orderTransformer->transform($this->getUser() , $queryParameters);
        $orderTransformer->validate($order);

        try{
            $orderManager->placeOrder($order);
        }catch (AppException $appException){
            throw new AppException($appException->getMessage());
        }catch (\Exception $exception){
            throw new AppException('Error occurred');
        }

        return new JsonResponse(['order' => $order->serializeBasic()], Response::HTTP_CREATED);
    }

    /**
     * @param string $orderId
     * @param OrderManager $orderManager
     * @return JsonResponse
     * @throws \Exception
     */
    public function getOrder($orderId, OrderManager $orderManager) : JsonResponse
    {
        /** @var Order $order */
        $order = $orderManager->load($orderId);

        // resolve access
        $this->denyAccessUnlessGranted('view', $order);

        return new JsonResponse(['order' => $order->serializeBasic()], Response::HTTP_OK);
    }
}
