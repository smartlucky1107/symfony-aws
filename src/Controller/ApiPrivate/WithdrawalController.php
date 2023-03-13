<?php

namespace App\Controller\ApiPrivate;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class WithdrawalController extends ApiPrivateController
{
    /**
     * @Rest\Put("/withdrawals/{withdrawalId}/approve", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     *
     * @param int $withdrawalId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function putWithdrawalApprove(int $withdrawalId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WithdrawalController:putWithdrawalApprove', ['withdrawalId'  => $withdrawalId, 'user' => $this->getUser()], ApiRoleInterface::ROLE_WITHDRAWAL_APPROVE
        );
    }

    /**
     * @Rest\Put("/withdrawals/{withdrawalId}/decline", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     *
     * @param int $withdrawalId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function putWithdrawalDecline(int $withdrawalId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WithdrawalController:putWithdrawalDecline', ['withdrawalId'  => $withdrawalId], ApiRoleInterface::ROLE_WITHDRAWAL_DECLINE
        );
    }

    /**
     * @Rest\Get("/withdrawals/for-external-approval", options={"expose"=true})
     *
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWithdrawalsForExternalApproval(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\WithdrawalController:getWithdrawalsForExternalApproval', ['request'  => $request], ApiRoleInterface::ROLE_WITHDRAWALS_FOR_EXTERNAL_APPROVAL
        );
    }
}
