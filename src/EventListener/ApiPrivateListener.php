<?php

namespace App\EventListener;

use App\Entity\Configuration\ApiKey;
use App\Manager\ApiPrivate\ApiPrivateManager;
use App\Security\Extractor\ApiKeyExtractor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiPrivateListener implements EventSubscriberInterface
{
    /** @var ApiPrivateManager */
    private $apiPrivateManager;

    /** @var ApiKeyExtractor */
    private $apiKeyExtractor;

    /**
     * ApiPrivateListener constructor.
     * @param ApiPrivateManager $apiPrivateManager
     * @param ApiKeyExtractor $apiKeyExtractor
     */
    public function __construct(ApiPrivateManager $apiPrivateManager, ApiKeyExtractor $apiKeyExtractor)
    {
        $this->apiPrivateManager = $apiPrivateManager;
        $this->apiKeyExtractor = $apiKeyExtractor;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST  => array('onKernelRequest', 9999),
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        return;

        if (!$event->isMasterRequest()) {
            return;
        }

        // perform preflight checks
        if ('OPTIONS' === $event->getRequest()->getRealMethod()) {
            return;
        }

        if (0 == strpos($event->getRequest()->getPathInfo(), '/api-private/')) {
            $key = $this->apiKeyExtractor->extract($event->getRequest());

            /** @var ApiKey $apiKey */
            $apiKey = $this->apiKeyExtractor->load($key);
            if($apiKey instanceof ApiKey){
                try{
                    $this->apiPrivateManager->verifyRequestLimits($apiKey);
                }catch (\Exception $exception){
                    $response = new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_FORBIDDEN);
                    $event->setResponse($response);
                }
            }
        }
    }
}