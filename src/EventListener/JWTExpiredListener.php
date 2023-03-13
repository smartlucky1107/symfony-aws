<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;

class JWTExpiredListener
{
    /**
     * @param JWTExpiredEvent $event
     */
    public function onJWTExpired(JWTExpiredEvent $event)
    {
        $response = new JWTAuthenticationFailureResponse('Token is expired, please renew it', JWTAuthenticationFailureResponse::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }
}