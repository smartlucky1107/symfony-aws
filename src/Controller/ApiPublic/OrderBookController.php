<?php

namespace App\Controller\ApiPublic;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderBookController extends FOSRestController
{
    /**
     * @Rest\Get("/order-book/price/{currencyPairShortName}")
     *
     * @param $currencyPairShortName
     * @return JsonResponse
     */
    public function getPrice($currencyPairShortName) : JsonResponse
    {
        /** @var JsonResponse $response */
        $response = $this->forward('App\Controller\ApiCommon\OrderBookController:getPrice', [
            'currencyPairShortName'  => $currencyPairShortName,
        ]);

        return $response;
    }

    /**
     * @Rest\Get("/order-book/{currencyPairShortName}")
     *
     * @param string $currencyPairShortName
     * @return JsonResponse
     */
    public function getOrderBook($currencyPairShortName) : JsonResponse
    {
        /** @var JsonResponse $response */
        $response = $this->forward('App\Controller\ApiCommon\OrderBookController:getOrderBook', [
            'currencyPairShortName'  => $currencyPairShortName,
        ]);

        return $response;
    }
}
