<?php

namespace App\Controller\ApiAdmin;

use App\Manager\ListFilter\OrderListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\OrderBook\OrderRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class OrderController extends FOSRestController
{
    /**
     * @Rest\Get("/orders", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param OrderRepository $orderRepository
     * @param ListManager $listManager
     * @return View
     * @throws \Exception
     */
    public function getOrders(Request $request, OrderRepository $orderRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_ORDER);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new OrderListFilter($request), $orderRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }
}
