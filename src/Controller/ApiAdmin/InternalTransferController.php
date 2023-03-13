<?php

namespace App\Controller\ApiAdmin;

use App\Entity\Wallet\InternalTransfer;
use App\Manager\InternalTransferManager;
use App\Manager\ListFilter\InternalTransferListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\Wallet\InternalTransferRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Exception\AppException;

class InternalTransferController extends FOSRestController
{
    /**
     * @Rest\Get("/internal-transfers/{internalTransferId}", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return View
     * @throws AppException
     */
    public function getInternalTransfer(int $internalTransferId, InternalTransferManager $internalTransferManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_INTERNAL_TRANSFER);

        $internalTransfer = $internalTransferManager->load($internalTransferId);

        return $this->view(['internalTransfer' => $internalTransfer->serialize(true)], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/internal-transfers", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param InternalTransferRepository $internalTransferRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getInternalTransfers(Request $request, InternalTransferRepository $internalTransferRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_INTERNAL_TRANSFER);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new InternalTransferListFilter($request), $internalTransferRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/internal-transfers/{internalTransferId}/reject", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putInternalTransferReject(int $internalTransferId, InternalTransferManager $internalTransferManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_INTERNAL_TRANSFER);

        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);
        $internalTransferManager->reject($internalTransfer);

        return $this->view(['rejected' => true], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/internal-transfers/{internalTransferId}/decline", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putInternalTransferDecline(int $internalTransferId, InternalTransferManager $internalTransferManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_INTERNAL_TRANSFER);

        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);
        $internalTransferManager->decline($internalTransfer);

        return $this->view(['declined' => true], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/internal-transfers/{internalTransferId}/approve", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putInternalTransferApprove(int $internalTransferId, InternalTransferManager $internalTransferManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_INTERNAL_TRANSFER);

        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);
        $internalTransferManager->approve($internalTransfer);

        return $this->view(['approved' => true], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/internal-transfers/{internalTransferId}/revert", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putInternalTransferRevert(int $internalTransferId, InternalTransferManager $internalTransferManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_INTERNAL_TRANSFER);

        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);
        $internalTransferManager->revert($internalTransfer);

        return $this->view(['reverted' => true], JsonResponse::HTTP_OK);
    }
}
