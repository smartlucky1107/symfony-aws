<?php

namespace App\Repository;

use App\Entity\ReferralLink;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReferralLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReferralLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReferralLink[]    findAll()
 * @method ReferralLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReferralLinkRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReferralLink::class);
    }

    /**
     * Find the latest referral link generated for passed $user
     *
     * @param User $user
     * @return ReferralLink|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLatestByUser(User $user) : ?ReferralLink
    {
        $query = $this->_em->createQuery("
            SELECT rl
            FROM App:ReferralLink rl
            WHERE rl.user = :userId
            ORDER BY rl.id DESC
        ");
        $query->setParameter('userId', $user->getId());
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * @param ReferralLink $referralLink
     * @return ReferralLink
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(ReferralLink $referralLink)
    {
        $this->_em->persist($referralLink);
        $this->_em->flush();

        return $referralLink;
    }

    // /**
    //  * @return ReferralLink[] Returns an array of ReferralLink objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ReferralLink
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
