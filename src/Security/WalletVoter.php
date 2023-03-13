<?php

namespace App\Security;

use App\Entity\Wallet\Wallet;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WalletVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Wallet) {
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

        /** @var Wallet $wallet */
        $wallet = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($wallet, $user);
            case self::EDIT:
                return $this->canEdit($wallet, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Wallet $wallet, User $user)
    {
        if ($this->canEdit($wallet, $user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Wallet $wallet, User $user)
    {
        return $wallet->isAllowedForUser($user);
    }
}