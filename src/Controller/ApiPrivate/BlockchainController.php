<?php

namespace App\Controller\ApiPrivate;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class BlockchainController extends ApiPrivateController
{
    /**
     * @Rest\Post("/blockchain/ethereum-tx")
     *
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function postBlockchainEthereumTx(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\BlockchainController:postBlockchainEthereumTx', ['request'  => $request], ApiRoleInterface::ROLE_BLOCKCHAIN_TX_CREATE
        );
    }

    /**
     * @Rest\Post("/blockchain/bitcoin-tx")
     *
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function postBlockchainBitcoinTx(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\BlockchainController:postBlockchainBitcoinTx', ['request'  => $request], ApiRoleInterface::ROLE_BLOCKCHAIN_TX_CREATE
        );
    }
}
