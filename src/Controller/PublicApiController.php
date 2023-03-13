<?php

namespace App\Controller;

use App\Document\OHLC;
use App\Entity\Country;
use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Wallet;
use App\Repository\CountryRepository;
use App\Repository\CurrencyPairRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Resolver\GrowthResolver;
use App\Resolver\PriceResolver;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class PublicApiController extends FOSRestController
{
    /**
     * @Rest\Get("/public-api/countries", options={"expose"=true})
     *
     * @param Request $request
     * @param CountryRepository $countryRepository
     * @param TranslatorInterface $translator
     * @return View
     */
    public function getCountries(Request $request, CountryRepository $countryRepository, TranslatorInterface $translator) : View
    {
        $includeDisabled = (bool) $request->query->get('includeDisabled', false);

        $result = [];

        if($includeDisabled === true){
            $countries = $countryRepository->findAll();
        }else {
            $countries = $countryRepository->findBy(['disabled' => false]);
        }

        if($countries){
            /** @var Country $country */
            foreach($countries as $country){
                $countryItem = $country->serialize();
                if(isset($countryItem['name'])) $countryItem['name'] = $translator->trans($countryItem['name']);
                $result[] = $countryItem;
            }
        }

        return $this->view($result, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/public-api/homepage-data")
     *
     * @param CurrencyPairRepository $currencyPairRepository
     * @param OrderRepository $orderRepository
     * @return View
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHomepageData(CurrencyPairRepository $currencyPairRepository, OrderRepository $orderRepository) : View
    {
        $result = [
            'currencyPairs'     => [],
            'mostPopularPair'   => null,
            'topRise'           => null,
            'topRisePair'       => null,
            'topDrop'           => null,
            'topDropPair'       => null,
            'offers24h'         => null,
            'bids24h'           => null,
            'gainers'           => [],
            'losers'            => []
        ];

        $result['offers24h'] = $orderRepository->findOffers24h();
        $result['bids24h'] = $orderRepository->findBids24h();

        $topRise = null;
        $topDrop = null;

        /** @var CurrencyPair $topRisePair */
        $topRisePair = null;
        /** @var CurrencyPair $topDropPair */
        $topDropPair = null;

        $currencyPairs = $currencyPairRepository->findBy(['enabled' => true]);

        /** @var CurrencyPair $currencyPair */
        foreach($currencyPairs as $currencyPair){
            $growth = $currencyPair->getGrowth24h();

            // resolve top rise
            if($topRisePair === null){
                $topRise = $growth;
                $topRisePair = $currencyPair;
            }elseif($topRisePair instanceof CurrencyPair){
                if($growth > $topRise){
                    $topRise = $growth;
                    $topRisePair = $currencyPair;
                }
            }

            // resolve top drop
            if($topDropPair === null){
                $topDrop = $growth;
                $topDropPair = $currencyPair;
            }elseif($topDropPair instanceof CurrencyPair){
                if($growth < $topDrop){
                    $topDrop = $growth;
                    $topDropPair = $currencyPair;
                }
            }

            $result['currencyPairs'][] = [
                'id'            => $currencyPair->getId(),
                'pairShortName' => $currencyPair->pairShortName(),
                'growth'        => $growth,
                '1hPoints'      => $currencyPair->getPrice12Points(),
                'price'         => $currencyPair->toPrecisionQuoted($currencyPair->getPrice())
            ];
        }

        if($topRisePair instanceof CurrencyPair) {
            $result['topRise'] = $topRise;
            $result['topRisePair'] = $topRisePair->serialize();
        }
        if($topDropPair instanceof CurrencyPair) {
            $result['topDrop'] = $topDrop;
            $result['topDropPair'] = $topDropPair->serialize();
        }

        $pairOrders = $orderRepository->findPairOrders();

        if($pairOrders){
            $mostPopularItem = null;

            foreach($pairOrders as $item){
                if(!$mostPopularItem) {
                    $mostPopularItem = $item;
                }else{
                    if($item['orders'] > $mostPopularItem['orders']){
                        $mostPopularItem = $item;
                    }
                }
            }

            $result['mostPopularPair'] = $currencyPairRepository->find($mostPopularItem['currencyPairId'])->serialize();
        }

        $gainersArray = [];
        $gainers = $currencyPairRepository->findGainers();
        if($gainers){
            /** @var CurrencyPair $gainerPair */
            foreach($gainers as $gainerPair){
                $gainersArray[] = $gainerPair->serializeForPrivateApi();
            }
        }

        $losersArray = [];
        $losers = $currencyPairRepository->findLosers();
        if($losers){
            /** @var CurrencyPair $loserPair */
            foreach($losers as $loserPair){
                $losersArray[] = $loserPair->serializeForPrivateApi();
            }
        }

        $result['gainers'] = $gainersArray;
        $result['losers'] = $losersArray;

        return $this->view($result, JsonResponse::HTTP_OK);
    }
}
