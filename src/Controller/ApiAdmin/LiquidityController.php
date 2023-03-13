<?php

namespace App\Controller\ApiAdmin;

use App\Entity\CurrencyPair;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\Liquidity\LiquidityReportsManager;
use App\Repository\CurrencyPairRepository;
use App\Repository\Liquidity\LiquidityTransactionRepository;
use App\Repository\UserRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class LiquidityController extends FOSRestController
{
    /**
     * @Rest\Get("/liquidity/transactions/{currencyPairId}", requirements={"currencyPairId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param Request $request
     * @param int $currencyPairId
     * @param CurrencyPairRepository $currencyPairRepository
     * @param LiquidityTransactionRepository $liquidityTransactionRepository
     * @return View
     * @throws \Exception
     */
    public function getLiquidityTransactions(Request $request, int $currencyPairId, CurrencyPairRepository $currencyPairRepository, LiquidityTransactionRepository $liquidityTransactionRepository) : View
    {
        return $this->view(['liquidityTransactions' => []], JsonResponse::HTTP_OK);

//        // TODO verification
//        //$this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_DEPOSIT);
//
//        /** @var CurrencyPair $currencyPair */
//        $currencyPair = $currencyPairRepository->find($currencyPairId);
//        if(!($currencyPair instanceof CurrencyPair)) throw new \Exception('Currency pair not found.');
//
//        $dateStart = new \DateTime($request->get('from'));
//        $dateEnd = new \DateTime($request->get('to'));
//
//        $result = [];
//        $liquidityTransactions = $liquidityTransactionRepository->findForCurrencyPairBetweenDates($currencyPair, $dateStart, $dateEnd);
//        if($liquidityTransactions){
//            $arr = [];
//
//            foreach ($liquidityTransactions as $key => $item) {
//                $arr[$item['orderId']][$key] = $item;
//            }
//            ksort($arr, SORT_NUMERIC);
//
//            $result = $arr;
//        }
//
//        return $this->view(['liquidityTransactions' => $result], JsonResponse::HTTP_OK);
    }
}
