<?php

namespace App\Manager;

use App\Entity\ReferralLink;
use App\Entity\User;
use App\Model\PriceInterface;
use App\Repository\ReferralLinkRepository;
use App\Repository\UserRepository;

class AffiliateManager
{
    /** @var ReferralLinkRepository */
    private $referralLinkRepository;

    /** @var UserRepository */
    private $userRepository;

    /**
     * AffiliateManager constructor.
     * @param ReferralLinkRepository $referralLinkRepository
     * @param UserRepository $userRepository
     */
    public function __construct(ReferralLinkRepository $referralLinkRepository, UserRepository $userRepository)
    {
        $this->referralLinkRepository = $referralLinkRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function getUserAffiliates(User $user) : ?array
    {
        $referralLinks = $this->referralLinkRepository->findBy(['user' => $user->getId()]);
        if($referralLinks){
            $referralLinkIds = [];

            /** @var ReferralLink $referralLink */
            foreach($referralLinks as $referralLink){
                $referralLinkIds[] = $referralLink->getId();
            }

            if(is_array($referralLinkIds) && count($referralLinkIds) > 0){
                return $this->userRepository->findReferredBy($referralLinkIds);
            }
        }

        return null;
    }
}
