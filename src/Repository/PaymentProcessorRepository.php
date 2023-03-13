<?php

namespace App\Repository;

use App\Entity\PaymentProcessor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PaymentProcessor|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentProcessor|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentProcessor[]    findAll()
 * @method PaymentProcessor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentProcessorRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentProcessor::class);
    }

    /**
     * @return array|null
     */
    public function findEnabled() : ?array
    {
        $query = $this->_em->createQuery("
            SELECT paymentProcessor
            FROM App:PaymentProcessor paymentProcessor
            WHERE paymentProcessor.enabled = TRUE 
        ");

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    // /**
    //  * @return PaymentProcessor[] Returns an array of PaymentProcessor objects
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
    public function findOneBySomeField($value): ?PaymentProcessor
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
