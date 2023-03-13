<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class JWTNotFoundListener
{
    /**
     * @param JWTNotFoundEvent $event
     */
    public function onJWTNotFound(JWTNotFoundEvent $event)
    {
        //throw new AccessDeniedHttpException('Access denied');
        $response = new JsonResponse(['message' => 'Authorization token is missing',], JsonResponse::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }
}