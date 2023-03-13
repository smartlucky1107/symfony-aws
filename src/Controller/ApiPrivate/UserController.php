<?php

namespace App\Controller\ApiPrivate;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class UserController extends ApiPrivateController
{
    /**
     * @Rest\Get("/users/me/wallets/{currencyShortName}")
     *
     * @param string $currencyShortName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getMyWallet(string $currencyShortName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\UserController:getMyWallet', ['currencyShortName'  => $currencyShortName], ApiRoleInterface::ROLE_USER
        );
    }

    /**
     * @Rest\Get("/users/me/wallets")
     *
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getMyWallets(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\UserController:getMyWallets', ['request'  => $request, 'isForPrivateApi' => true], ApiRoleInterface::ROLE_USER
        );
    }

    /**
     * @Rest\Get("/users/me/orders")
     *
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getMyOrders(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\UserController:getMyOrders', ['request'  => $request, 'isForPrivateApi' => true], ApiRoleInterface::ROLE_USER
        );
    }

    /**
     * @Rest\Get("/users/me/trades")
     *
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getMyTrades(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\UserController:getMyTrades', ['request'  => $request, 'isForPrivateApi' => true], ApiRoleInterface::ROLE_USER
        );
    }
}
