<?php

namespace App\Repository\Payment;

use App\Entity\Payment\PaywallCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PaywallCard|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaywallCard|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaywallCard[]    findAll()
 * @method PaywallCard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaywallCardRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaywallCard::class);
    }

    // /**
    //  * @return PaywallCard[] Returns an array of PaywallCard objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PaywallCard
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
