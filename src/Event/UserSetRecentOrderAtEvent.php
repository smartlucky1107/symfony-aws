<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class UserSetRecentOrderAtEvent extends Event
{
    public const NAME = 'user.on_set_recent_order_at';

    /** @var User */
    protected $user;

    /** @var \DateTime */
    protected $dateTime;

    /**
     * UserSetRecentOrderAtEvent constructor.
     * @param User $user
     * @param \DateTime $dateTime
     */
    public function __construct(User $user, \DateTime $dateTime)
    {
        $this->user = $user;
        $this->dateTime = $dateTime;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }
}