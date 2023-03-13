<?php

namespace App\EventListener;

use App\Event\UserSetRecentOrderAtEvent;
use App\Manager\UserManager;

class UserUpdateListener
{
    /** @var UserManager */
    private $userManager;

    /**
     * UserUpdateListener constructor.
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param UserSetRecentOrderAtEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onSetRecentOrderAt(UserSetRecentOrderAtEvent $event)
    {
        $this->userManager->updateRecentOrderTime($event->getUser(), $event->getDateTime());
    }
}
