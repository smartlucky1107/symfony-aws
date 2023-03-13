<?php

namespace App\EventListener;

use App\Document\Login;
use App\Entity\User;
use App\Event\LoginEvent;
use App\Manager\LoginManager;

class LoginListener
{
    /** @var LoginManager */
    private $loginManager;

    /**
     * LoginListener constructor.
     * @param LoginManager $loginManager
     */
    public function __construct(LoginManager $loginManager)
    {
        $this->loginManager = $loginManager;
    }

    /**
     * @return string
     */
    private function getClientIp() : string
    {
        $ip = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ip = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ip = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ip = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ip = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ip = getenv('REMOTE_ADDR');

        return $ip;
    }

    /**
     * @param LoginEvent $event
     * @throws \Exception
     */
    public function onLogin(LoginEvent $event)
    {
        $user = $event->getUser();
        if($user instanceof User){
            /** @var Login $login */
            $login = new Login($user->getId(), $this->getClientIp());

            $this->loginManager->saveLogin($login);
        }
    }
}