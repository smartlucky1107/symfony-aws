<?php

namespace App\Controller\ApiAdmin;

use App\DataTransformer\VoterRoleTransformer;
use App\Entity\Configuration\VoterRole;
use App\Manager\ListFilter\VoterRoleListFilter;
use App\Manager\VoterRoleManager;
use App\Manager\ListFilter\DepositListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\Configuration\VoterRoleRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Exception\AppException;

class VoterRoleController extends FOSRestController
{
    /**
     * @Rest\Get("/voter-roles", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param VoterRoleRepository $voterRoleRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getVoterRoles(Request $request, VoterRoleRepository $voterRoleRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_VOTER_ROLE);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new VoterRoleListFilter($request), $voterRoleRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/voter-roles", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param VoterRoleTransformer $voterRoleTransformer
     * @param VoterRoleManager $voterRoleManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postVoterRole(Request $request, VoterRoleTransformer $voterRoleTransformer, VoterRoleManager $voterRoleManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_CREATE, VoterRoleInterface::MODULE_VOTER_ROLE);

        /** @var VoterRole $voterRole */
        $voterRole = $voterRoleTransformer->transform($request);
        $voterRoleTransformer->validate($voterRole);

        $voterRole = $voterRoleManager->update($voterRole);

        return $this->view(['voterRole' => $voterRole->serialize()], JsonResponse::HTTP_OK);
    }
}
