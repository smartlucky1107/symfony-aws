<?php

namespace App\Security;

use App\Entity\POS\POSOrder;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class POSOrderVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof POSOrder) {
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

        /** @var POSOrder $POSOrder */
        $POSOrder = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($POSOrder, $user);
            case self::EDIT:
                return $this->canEdit($POSOrder, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(POSOrder $POSOrder, User $user)
    {
        if ($this->canEdit($POSOrder, $user)) {
            return true;
        }

        return false;
    }

    private function canEdit(POSOrder $POSOrder, User $user)
    {
        return ($POSOrder->isAllowedForUser($user) || $user->isAdmin());
    }
}
