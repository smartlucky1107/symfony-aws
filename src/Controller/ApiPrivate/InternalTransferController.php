<?php

namespace App\Controller\ApiPrivate;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class InternalTransferController extends ApiPrivateController
{
    /**
     * @Rest\Put("/internal-transfers/{internalTransferId}/confirm", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     *
     * @param int $internalTransferId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function putInternalTransferConfirm(int $internalTransferId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        // TODO - zabezpieczenie przed tym, zeby mozna było confirmowac dowolny internal transfer - użyc np. hash lub cos innego

        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\InternalTransferController:putInternalTransferConfirm', ['internalTransferId'  => $internalTransferId], ApiRoleInterface::ROLE_INTERNAL_TRANSFER_CONFIRM
        );
    }

    /**
     * @Rest\Get("/internal-transfers/{internalTransferId}", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     *
     * @param int $internalTransferId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getInternalTransfer(int $internalTransferId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        // TODO - zabezpieczenie przed tym, zeby mozna było confirmowac dowolny internal transfer - użyc np. hash lub cos innego

        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\InternalTransferController:getInternalTransfer', ['internalTransferId'  => $internalTransferId], ApiRoleInterface::ROLE_INTERNAL_TRANSFER_CONFIRM
        );
    }
}
