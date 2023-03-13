<?php

namespace App\Controller\ApiAdmin;


use App\Repository\CheckoutOrderRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class StatisticsController extends FOSRestController
{
    /**
     * @Rest\Get("/statistics/checkout-orders", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param UserRepository $userRepository
     * @return View
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCheckoutOrdersStats(Request $request, CheckoutOrderRepository $checkoutOrderRepository): View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        $dateStart = $request->get('dateStart', null);
        $dateStop = $request->get('dateStop', null);

        if(!is_null($dateStart)) {
            $dateStart =  new \DateTime($dateStart);
        }

        if(!is_null($dateStop)) {
            $dateStop =  new \DateTime($dateStop);
        }

        $checkoutOrderStatistics = $checkoutOrderRepository->findBetweenDates($dateStart, $dateStop);

        return $this->view($checkoutOrderStatistics, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/statistics/trades", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param UserRepository $userRepository
     * @return View
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTradesStats(Request $request, CheckoutOrderRepository $checkoutOrderRepository)#: View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        $dateStart = $request->get('dateStart', null);
        $dateStop = $request->get('dateStop', null);

        if(!is_null($dateStart)) {
            $dateStart =  new \DateTime($dateStart);
        }

        if(!is_null($dateStop)) {
            $dateStop =  new \DateTime($dateStop);
        }

        $checkoutOrderStatistics = $checkoutOrderRepository->findBetweenDates($dateStart, $dateStop);

        dump($checkoutOrderStatistics); die;

        return $this->view($checkoutOrderStatistics, JsonResponse::HTTP_OK);
    }
}