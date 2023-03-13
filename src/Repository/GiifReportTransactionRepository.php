<?php

namespace App\Repository;

use App\Entity\GiifReportTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GiifReportTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiifReportTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiifReportTransaction[]    findAll()
 * @method GiifReportTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GiifReportTransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GiifReportTransaction::class);
    }

    /**
     * @param GiifReportTransaction $giifReportTransaction
     * @return GiifReportTransaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(GiifReportTransaction $giifReportTransaction)
    {
        $this->_em->persist($giifReportTransaction);
        $this->_em->flush();

        return $giifReportTransaction;
    }

    // /**
    //  * @return GiifReportTransaction[] Returns an array of GiifReportTransaction objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GiifReportTransaction
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
