<?php

namespace App\Repository;

use App\Entity\PaymentCallback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PaymentCallback|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentCallback|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentCallback[]    findAll()
 * @method PaymentCallback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentCallbackRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentCallback::class);
    }

    /**
     * @param PaymentCallback $paymentCallback
     * @return PaymentCallback
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(PaymentCallback $paymentCallback)
    {
        $this->_em->persist($paymentCallback);
        $this->_em->flush();

        return $paymentCallback;
    }

    // /**
    //  * @return PaymentCallback[] Returns an array of PaymentCallback objects
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
    public function findOneBySomeField($value): ?PaymentCallback
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
