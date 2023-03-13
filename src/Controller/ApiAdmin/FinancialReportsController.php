<?php

namespace App\Controller\ApiAdmin;

use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\Liquidity\LiquidityReportsManager;
use App\Repository\UserRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class FinancialReportsController extends FOSRestController
{
    /**
     * @Rest\Get("/financial-reports/balances", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return View
     */
    public function getBalances(Request $request) : View
    {
        //$this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_BLOCKCHAIN);

        $response = $this->forward('App\Controller\ApiCommon\FinancialReportsController:getBalances', [
            'request' => $request
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/financial-reports/incoming-fees", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return View
     */
    public function getIncomingFees(Request $request) : View
    {
        //$this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_BLOCKCHAIN);

        $response = $this->forward('App\Controller\ApiCommon\FinancialReportsController:getIncomingFees', [
            'request' => $request
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/financial-reports/liquidity-balances", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param UserRepository $userRepository
     * @return View
     */
    public function getLiquidityBalances(UserRepository $userRepository) : View
    {
        /** @var User $bitbayLiqUser */
        $bitbayLiqUser = $userRepository->findBitbayLiquidityUser();

        /** @var User $binanceLiqUser */
        $binanceLiqUser = $userRepository->findBinanceLiquidityUser();

        $bitbayWallets = [];
        $binanceWallets = [];

        if($bitbayLiqUser instanceof User){
            $wallets = $bitbayLiqUser->getWallets();
            if($wallets){
                /** @var Wallet $wallet */
                foreach($wallets as $wallet){
                    $bitbayWallets[] = $wallet->serializeForPrivateApi();
                }
            }
        }

        if($binanceLiqUser instanceof User){
            $wallets = $binanceLiqUser->getWallets();
            if($wallets){
                /** @var Wallet $wallet */
                foreach($wallets as $wallet){
                    $binanceWallets[] = $wallet->serializeForPrivateApi();
                }
            }
        }

        $result = [
            'bitbay'    => $bitbayWallets,
            'binance'   => $binanceWallets
        ];

        return $this->view(['balances' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/financial-reports/liquidity/{liquidityType}", options={"expose"=true}, defaults={"liquidityType": "bitbay"})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param Request $request
     * @param string $liquidityType
     * @param LiquidityReportsManager $liquidityReportsManager
     * @param UserRepository $userRepository
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLiquidityReports(Request $request, string $liquidityType, LiquidityReportsManager $liquidityReportsManager, UserRepository $userRepository) : View
    {
        //$this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_DEPOSIT);

        $from = $request->query->has('from') ? new \DateTime($request->query->get('from')) : null;
        $to = $request->query->has('to') ? new \DateTime($request->query->get('to')) : null;

        if($liquidityType === 'bitbay'){
            /** @var User $liquidityUser */
            $liquidityUser = $userRepository->findBitbayLiquidityUser();
        }elseif($liquidityType === 'binance'){
            /** @var User $liquidityUser */
            $liquidityUser = $userRepository->findBinanceLiquidityUser();
        }else{
            throw new AppException('Liquidity type not allowed');
        }

//        /** @var User $liquidityUser */
//        $liquidityUser = $userRepository->find(2);
//
        $liquidityReportsManager->initLiquidityUser($liquidityUser);
        $result = $liquidityReportsManager->getReports($from, $to);

        return $this->view(['report' => $result], JsonResponse::HTTP_OK);
    }
}
