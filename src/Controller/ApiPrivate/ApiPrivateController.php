<?php

namespace App\Controller\ApiPrivate;

use App\Document\ApiPrivateRequest;
use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\Extractor\ApiKeyExtractor;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;

class ApiPrivateController extends FOSRestController
{
    /**
     * @param Request $request
     * @param ApiKeyExtractor $apiKeyExtractor
     * @param ApiPrivateManager $apiPrivateManager
     * @param string $controller
     * @param array $path
     * @param string $requiredApiRole
     * @return View
     * @throws \Exception
     */
    protected function callPrivateApi(Request $request, ApiKeyExtractor $apiKeyExtractor, ApiPrivateManager $apiPrivateManager, string $controller, array $path, string $requiredApiRole){
        $key = $apiKeyExtractor->extract($request);

        $this->denyAccessUnlessGranted($requiredApiRole, $apiKeyExtractor->load($key));

        /** @var ApiPrivateRequest $apiPrivateRequest */
        $apiPrivateRequest = $apiPrivateManager->saveRequest($key, $request->getRequestUri(), $request->getContent(), $request->getMethod());

        $response = $this->forward($controller, $path);

        $apiPrivateManager->assignResponse($apiPrivateRequest, $response->getContent());

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }
}
