<?php

namespace App\Controller\ApiCommon;

use App\Document\Transfer;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Manager\TransferManager;
use App\Model\PriceInterface;
use App\Model\SystemUserInterface;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FinancialReportsController extends AbstractController
{
    /**
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function getBalances(Request $request, WalletRepository $walletRepository, UserRepository $userRepository) : JsonResponse
    {
        $walletType = (string) $request->get('type', null);

        $reportDate = $request->get('reportDate', null);

        $balances = [];

        if($reportDate){
            $reportDate = new \DateTime($reportDate);
            $reportDate->setTime(0, 0, 0);

            // TODO
        }else{
            $exceptUserIds = array_merge(SystemUserInterface::LIQ_USER, SystemUserInterface::FEE_USERS);

            $balances = $walletRepository->findBalancesGroupedByCurrency($walletType, $exceptUserIds);
        }

        return new JsonResponse(['balances' => $balances], JsonResponse::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param TransferManager $transferManager
     * @return JsonResponse
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getIncomingFees(Request $request, WalletRepository $walletRepository, TransferManager $transferManager) : JsonResponse
    {
        $from = new \DateTime($request->get('from', 'now'));
        $from->setTime(0,0,0);
        $to = new \DateTime($request->get('to', 'now'));
        $to->setTime(23, 59, 59);

        $result = [];

        $feeWallets = $walletRepository->getFeeWallets();
        if($feeWallets){
            /** @var Wallet $feeWallet */
            foreach ($feeWallets as $feeWallet){
//                $incomesArray = [];
                $incomesTotal = 0;

                $transfers = $transferManager->findIncomesByWalletId($feeWallet->getId(), $from, $to);
                if($transfers){
                    /** @var Transfer $transfer */
                    foreach($transfers as $transfer){
                        $incomesTotal = bcadd($incomesTotal, $transfer->getAmount(), PriceInterface::BC_SCALE);
//                        $incomesArray[] = [
//                            'type'      => $transfer->getTypeName(),
//                            'amount'    => $transfer->getAmount()
//                        ];
                    }
                }

                $result[] = [
                    'currency'  => $feeWallet->getCurrency()->getFullName(),
                    'incomesTotal' => $incomesTotal,
//                    'incomes'   => $incomesArray
                ];
            }
        }

        return new JsonResponse(['fees' => $result], JsonResponse::HTTP_OK);
    }
}
