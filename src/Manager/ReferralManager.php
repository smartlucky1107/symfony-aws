<?php

namespace App\Manager;

use App\Entity\ReferralLink;
use App\Entity\User;
use App\Repository\ReferralLinkRepository;

class ReferralManager
{
    /** @var ReferralLinkRepository */
    private $referralLinkRepository;

    /**
     * ReferralManager constructor.
     * @param ReferralLinkRepository $referralLinkRepository
     */
    public function __construct(ReferralLinkRepository $referralLinkRepository)
    {
        $this->referralLinkRepository = $referralLinkRepository;
    }

    /**
     * @param User $user
     * @return ReferralLink
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateUserReferral(User $user) : ReferralLink
    {
        $referralLink = new ReferralLink($user);

        return $this->referralLinkRepository->save($referralLink);
    }
}
