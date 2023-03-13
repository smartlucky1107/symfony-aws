<?php

namespace App\Controller\ApiAdmin;

use App\Document\WalletBalance;
use App\Manager\WalletBalanceManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WalletBalanceController extends FOSRestController
{
    /**
     * @Rest\Get("/wallet-balance/by-wallet/{walletId}", options={"expose"=true})
     *
     * @param int $walletId
     * @param WalletBalanceManager $walletBalanceManager
     * @return View
     */
    public function getWalletBalancesByWallet(int $walletId, WalletBalanceManager $walletBalanceManager) : View
    {
        // TODO dodać ACL

        $walletBalancesSerialize = [];

        $walletBalances = $walletBalanceManager->findForWallet($walletId);
        if(count($walletBalances) > 0){
            /** @var WalletBalance $walletBalance */
            foreach ($walletBalances as $walletBalance){
                $walletBalancesSerialize[] = $walletBalance->serialize();
            }
        }

        return $this->view(['walletBalances' => $walletBalancesSerialize], JsonResponse::HTTP_OK);
    }
}
