<?php

namespace App\Manager;

use App\Entity\Currency;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Entity\Wallet\AffiliateReward;
use App\Repository\Wallet\AffiliateRewardRepository;

class AffiliateRewardManager
{
    /** @var AffiliateRewardRepository */
    private $affiliateRewardRepository;

    /**
     * AffiliateRewardManager constructor.
     * @param AffiliateRewardRepository $affiliateRewardRepository
     */
    public function __construct(AffiliateRewardRepository $affiliateRewardRepository)
    {
        $this->affiliateRewardRepository = $affiliateRewardRepository;
    }

    /**
     * @param User $user
     * @param User $affiliateUser
     * @param Trade $trade
     * @return bool
     */
    public function rewardExists(User $user, User $affiliateUser, Trade $trade) : bool
    {
        /** @var AffiliateReward $affiliateReward */
        $affiliateReward = $this->affiliateRewardRepository->findOneBy([
            'user' => $user->getId(),
            'affiliateUser' => $affiliateUser->getId(),
            'trade' => $trade->getId()
        ]);
        if($affiliateReward instanceof AffiliateReward){
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param User $affiliateUser
     * @param Trade $trade
     * @param $amount
     * @param Currency $currency
     * @return AffiliateReward
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function createReward(User $user, User $affiliateUser, Trade $trade, $amount, Currency $currency) : AffiliateReward
    {
        /** @var AffiliateReward $affiliateReward */
        $affiliateReward = new AffiliateReward($user, $affiliateUser, $trade, $amount, $currency);

        return $this->affiliateRewardRepository->save($affiliateReward);
    }

    /**
     * @param AffiliateReward $affiliateReward
     * @return AffiliateReward
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function assignReward(AffiliateReward $affiliateReward) : AffiliateReward
    {
        // TODO

        return $this->affiliateRewardRepository->save($affiliateReward);
    }
}
