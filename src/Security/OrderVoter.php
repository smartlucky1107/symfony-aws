<?php

namespace App\Security;

use App\Entity\Orderbook\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Order) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Order $order */
        $order = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($order, $user);
            case self::EDIT:
                return $this->canEdit($order, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Order $order, User $user)
    {
        if ($this->canEdit($order, $user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Order $order, User $user)
    {
        return ($order->isAllowedForUser($user) || $user->isAdmin());
    }
}