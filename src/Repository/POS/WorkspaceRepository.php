<?php

namespace App\Repository\POS;

use App\Entity\POS\Workspace;
use App\Entity\User;
use App\Manager\ListFilter\WorkspaceListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Workspace|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workspace|null findOneBy(array $criteria, array $orderBy = null)
 * @method Workspace[]    findAll()
 * @method Workspace[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkspaceRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Workspace::class);
    }

    /**
     * @param WorkspaceListFilter $workspaceListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(WorkspaceListFilter $workspaceListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($workspaceListFilter->id)){
            $criteria->where(Criteria::expr()->eq('workspace.id', $workspaceListFilter->id));
        }

        if(!is_null($workspaceListFilter->name)){
            $criteria->andwhere(Criteria::expr()->eq('workspace.name', $workspaceListFilter->name));
        }

        if(!is_null($workspaceListFilter->userId)){
            $criteria->andWhere(Criteria::expr()->eq('workspace.user', (int) $workspaceListFilter->userId));
        }

        // query for results
        $qb = $this->createQueryBuilder('workspace');
        $qb->addCriteria($criteria);
        if(!is_null($workspaceListFilter->sortBy)){
            $qb->orderBy('workspace.'.$workspaceListFilter->sortBy, $workspaceListFilter->sortType);
        }
        if($workspaceListFilter->pageSize > 0){
            $qb->setFirstResult($workspaceListFilter->pageSize * ($workspaceListFilter->page - 1));
            $qb->setMaxResults($workspaceListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('workspace');
        $qbTotal->select($qbTotal->expr()->count('workspace.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $workspaceListFilter->page,
            $workspaceListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param string $name
     * @param User $user
     * @return Workspace|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByNameAndUser(string $name, User $user) : ?Workspace
    {
        $query = $this->_em->createQuery("
            SELECT workspace
            FROM App:POS\Workspace workspace
            WHERE workspace.user = :userId AND workspace.name = :workspaceName
        ");
        $query->setParameter('workspaceName', $name);
        $query->setParameter('userId', $user->getId());
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * @param Workspace $workspace
     * @return Workspace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Workspace $workspace)
    {
        $this->_em->persist($workspace);
        $this->_em->flush();

        return $workspace;
    }

    // /**
    //  * @return Workspace[] Returns an array of Workspace objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Workspace
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
