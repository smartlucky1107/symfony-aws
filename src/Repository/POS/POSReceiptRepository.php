<?php

namespace App\Repository\POS;

use App\Entity\POS\Employee;
use App\Entity\POS\POSReceipt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method POSReceipt|null find($id, $lockMode = null, $lockVersion = null)
 * @method POSReceipt|null findOneBy(array $criteria, array $orderBy = null)
 * @method POSReceipt[]    findAll()
 * @method POSReceipt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class POSReceiptRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, POSReceipt::class);
    }

    /**
     * @param Employee $employee
     * @return POSReceipt|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findForPrintByEmployee(Employee $employee) : ?POSReceipt
    {
        $query = $this->_em->createQuery("
            SELECT receipt
            FROM App:POS\POSReceipt receipt
            LEFT JOIN receipt.POSOrder posOrder
            WHERE posOrder.employee = :employeeId AND receipt.status = :statusNew 
        ");
        $query->setParameter('employeeId', $employee->getId());
        $query->setParameter('statusNew', POSReceipt::STATUS_NEW);
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * Find currently printing receipt for Employee
     *
     * @param Employee $employee
     * @return POSReceipt|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findPrintingByEmployee(Employee $employee) : ?POSReceipt
    {
        $query = $this->_em->createQuery("
            SELECT receipt
            FROM App:POS\POSReceipt receipt
            LEFT JOIN receipt.POSOrder posOrder
            WHERE posOrder.employee = :employeeId AND receipt.status = :statusNew 
        ");
        $query->setParameter('employeeId', $employee->getId());
        $query->setParameter('statusNew', POSReceipt::STATUS_PRINTING);
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * @param POSReceipt $POSReceipt
     * @return POSReceipt
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(POSReceipt $POSReceipt)
    {
        $this->_em->persist($POSReceipt);
        $this->_em->flush();

        return $POSReceipt;
    }

    // /**
    //  * @return POSReceipt[] Returns an array of POSReceipt objects
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
    public function findOneBySomeField($value): ?POSReceipt
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
