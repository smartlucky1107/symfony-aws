<?php

namespace App\Controller\ApiAdmin;

use App\Manager\ListFilter\POSOrderListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\POS\POSOrderRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class POSOrderController extends FOSRestController
{
    /**
     * @Rest\Get("/pos-orders", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param POSOrderRepository $POSOrderRepository
     * @param ListManager $listManager
     * @return View
     * @throws \Exception
     */
    public function getPOSOrders(Request $request, POSOrderRepository $POSOrderRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_POS_ORDER);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new POSOrderListFilter($request), $POSOrderRepository)
            ->load();

        return $this->view($paginator, Response::HTTP_OK);
    }
}
