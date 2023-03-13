<?php

namespace App\Repository;

use App\Entity\PaymentCardRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PaymentCardRegistration|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentCardRegistration|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentCardRegistration[]    findAll()
 * @method PaymentCardRegistration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentCardRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentCardRegistration::class);
    }

    /**
     * @param PaymentCardRegistration $paymentCardRegistration
     * @return PaymentCardRegistration
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(PaymentCardRegistration $paymentCardRegistration)
    {
        $this->_em->persist($paymentCardRegistration);
        $this->_em->flush();

        return $paymentCardRegistration;
    }

    // /**
    //  * @return PaymentCardRegistration[] Returns an array of PaymentCardRegistration objects
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
    public function findOneBySomeField($value): ?PaymentCardRegistration
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
