<?php

namespace App\Repository\Configuration;

use App\Entity\Configuration\VoterRole;
use App\Manager\ListFilter\VoterRoleListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method VoterRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method VoterRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method VoterRole[]    findAll()
 * @method VoterRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoterRoleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, VoterRole::class);
    }

    /**
     * @param VoterRoleListFilter $voterRoleListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(VoterRoleListFilter $voterRoleListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($voterRoleListFilter->module)){
            $criteria->where(Criteria::expr()->contains('voterRole.module', $voterRoleListFilter->module));
        }

        if(!is_null($voterRoleListFilter->action)){
            $criteria->andWhere(Criteria::expr()->contains('voterRole.action', $voterRoleListFilter->action));
        }

        // query for results
        $qb = $this->createQueryBuilder('voterRole');
        $qb->addCriteria($criteria);
        if(!is_null($voterRoleListFilter->sortBy)){
            $qb->orderBy('voterRole.'.$voterRoleListFilter->sortBy, $voterRoleListFilter->sortType);
        }
        if($voterRoleListFilter->pageSize > 0) {
            $qb->setFirstResult($voterRoleListFilter->pageSize * ($voterRoleListFilter->page - 1));
            $qb->setMaxResults($voterRoleListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('voterRole');
        $qbTotal->select($qbTotal->expr()->count('voterRole.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $voterRoleListFilter->page,
            $voterRoleListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param string $module
     * @param string $action
     * @return bool
     */
    public function voterRoleExists(string $module, string $action) : bool
    {
        /** @var VoterRole $voterRole */
        $voterRole = $this->findOneBy([
            'module' => $module,
            'action' => $action
        ]);

        if($voterRole instanceof VoterRole){
            return true;
        }

        return false;
    }

    /**
     * @param VoterRole $voterRole
     * @return VoterRole
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(VoterRole $voterRole)
    {
        $this->_em->persist($voterRole);
        $this->_em->flush();

        return $voterRole;
    }

    // /**
    //  * @return VoterRole[] Returns an array of VoterRole objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?VoterRole
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
