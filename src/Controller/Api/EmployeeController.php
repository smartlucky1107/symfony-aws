<?php

namespace App\Controller\Api;

use App\Manager\ListFilter\EmployeeListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\POS\EmployeeRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EmployeeController extends FOSRestController
{
    /**
     * @Rest\Get("/employees", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param EmployeeRepository $employeeRepository
     * @param ListManager $listManager
     * @return View
     * @throws \Exception
     */
    public function getEmployees(Request $request, EmployeeRepository $employeeRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_EMPLOYEE);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new EmployeeListFilter($request), $employeeRepository)
            ->load();

        return $this->view($paginator, Response::HTTP_OK);
    }
}
