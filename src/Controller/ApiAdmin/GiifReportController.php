<?php

namespace App\Controller\ApiAdmin;

use App\Manager\ListFilter\GiifReportListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\GiifReportRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class GiifReportController extends FOSRestController
{
    /**
     * @Rest\Get("/giif-reports", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param GiifReportRepository $giifReportRepository
     * @param ListManager $listManager
     * @return View
     * @throws \Exception
     */
    public function getGiifReports(Request $request, GiifReportRepository $giifReportRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_GIIF_REPORTS);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new GiifReportListFilter($request), $giifReportRepository)
            ->load();

        return $this->view($paginator, Response::HTTP_OK);
    }
}
