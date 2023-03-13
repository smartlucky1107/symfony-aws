<?php

namespace App\Security;

use App\Entity\Configuration\ApiKey;
use App\Entity\Wallet\Wallet;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiRoleVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, ApiRoleInterface::ROLES)) {
            return false;
        }

        if (!$subject instanceof ApiKey) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        /** @var ApiKey $apiKey */
        $apiKey = $subject;
        if($apiKey->getApiRoles()){
            foreach($apiKey->getApiRoles() as $role){
                if($role === $attribute){
                    return true;
                }
            }
        }

        return false;
    }
}