<?php

namespace App\Repository;

use App\Entity\CheckoutOrder;
use App\Manager\ListFilter\CheckoutOrderListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CheckoutOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method CheckoutOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method CheckoutOrder[]    findAll()
 * @method CheckoutOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CheckoutOrderRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CheckoutOrder::class);
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function findNewExpired() : ?array
    {
        $nowDate = new \DateTime('now');

        $query = $this->_em->createQuery("
            SELECT cOrder
            FROM App:CheckoutOrder cOrder
            WHERE 
                (cOrder.status = :statusPending or cOrder.status = :statusPaymentInit) AND cOrder.expiresAt < :nowDate
            ORDER BY cOrder.id ASC
        ");
        $query->setParameter('statusPending', CheckoutOrder::STATUS_PENDING);
        $query->setParameter('statusPaymentInit', CheckoutOrder::STATUS_PAYMENT_INIT);
        $query->setParameter('nowDate', $nowDate);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function findPaidForProcessing() : ?array
    {
        $query = $this->_em->createQuery("
            SELECT pOrder
            FROM App:CheckoutOrder pOrder
            WHERE 
                pOrder.status = :paidStatus
            ORDER BY pOrder.id ASC
        ");
        $query->setParameter('paidStatus', CheckoutOrder::STATUS_PAYMENT_SUCCESS);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param CheckoutOrderListFilter $checkoutOrderListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(CheckoutOrderListFilter $checkoutOrderListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($checkoutOrderListFilter->id)){
            $criteria->where(Criteria::expr()->eq('checkout_order.id', $checkoutOrderListFilter->id));
        }

        if(!is_null($checkoutOrderListFilter->type)){
            $criteria->andWhere(Criteria::expr()->eq('checkout_order.type', $checkoutOrderListFilter->type));
        }

        if(!is_null($checkoutOrderListFilter->status)){
            $criteria->andwhere(Criteria::expr()->eq('checkout_order.status', $checkoutOrderListFilter->status));
        }

        if(!is_null($checkoutOrderListFilter->userId)){
            $criteria->andWhere(Criteria::expr()->eq('checkout_order.user', (int) $checkoutOrderListFilter->userId));
        }

        if(!is_null($checkoutOrderListFilter->currencyPairId)){
            $criteria->andwhere(Criteria::expr()->eq('checkout_order.currencyPair', (int) $checkoutOrderListFilter->currencyPairId));
        }

        if(!is_null($checkoutOrderListFilter->from)){
            $criteria->andwhere(Criteria::expr()->gt('checkout_order.createdAt', $checkoutOrderListFilter->from));
        }

        if(!is_null($checkoutOrderListFilter->to)){
            $criteria->andwhere(Criteria::expr()->lt('checkout_order.createdAt', $checkoutOrderListFilter->to));
        }

        // query for results
        $qb = $this->createQueryBuilder('checkout_order');
        $qb->addCriteria($criteria);
        if(!is_null($checkoutOrderListFilter->sortBy)){
            $qb->orderBy('checkout_order.'.$checkoutOrderListFilter->sortBy, $checkoutOrderListFilter->sortType);
        }
        if($checkoutOrderListFilter->pageSize > 0){
            $qb->setFirstResult($checkoutOrderListFilter->pageSize * ($checkoutOrderListFilter->page - 1));
            $qb->setMaxResults($checkoutOrderListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('checkout_order');
        $qbTotal->select($qbTotal->expr()->count('checkout_order.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $checkoutOrderListFilter->page,
            $checkoutOrderListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(CheckoutOrder $checkoutOrder)
    {
        $this->_em->persist($checkoutOrder);
        $this->_em->flush();

        return $checkoutOrder;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array|null
     */
    public function findBetweenDates(?\DateTime $from, ?\DateTime $to, ?int $status = null) : ?array
    {
        $q = "
            SELECT SUM(checkoutOrder.totalPaymentValue) as totalPaymentValue, SUM(checkoutOrder.checkoutFee) as checkoutFee
            FROM App:CheckoutOrder checkoutOrder
        ";

        $where = "WHERE ";
        $whereUsed = false;

        if($from && $to) {
            $where .= "checkoutOrder.createdAt >= :fromDate AND checkoutOrder.createdAt <= :toDate";
            $whereUsed = true;
        }

        if($from && !$to) {
            $where .= "checkoutOrder.createdAt >= :fromDate";
            $whereUsed = true;
        }

        if(!$from && $to) {
            $where .= "checkoutOrder.createdAt <= :toDate";
            $whereUsed = true;
        }

        if($status) {
            if($whereUsed) $where .= " AND ";
            $where .= "checkoutOrder.status = :status";
            $whereUsed = true;
        }

        if($whereUsed) {
            $q .= $where;
        }

        $query = $this->_em->createQuery($q);

        if($from) {
            $query->setParameter('fromDate', $from);
        }

        if($to) {
            $query->setParameter('toDate', $to);
        }

        if($status) {
            $query->setParameter('status', $status);
        }

        $result = $query->getSingleResult();

        if(count($result) > 0) return $result;

        return null;
    }

    // /**
    //  * @return CheckoutOrder[] Returns an array of CheckoutOrder objects
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
    public function findOneBySomeField($value): ?CheckoutOrder
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
