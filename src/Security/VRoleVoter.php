<?php

namespace App\Security;

use App\Entity\Configuration\VoterRole;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VRoleVoter extends Voter
{
    private $actionsHierarchy = [];

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, VoterRoleInterface::ACTIONS)) {
            return false;
        }

        if (!in_array($subject, VoterRoleInterface::MODULES)) {
            return false;
        }

        return true;
    }

    private function buildHierarchyRecursively($parent, $array) {
        if ($array) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $this->actionsHierarchy[] = [
                        'parent' => $parent,
                        'child' => $key
                    ];
                }else{
                    $this->actionsHierarchy[] = [
                        'parent' => $parent,
                        'child' => $value
                    ];
                }

                if (is_array($value)) {
                    $this->buildHierarchyRecursively($key, $value);
                }
            }
        }
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        if($user->isSuperAdmin()) return true;
        if($user->isAdmin()){
            /** @var VoterRole $voterRole */
            foreach($user->getVoterRoles() as $voterRole){
                if($voterRole->getModule() === $subject){
                    if($voterRole->getAction() === $attribute){
                        return true;
                    }

                    $this->buildHierarchyRecursively(null, VoterRoleInterface::ACTIONS_HIERARCHY);

                    foreach($this->actionsHierarchy as $item){
                        if($item['child'] === $attribute && $voterRole->getAction() === $item['parent']){
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}