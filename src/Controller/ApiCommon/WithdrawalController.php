<?php

namespace App\Controller\ApiCommon;

use App\Entity\User;
use App\Entity\Wallet\Withdrawal;
use App\Manager\WithdrawalManager;
use App\Repository\Wallet\WithdrawalRepository;
use App\Security\VoterRoleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exception\AppException;

class WithdrawalController extends AbstractController
{
    /**
     * @param int $withdrawalId
     * @param User $user
     * @param WithdrawalManager $withdrawalManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putWithdrawalApprove(int $withdrawalId, User $user, WithdrawalManager $withdrawalManager) : JsonResponse
    {
        /** @var Withdrawal $withdrawal */
        $withdrawal = $withdrawalManager->load($withdrawalId);
        $withdrawal = $withdrawalManager->setApprovedBy($withdrawal, $user);

        $withdrawalManager->approve($withdrawal);

//        $withdrawalManager->pushForWithdrawalApproveRequest($withdrawal);

        return new JsonResponse(['approved' => true], Response::HTTP_OK);
    }

    /**
     * @param int $withdrawalId
     * @param WithdrawalManager $withdrawalManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putWithdrawalDecline(int $withdrawalId, WithdrawalManager $withdrawalManager) : JsonResponse
    {
        /** @var Withdrawal $withdrawal */
        $withdrawal = $withdrawalManager->load($withdrawalId);
        $withdrawalManager->decline($withdrawal);

        return new JsonResponse(['declined' => true], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param WithdrawalRepository $withdrawalRepository
     * @return JsonResponse
     */
    public function getWithdrawalsForExternalApproval(Request $request, WithdrawalRepository $withdrawalRepository) : JsonResponse
    {
        $withdrawalsSerialized = [];

        $withdrawals = $withdrawalRepository->findBy(['status' => Withdrawal::STATUS_EXTERNAL_APPROVAL]);
        if($withdrawals && count($withdrawals) > 0){
            /** @var Withdrawal $withdrawal */
            foreach($withdrawals as $withdrawal){
                $withdrawalsSerialized[] = $withdrawal->serializeForTransferApp();
            }
        }


        return new JsonResponse(['withdrawals' => $withdrawalsSerialized], Response::HTTP_OK);
    }
}
