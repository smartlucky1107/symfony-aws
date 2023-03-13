<?php

namespace App\Controller\ApiAdmin;

use App\Entity\Configuration\SystemTag;
use App\Manager\ListFilter\SystemTagListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\SystemTagManager;
use App\Repository\Configuration\SystemTagRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Exception\AppException;

class SystemTagController extends FOSRestController
{
    /**
     * @Rest\Get("/system-tags", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param SystemTagRepository $systemTagRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getSystemTags(Request $request, SystemTagRepository $systemTagRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_VOTER_ROLE);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new SystemTagListFilter($request), $systemTagRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/system-tags/{systemTagId}/toggle", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $systemTagId
     * @param SystemTagManager $systemTagManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putSystemTagToggle(int $systemTagId, SystemTagManager $systemTagManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_VOTER_ROLE);

        $systemTagManager->load($systemTagId);

        /** @var SystemTag $systemTag */
        $systemTag = $systemTagManager->toggle();

        return $this->view(['systemTag' => $systemTag->serialize()], JsonResponse::HTTP_OK);
    }
}
