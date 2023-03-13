<?php

namespace App\Repository\Configuration;

use App\Entity\Configuration\SystemTag;
use App\Manager\ListFilter\SystemTagListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SystemTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemTag[]    findAll()
 * @method SystemTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SystemTagRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SystemTag::class);
    }

    /**
     * @return array|null
     */
    public function findActivated() : ?array
    {
        return $this->findBy(['activated' => true]);
    }

    /**
     * @param SystemTagListFilter $systemTagListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(SystemTagListFilter $systemTagListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($systemTagListFilter->type)){
            $criteria->where(Criteria::expr()->contains('systemTag.type', $systemTagListFilter->type));
        }

        // query for results
        $qb = $this->createQueryBuilder('systemTag');
        $qb->addCriteria($criteria);
        if(!is_null($systemTagListFilter->sortBy)){
            $qb->orderBy('systemTag.'.$systemTagListFilter->sortBy, $systemTagListFilter->sortType);
        }
        if($systemTagListFilter->pageSize > 0) {
            $qb->setFirstResult($systemTagListFilter->pageSize * ($systemTagListFilter->page - 1));
            $qb->setMaxResults($systemTagListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('systemTag');
        $qbTotal->select($qbTotal->expr()->count('systemTag.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $systemTagListFilter->page,
            $systemTagListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param SystemTag $systemTag
     * @return SystemTag
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(SystemTag $systemTag)
    {
        $this->_em->persist($systemTag);
        $this->_em->flush();

        return $systemTag;
    }

    // /**
    //  * @return SystemTag[] Returns an array of SystemTag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SystemTag
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
