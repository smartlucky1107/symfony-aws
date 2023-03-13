<?php

namespace App\Repository\POS;

use App\Entity\POS\Employee;
use App\Manager\ListFilter\EmployeeListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Employee|null find($id, $lockMode = null, $lockVersion = null)
 * @method Employee|null findOneBy(array $criteria, array $orderBy = null)
 * @method Employee[]    findAll()
 * @method Employee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    /**
     * @param EmployeeListFilter $employeeListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(EmployeeListFilter $employeeListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($employeeListFilter->id)){
            $criteria->where(Criteria::expr()->eq('employee.id', $employeeListFilter->id));
        }

        if(!is_null($employeeListFilter->firstName)){
            $criteria->andwhere(Criteria::expr()->eq('employee.firstName', $employeeListFilter->firstName));
        }

        if(!is_null($employeeListFilter->lastName)){
            $criteria->andwhere(Criteria::expr()->eq('employee.lastName', $employeeListFilter->lastName));
        }

        if(!is_null($employeeListFilter->workspaceId)){
            $criteria->andWhere(Criteria::expr()->eq('employee.workspace', (int) $employeeListFilter->workspaceId));
        }

        // query for results
        $qb = $this->createQueryBuilder('employee');
        $qb->addCriteria($criteria);
        if(!is_null($employeeListFilter->sortBy)){
            $qb->orderBy('employee.'.$employeeListFilter->sortBy, $employeeListFilter->sortType);
        }
        if($employeeListFilter->pageSize > 0){
            $qb->setFirstResult($employeeListFilter->pageSize * ($employeeListFilter->page - 1));
            $qb->setMaxResults($employeeListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('employee');
        $qbTotal->select($qbTotal->expr()->count('employee.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $employeeListFilter->page,
            $employeeListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param Employee $employee
     * @return Employee
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Employee $employee)
    {
        $this->_em->persist($employee);
        $this->_em->flush();

        return $employee;
    }

    // /**
    //  * @return Employee[] Returns an array of Employee objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Employee
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
