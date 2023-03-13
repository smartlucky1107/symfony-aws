<?php

namespace App\Repository;

use App\Entity\GiifReport;
use App\Manager\ListFilter\GiifReportListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GiifReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiifReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiifReport[]    findAll()
 * @method GiifReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GiifReportRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GiifReport::class);
    }

    /**
     * @param GiifReportListFilter $giifReportListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(GiifReportListFilter $giifReportListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($giifReportListFilter->id)){
            $criteria->where(Criteria::expr()->eq('giif_report.id', $giifReportListFilter->id));
        }

        if(!is_null($giifReportListFilter->userId)){
            $criteria->andWhere(Criteria::expr()->eq('giif_report.user', (int) $giifReportListFilter->userId));
        }

        // query for results
        $qb = $this->createQueryBuilder('giif_report');
        $qb->addCriteria($criteria);
        if(!is_null($giifReportListFilter->sortBy)){
            $qb->orderBy('giif_report.'.$giifReportListFilter->sortBy, $giifReportListFilter->sortType);
        }
        if($giifReportListFilter->pageSize > 0){
            $qb->setFirstResult($giifReportListFilter->pageSize * ($giifReportListFilter->page - 1));
            $qb->setMaxResults($giifReportListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('giif_report');
        $qbTotal->select($qbTotal->expr()->count('giif_report.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $giifReportListFilter->page,
            $giifReportListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param GiifReport $giifReport
     * @return GiifReport
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(GiifReport $giifReport)
    {
        $this->_em->persist($giifReport);
        $this->_em->flush();

        return $giifReport;
    }

    // /**
    //  * @return GiifReport[] Returns an array of GiifReport objects
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
    public function findOneBySomeField($value): ?GiifReport
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
