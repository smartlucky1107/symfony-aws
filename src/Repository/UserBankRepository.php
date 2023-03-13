<?php

namespace App\Repository;

use App\Entity\UserBank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UserBank|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBank|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBank[]    findAll()
 * @method UserBank[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBankRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserBank::class);
    }

    /**
     * @param UserBank $userBank
     * @return UserBank
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(UserBank $userBank)
    {
        $this->_em->persist($userBank);
        $this->_em->flush();

        return $userBank;
    }

    // /**
    //  * @return UserBank[] Returns an array of UserBank objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserBank
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
