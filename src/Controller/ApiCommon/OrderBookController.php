<?php

namespace App\Controller\ApiCommon;

use App\DataTransformer\OrderTransformer;
use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\OrderBookManager;
use App\Manager\OrderManager;
use App\Model\OrderBook\OrderBookModel;
use App\Repository\CurrencyPairRepository;
use App\Resolver\PriceResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderBookController extends AbstractController
{
    /**
     * @param $currencyPairShortName
     * @param CurrencyPairRepository $currencyPairRepository
     * @param PriceResolver $priceResolver
     * @return JsonResponse
     */
    public function getPrice($currencyPairShortName, CurrencyPairRepository $currencyPairRepository, PriceResolver $priceResolver) : JsonResponse
    {
        try{
            $currencyPair = $currencyPairRepository->findByShortName($currencyPairShortName);
            if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found.');

            return new JsonResponse(['price' => $priceResolver->resolve($currencyPair)], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['message' => $exception->getMessage()],Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param $currencyPairShortName
     * @param OrderBookManager $orderBookManager
     * @return JsonResponse
     */
    public function getOrderBook($currencyPairShortName, OrderBookManager $orderBookManager) : JsonResponse
    {
        try{
            /** @var CurrencyPairRepository $currencyPairRepository */
            $currencyPairRepository = $this->getDoctrine()->getRepository(CurrencyPair::class);

            $currencyPair = $currencyPairRepository->findByShortName($currencyPairShortName);
            if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found.');

            /** @var OrderBookModel $orderBook */
            $orderBook = $orderBookManager->generateOrderBook($currencyPair);

            return new JsonResponse(['orderbook' => $orderBook, 'growth' => $currencyPair->getGrowth24h()], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['message' => $exception->getMessage()],Response::HTTP_BAD_REQUEST);
        }
    }
}
