<?php

namespace App\Controller\ApiPrivate;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class TradeController extends ApiPrivateController
{
    /**
     * @Rest\Put("/trades/{tradeId}/revert", requirements={"tradeId"="\d+"}, options={"expose"=true})
     *
     * @param int $tradeId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function putTradeRevert(int $tradeId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\TradeController:putTradeRevert', ['tradeId'  => $tradeId], ApiRoleInterface::ROLE_TRADE_REVERT
        );
    }
}
