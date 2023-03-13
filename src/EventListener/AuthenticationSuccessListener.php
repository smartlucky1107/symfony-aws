<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuthenticationSuccessListener
{
    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();

        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            $event->setData([]);
        }

        if(!$user->isEmailConfirmed()){
            throw new AccessDeniedException('Email address is not confirmed');
//            $event->setData([]);
        }
    }
}
