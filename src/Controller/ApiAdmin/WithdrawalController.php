<?php

namespace App\Controller\ApiAdmin;

use App\Entity\Wallet\Withdrawal;
use App\Manager\ListFilter\WithdrawalListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\WithdrawalManager;
use App\Repository\Wallet\WithdrawalRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Exception\AppException;

class WithdrawalController extends FOSRestController
{
    /**
     * @Rest\Get("/withdrawals/{withdrawalId}", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param int $withdrawalId
     * @param WithdrawalManager $withdrawalManager
     * @return View
     * @throws AppException
     */
    public function getWithdrawal(int $withdrawalId, WithdrawalManager $withdrawalManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_WITHDRAWAL);

        $withdrawal = $withdrawalManager->load($withdrawalId);

        return $this->view(['withdrawal' => $withdrawal->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/withdrawals", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param WithdrawalRepository $withdrawalRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getWithdrawals(Request $request, WithdrawalRepository $withdrawalRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_WITHDRAWAL);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new WithdrawalListFilter($request), $withdrawalRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/withdrawals/{withdrawalId}/external-approval", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $withdrawalId
     * @param WithdrawalManager $withdrawalManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putWithdrawalSendForExternalApproval(int $withdrawalId, WithdrawalManager $withdrawalManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WITHDRAWAL);

        /** @var Withdrawal $withdrawal */
        $withdrawal = $withdrawalManager->load($withdrawalId);
        $withdrawalManager->sendForExternalApproval($withdrawal);

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/withdrawals/{withdrawalId}/reject", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $withdrawalId
     * @param WithdrawalManager $withdrawalManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putWithdrawalReject(int $withdrawalId, WithdrawalManager $withdrawalManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WITHDRAWAL);

        /** @var Withdrawal $withdrawal */
        $withdrawal = $withdrawalManager->load($withdrawalId);
        $withdrawalManager->reject($withdrawal);

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/withdrawals/{withdrawalId}/decline", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $withdrawalId
     * @return View
     */
    public function putWithdrawalDecline(int $withdrawalId){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WITHDRAWAL);

        $response = $this->forward('App\Controller\ApiCommon\WithdrawalController:putWithdrawalDecline', [
            'withdrawalId'  => $withdrawalId,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Put("/withdrawals/{withdrawalId}/approve", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $withdrawalId
     * @return View
     */
    public function putWithdrawalApprove(int $withdrawalId){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WITHDRAWAL);

        $response = $this->forward('App\Controller\ApiCommon\WithdrawalController:putWithdrawalApprove', [
            'withdrawalId'  => $withdrawalId,
            'user' => $this->getUser()
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }
}
