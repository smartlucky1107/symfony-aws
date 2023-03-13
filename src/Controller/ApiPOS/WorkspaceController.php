<?php

namespace App\Controller\ApiPOS;

use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\ApiRoleInterface;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class WorkspaceController extends ApiPOSController
{

    /**
     * Authorize the workspace
     *
     * @Rest\Post("/workspaces/{workspaceName}/auth", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns true|false",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function postWorkspaceAuth(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:postWorkspaceAuth', ['workspaceName' => $workspaceName, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Check if workspace exist for authorized user
     *
     * @Rest\Get("/workspaces/{workspaceName}/exists", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns true|false",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWorkspaceExists(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:getWorkspaceExists', ['workspaceName' => $workspaceName], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Check if workspace exist for authorized user
     *
     * @Rest\Get("/workspaces/{workspaceName}/currencies", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns true|false",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWorkspaceCurrencies(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:getWorkspaceCurrencies', ['workspaceName' => $workspaceName], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Create new pos order and check new pos order price.
     *
     * @Rest\Post("/workspaces/{workspaceName}/order", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="",
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about new POSOrder",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="amount", type="string", description="Amount of the base currency"),
     *         @SWG\Property(property="currency", type="string", description="Short name of the currency, eg. PLN, BTC"),
     *         @SWG\Property(property="phone", type="string", description="Phone number of the customer | optional"),
     *         @SWG\Property(property="place-order", type="boolean", description="If 'TRUE' then POSOrder is placed | optional")
     *     )
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function postWorkspaceOrder(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:postWorkspaceOrder', ['workspaceName' => $workspaceName, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Get details about the POSOrder
     *
     * @Rest\Get("/workspaces/{workspaceName}/order/{POSOrderId}", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized POSOrder object",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWorkspaceOrder(string $workspaceName, int $POSOrderId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:getWorkspaceOrder', ['workspaceName' => $workspaceName, 'POSOrderId' => $POSOrderId, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Get list of employees
     *
     * @Rest\Get("/workspaces/{workspaceName}/employees", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of employees",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWorkspaceEmployees(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:getWorkspaceEmployees', ['workspaceName' => $workspaceName], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Get list of transactions made today for employee
     *
     * @Rest\Get("/workspaces/{workspaceName}/tranactions", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of tranactions",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function getWorkspaceEmployeeTransactions(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:getWorkspaceEmployeeTransactions', ['workspaceName' => $workspaceName, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Employee ping to verify the PIN
     *
     * @Rest\Get("/workspaces/{workspaceName}/employee-ping", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="No content. PIN is valid.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid PIN.",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function patchWorkspaceEmployeePing(string $workspaceName, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:patchWorkspaceEmployeePing', ['workspaceName' => $workspaceName, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Confirm the POSOrder and create real Order. Start processing.
     *
     * @Rest\Patch("/workspaces/{workspaceName}/order/{POSOrderId}/confirm", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params for confirmation",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"code"},
     *         @SWG\Property(property="code",  type="string",  description="Confirmation code from Google Authenticator", example="1234"),
     *     )
     * )
     *
     * @SWG\Response(
     *     response=202,
     *     description="Added for processing. Return serialized POSOrder object.",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function confirmPOSOrder(string $workspaceName, int $POSOrderId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:confirmPOSOrder', ['workspaceName' => $workspaceName, 'POSOrderId' => $POSOrderId, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Send confirmation code for POSOrder.
     *
     * @Rest\Patch("/workspaces/{workspaceName}/order/{POSOrderId}/confirmation-code", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=201,
     *     description="Confirmation code sent.",
     * )
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function sendPOSOrderConfirmationCode(string $workspaceName, int $POSOrderId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:sendPOSOrderConfirmationCode', ['workspaceName' => $workspaceName, 'POSOrderId' => $POSOrderId, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Send confirmation message for POSOrder after success.
     *
     * @Rest\Patch("/workspaces/{workspaceName}/order/{POSOrderId}/confirmation", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=201,
     *     description="Confirmation SMS sent.",
     * )
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function sendPOSOrderConfirmationSMS(string $workspaceName, int $POSOrderId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:sendPOSOrderConfirmationSMS', ['workspaceName' => $workspaceName, 'POSOrderId' => $POSOrderId, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }

    /**
     * Reject the POSOrder.
     *
     * @Rest\Patch("/workspaces/{workspaceName}/order/{POSOrderId}/reject", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=202,
     *     description="Added for processing. Return serialized POSOrder object.",
     * )
     *
     * @SWG\Tag(name="Workspace")
     *
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @return View
     * @throws \Exception
     */
    public function rejectPOSOrder(string $workspaceName, int $POSOrderId, Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager) : View
    {
        return $this->callPrivateApi(
            $request, $apiKeyExtractor, $apiPrivateManager,
            'App\Controller\ApiCommon\POSController:rejectPOSOrder', ['workspaceName' => $workspaceName, 'POSOrderId' => $POSOrderId, 'request' => $request], ApiRoleInterface::ROLE_POS
        );
    }
}
