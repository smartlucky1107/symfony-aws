<?php

namespace App\Controller\ApiPrivate;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class WalletController extends ApiPrivateController
{
    /**
     * @Rest\Put("/wallets/{currencyShortName}/generate-address")
     *
     * @param string $currencyShortName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function putWalletGenerateAddress(string $currencyShortName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WalletController:putWalletGenerateAddress', ['currencyShortName' => $currencyShortName], ApiRoleInterface::ROLE_WALLET
        );
    }

    /**
     * @Rest\Post("/wallets/{currencyShortName}/withdrawal-request")
     *
     * @param string $currencyShortName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function postWalletWithdrawal(string $currencyShortName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WalletController:postWalletWithdrawal', ['currencyShortName' => $currencyShortName, 'request' => $request], ApiRoleInterface::ROLE_WALLET
        );
    }

    /**
     * @Rest\Post("/wallets/{currencyShortName}/internal-transfer-request")
     *
     * @param string $currencyShortName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function postWalletInternalTransfer(string $currencyShortName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WalletController:postWalletInternalTransfer', ['currencyShortName' => $currencyShortName, 'request' => $request], ApiRoleInterface::ROLE_WALLET
        );
    }

    /**
     * @Rest\Get("/wallets/{walletId}/withdrawals", requirements={"walletId"="\d+"})
     *
     * @param int $walletId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWalletWithdrawals(int $walletId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WalletController:getWalletWithdrawals', ['walletId' => $walletId, 'request' => $request], ApiRoleInterface::ROLE_WALLET
        );
    }

    /**
     * @Rest\Get("/wallets/{walletId}/deposits", requirements={"walletId"="\d+"})
     *
     * @param int $walletId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWalletDeposits(int $walletId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WalletController:getWalletDeposits', ['walletId' => $walletId, 'request' => $request], ApiRoleInterface::ROLE_WALLET
        );
    }

    /**
     * @Rest\Get("/wallets/{walletId}/analyze", requirements={"walletId"="\d+"}, options={"expose"=true})
     *
     * @param int $walletId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWalletAnalyze(int $walletId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WalletController:getWalletAnalyze', ['walletId' => $walletId], ApiRoleInterface::ROLE_WALLET_ANALYZE
        );
    }
}
