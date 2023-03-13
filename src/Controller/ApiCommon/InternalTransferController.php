<?php

namespace App\Controller\ApiCommon;

use App\Entity\Wallet\InternalTransfer;
use App\Manager\InternalTransferManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalTransferController extends AbstractController
{
    /**
     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return JsonResponse
     * @throws \Exception
     */
    public function putInternalTransferConfirm(int $internalTransferId, InternalTransferManager $internalTransferManager) : JsonResponse
    {
        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);
        $internalTransferManager->pushForInternalTransferRequest($internalTransfer);

        return new JsonResponse(['confirmed' => true], Response::HTTP_OK);
    }

    /**
     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return JsonResponse
     * @throws \Exception
     */
    public function getInternalTransfer(int $internalTransferId, InternalTransferManager $internalTransferManager) : JsonResponse
    {
        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);

        return new JsonResponse(['internalTransfer' => $internalTransfer->serialize()], Response::HTTP_OK);
    }
}
