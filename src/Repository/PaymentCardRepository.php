<?php

namespace App\Repository;

use App\Entity\PaymentCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PaymentCard|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentCard|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentCard[]    findAll()
 * @method PaymentCard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentCardRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentCard::class);
    }

    /**
     * @param PaymentCard $paymentCard
     * @return PaymentCard
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(PaymentCard $paymentCard)
    {
        $this->_em->persist($paymentCard);
        $this->_em->flush();

        return $paymentCard;
    }

    // /**
    //  * @return PaymentCard[] Returns an array of PaymentCard objects
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
    public function findOneBySomeField($value): ?PaymentCard
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
