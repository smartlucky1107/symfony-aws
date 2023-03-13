<?php

namespace App\Repository\POS;

use App\Entity\POS\POSOrder;
use App\Exception\AppException;
use App\Manager\ListFilter\POSOrderListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method POSOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method POSOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method POSOrder[]    findAll()
 * @method POSOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class POSOrderRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, POSOrder::class);
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function findNewForProcessing() : ?array
    {
        $query = $this->_em->createQuery("
            SELECT pOrder
            FROM App:POS\POSOrder pOrder
            WHERE 
                pOrder.status = :newStatus
            ORDER BY pOrder.id ASC
        ");
        $query->setParameter('newStatus', POSOrder::STATUS_NEW);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function findInitiatedExpired() : ?array
    {
        $nowDate = new \DateTime('now');

        $query = $this->_em->createQuery("
            SELECT pOrder
            FROM App:POS\POSOrder pOrder
            WHERE 
                pOrder.status = :statusInit AND pOrder.expiresAt < :nowDate
            ORDER BY pOrder.id ASC
        ");
        $query->setParameter('statusInit', POSOrder::STATUS_INIT);
        $query->setParameter('nowDate', $nowDate);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param int $id
     * @return POSOrder
     * @throws AppException
     */
    public function findOrException(int $id){
        /** @var POSOrder $POSOrder */
        $POSOrder = $this->find($id);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('error.pos_order.not_found');

        return $POSOrder;
    }

    /**
     * @param POSOrderListFilter $POSOrderListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(POSOrderListFilter $POSOrderListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($POSOrderListFilter->id)){
            $criteria->where(Criteria::expr()->eq('pos_order.id', $POSOrderListFilter->id));
        }

        if(!is_null($POSOrderListFilter->status)){
            $criteria->andwhere(Criteria::expr()->eq('pos_order.status', $POSOrderListFilter->status));
        }

        if(!is_null($POSOrderListFilter->workspaceId)){
            $criteria->andWhere(Criteria::expr()->eq('pos_order.workspace', (int) $POSOrderListFilter->workspaceId));
        }

        if(!is_null($POSOrderListFilter->currencyPairId)){
            $criteria->andwhere(Criteria::expr()->eq('pos_order.currencyPair', (int) $POSOrderListFilter->currencyPairId));
        }

        // query for results
        $qb = $this->createQueryBuilder('pos_order');
        $qb->addCriteria($criteria);
        if(!is_null($POSOrderListFilter->sortBy)){
            $qb->orderBy('pos_order.'.$POSOrderListFilter->sortBy, $POSOrderListFilter->sortType);
        }
        if($POSOrderListFilter->pageSize > 0){
            $qb->setFirstResult($POSOrderListFilter->pageSize * ($POSOrderListFilter->page - 1));
            $qb->setMaxResults($POSOrderListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('pos_order');
        $qbTotal->select($qbTotal->expr()->count('pos_order.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $POSOrderListFilter->page,
            $POSOrderListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(POSOrder $POSOrder)
    {
        $this->_em->persist($POSOrder);
        $this->_em->flush();

        return $POSOrder;
    }

    // /**
    //  * @return POSOrder[] Returns an array of POSOrder objects
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
    public function findOneBySomeField($value): ?POSOrder
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
