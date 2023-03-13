<?php

namespace App\Controller\ApiCommon;

use App\Entity\POS\POSOrder;
use App\Manager\OrderManager;
use App\Repository\POS\POSOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class POSOrderController extends AbstractController
{
    /**
     * @param int $POSOrderId
     * @param POSOrderRepository $POSOrderRepository
     * @return JsonResponse
     * @throws \App\Exception\AppException
     */
    public function getPOSOrder(int $POSOrderId, POSOrderRepository $POSOrderRepository) : JsonResponse
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderRepository->findOrException($POSOrderId);

        // resolve access
        $this->denyAccessUnlessGranted('view', $POSOrder);

        return new JsonResponse($POSOrder->serializeBasic(), Response::HTTP_OK);
    }
}
