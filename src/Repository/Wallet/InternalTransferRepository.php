<?php

namespace App\Repository\Wallet;

use App\Entity\Wallet\InternalTransfer;
use App\Manager\ListFilter\InternalTransferListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method InternalTransfer|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternalTransfer|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternalTransfer[]    findAll()
 * @method InternalTransfer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternalTransferRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, InternalTransfer::class);
    }

    public function checkConnection(){
        if($this->_em->getConnection()->ping() === false){
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }

    /**
     * @param InternalTransferListFilter $internalTransferListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(InternalTransferListFilter $internalTransferListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($internalTransferListFilter->id)){
            $criteria->where(Criteria::expr()->eq('internalTransfer.id', $internalTransferListFilter->id));
        }

        if(!is_null($internalTransferListFilter->status)){
            $criteria->andWhere(Criteria::expr()->contains('internalTransfer.status', $internalTransferListFilter->status));
        }

        if(!is_null($internalTransferListFilter->walletId)){
            $criteria->andWhere(Criteria::expr()->eq('internalTransfer.wallet', (int) $internalTransferListFilter->walletId));
        }

        // query for results
        $qb = $this->createQueryBuilder('internalTransfer');
        $qb->addCriteria($criteria);
        if(!is_null($internalTransferListFilter->sortBy)){
            $qb->orderBy('internalTransfer.'.$internalTransferListFilter->sortBy, $internalTransferListFilter->sortType);
        }
        if($internalTransferListFilter->pageSize > 0) {
            $qb->setFirstResult($internalTransferListFilter->pageSize * ($internalTransferListFilter->page - 1));
            $qb->setMaxResults($internalTransferListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('internalTransfer');
        $qbTotal->select($qbTotal->expr()->count('internalTransfer.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $internalTransferListFilter->page,
            $internalTransferListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return InternalTransfer
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(InternalTransfer $internalTransfer)
    {
        $this->_em->persist($internalTransfer);
        $this->_em->flush();

        return $internalTransfer;
    }

    // /**
    //  * @return InternalTransfer[] Returns an array of InternalTransfer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InternalTransfer
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
