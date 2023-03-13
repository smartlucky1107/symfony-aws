<?php

namespace App\Repository\Wallet;

use App\Entity\User;
use App\Entity\Wallet\AffiliateReward;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AffiliateReward|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffiliateReward|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffiliateReward[]    findAll()
 * @method AffiliateReward[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffiliateRewardRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AffiliateReward::class);
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function findByUserGroupedByCurrency(User $user) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT
                SUM(affiliateReward.amount) as amount,
                currency.fullName as fullName,
                currency.shortName as shortName
            FROM App:Wallet\AffiliateReward affiliateReward
            LEFT JOIN affiliateReward.currency currency
            WHERE
                affiliateReward.user = :userId
            GROUP BY currency.id
        ");
        $query->setParameter('userId', $user->getId());

        $result = $query->getArrayResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param AffiliateReward $affiliateReward
     * @return AffiliateReward
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(AffiliateReward $affiliateReward)
    {
        $this->_em->persist($affiliateReward);
        $this->_em->flush();

        return $affiliateReward;
    }

    // /**
    //  * @return AffiliateReward[] Returns an array of AffiliateReward objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AffiliateReward
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
