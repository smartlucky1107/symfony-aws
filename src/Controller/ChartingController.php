<?php

namespace App\Controller;

use App\Document\OHLC;
use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Exception\AppException;
use App\Manager\Charting\OHLCManager;
use App\Repository\CurrencyPairRepository;
use App\Resolver\GrowthResolver;
use App\Resolver\PriceResolver;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ChartingController extends FOSRestController
{
    /**
     * @Rest\Get("/charting/currencies")
     *
     * @param CurrencyPairRepository $currencyPairRepository
     * @return View
     */
    public function getCurrenciesSegregated(CurrencyPairRepository $currencyPairRepository) : View
    {
        $result = [];
        $array = [];

        $currencyPairs = $currencyPairRepository->findBy(['visible' => true], ['sortIndex' => 'ASC']);
        if($currencyPairs){
            /** @var CurrencyPair $currencyPair */
            foreach($currencyPairs as $currencyPair){
                /** @var Currency $baseCurrency */
                $baseCurrency = $currencyPair->getBaseCurrency();

                $result[] = [
                    'baseShortName' => $baseCurrency->getShortName(),
                    'currencyPair'  => $currencyPair->serialize(),
                    'growth'        => $currencyPair->getGrowth24h(),
                    'price'         => $currencyPair->toPrecisionQuoted($currencyPair->getPrice())
                ];
            }

            foreach ($result as $item){
                $array[$item['baseShortName']][] = [
                    'currencyPair'  => $item['currencyPair'],
                    'growth'        => $item['growth'],
                    'price'         => $item['price']
                ];;
            }
        }

        return $this->view($array, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/charting/currency-pair/{pairShortName}/info")
     *
     * @param string $pairShortName
     * @param CurrencyPairRepository $currencyPairRepository
     * @return View
     * @throws \Exception
     */
    public function getCurrencyPairInfo(string $pairShortName, CurrencyPairRepository $currencyPairRepository) : View
    {
        /** @var Currency $currencyPair */
        $currencyPair = $currencyPairRepository->findByShortName($pairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found.');

        $result = [
            'currencyPair'  => $currencyPair->serialize(),
            'growth'        => $currencyPair->getGrowth24h(),
            'price'         => $currencyPair->toPrecisionQuoted($currencyPair->getPrice())
        ];
        return $this->view($result, JsonResponse::HTTP_OK);
    }
}
