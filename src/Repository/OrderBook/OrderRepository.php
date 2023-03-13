<?php

namespace App\Repository\OrderBook;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\ListFilter\OrderListFilter;
use App\Manager\ListManager\Paginator;
use App\Model\PriceInterface;
use App\Repository\CurrencyPairRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function checkConnection(){
        if($this->_em->getConnection()->ping() === false){
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }

    /**
     * @param OrderListFilter $orderListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(OrderListFilter $orderListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($orderListFilter->id)){
            $criteria->where(Criteria::expr()->eq('the_order.id', $orderListFilter->id));
        }

        if(!is_null($orderListFilter->type)){
            $criteria->andWhere(Criteria::expr()->eq('the_order.type', $orderListFilter->type));
        }

        if(!is_null($orderListFilter->status)){
            $criteria->andwhere(Criteria::expr()->eq('the_order.status', $orderListFilter->status));
        }

        if(!is_null($orderListFilter->isFilled)){
            $criteria->andwhere(Criteria::expr()->eq('the_order.isFilled', (bool) $orderListFilter->isFilled));
        }

        if(!is_null($orderListFilter->userId)){
            $criteria->andWhere(Criteria::expr()->eq('the_order.user', (int) $orderListFilter->userId));
        }

        if(!is_null($orderListFilter->currencyPairId)){
            $criteria->andwhere(Criteria::expr()->eq('the_order.currencyPair', (int) $orderListFilter->currencyPairId));
        }

        // query for results
        $qb = $this->createQueryBuilder('the_order');
        $qb->addCriteria($criteria);
        if(!is_null($orderListFilter->sortBy)){
            $qb->orderBy('the_order.'.$orderListFilter->sortBy, $orderListFilter->sortType);
        }
        if($orderListFilter->pageSize > 0){
            $qb->setFirstResult($orderListFilter->pageSize * ($orderListFilter->page - 1));
            $qb->setMaxResults($orderListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('the_order');
        $qbTotal->select($qbTotal->expr()->count('the_order.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $orderListFilter->page,
            $orderListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @return array|null
     */
    public function findRejectedForRelease() : ?array
    {
        $query = $this->_em->createQuery("
        SELECT o
        FROM App:OrderBook\Order o
        WHERE o.amountBlocked > 0 AND o.status = :statusRejected
        ");
        $query->setParameter('statusRejected', Order::STATUS_REJECTED);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * Find hedge pending order for passed $order
     *
     * @param Order $order
     * @return Order|null
     * @throws AppException
     */
    public function findHedgeOrder(Order $order) : ?Order
    {
        if(!$order->isTypeAllowed($order->getType())) throw new AppException('Type not allowed');

        $query = $this->_em->createQuery("
            SELECT o
            FROM App:OrderBook\Order o
            LEFT JOIN o.currencyPair cp 
            WHERE 
                o.status = :statusPending AND 
                o.isFilled = false AND 
                cp.id = :currencyPairId AND 
                o.type = :hedgeType AND
                o.user = :userId
        ");
        $query->setParameter('statusPending', Order::STATUS_PENDING);
        $query->setParameter('currencyPairId', $order->getCurrencyPair()->getId());
        $query->setParameter('hedgeType', $order->hedgeType());
        $query->setParameter('userId', $order->getUser()->getId());
        $query->setMaxResults(1);

        $result = $query->getResult();

        if(count($result) > 0) return $result[0];

        return null;
    }

    /**
     * Find currencyPairs and number of orders for each pair
     *
     * @return array|null
     */
    public function findPairOrders() : ?array
    {
        // MYSQL ONLY_FULL_GROUP_BY

        $query = $this->_em->createQuery("
            SELECT IDENTITY(o.currencyPair) as currencyPairId, count(o) as orders
            FROM App:OrderBook\Order o
            GROUP BY o.currencyPair          
        ");
        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param bool $arrayResult
     * @return array|null
     */
    public function findUserPendingOrders(int $userId, int $limit = 10, bool $arrayResult = false) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT o
            FROM App:OrderBook\Order o
            LEFT JOIN o.user ou  
            WHERE 
                (o.status = :statusPending OR o.status = :statusNew) AND 
                o.isFilled = false AND 
                ou.id = :userId
            ORDER BY o.id DESC
        ");
        $query->setParameter('statusNew', Order::STATUS_NEW);
        $query->setParameter('statusPending', Order::STATUS_PENDING);
        $query->setParameter('userId', $userId);
        $query->setMaxResults($limit);

        if($arrayResult){
            $result = $query->getArrayResult();
        }else{
            $result = $query->getResult();
        }

        if(count($result) > 0) return $result;

        return null;
    }

//    /**
//     * @param int $userId
//     * @param int $currencyPairId
//     * @param int|null $limit
//     * @param bool $arrayResult
//     * @return array|null
//     */
//    public function findUserPairPendingOrders(int $userId, int $currencyPairId, int $limit = null, bool $arrayResult = false) : ?array
//    {
//        $query = $this->_em->createQuery("
//            SELECT o
//            FROM App:OrderBook\Order o
//            LEFT JOIN o.user ou
//            WHERE
//                (o.status = :statusPending OR o.status = :statusNew) AND
//                o.isFilled = false AND
//                ou.id = :userId AND
//                o.currencyPair = :currencyPairId
//            ORDER BY o.id DESC
//        ");
//        $query->setParameter('statusNew', Order::STATUS_NEW);
//        $query->setParameter('statusPending', Order::STATUS_PENDING);
//        $query->setParameter('userId', $userId);
//        $query->setParameter('currencyPairId', $currencyPairId);
//        if(!is_null($limit)){
//            $query->setMaxResults($limit);
//        }
//
//        if($arrayResult){
//            $result = $query->getArrayResult();
//        }else{
//            $result = $query->getResult();
//        }
//
//        if(count($result) > 0) return $result;
//
//        return null;
//    }

    /**
     * @param Wallet $wallet
     * @param int $limit
     * @return array|null
     */
    public function findWalletPendingOrders(Wallet $wallet, int $limit = 10) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT o
            FROM App:OrderBook\Order o  
            WHERE 
                (o.status = :statusPending OR o.status = :statusNew) AND 
                o.isFilled = false AND
                (o.baseCurrencyWallet = :walletId OR o.quotedCurrencyWallet =:walletId)
            ORDER BY o.id DESC
        ");
        $query->setParameter('statusNew', Order::STATUS_NEW);
        $query->setParameter('statusPending', Order::STATUS_PENDING);
        $query->setParameter('walletId', $wallet->getId());
        $query->setMaxResults($limit);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param int $orderType
     * @param float $limitPrice
     * @return array|null
     */
    public function findLiquidity(CurrencyPair $currencyPair, int $orderType, float $limitPrice) : ?array
    {
        if($orderType === Order::TYPE_BUY){
            $dqlWhere = 'o.limitPrice >= :limitPrice AND';
            $dqlOrderBy = 'ORDER BY o.limitPrice DESC, o.id ASC';
        }elseif($orderType === Order::TYPE_SELL){
            $dqlWhere = 'o.limitPrice <= :limitPrice AND';
            $dqlOrderBy = 'ORDER BY o.limitPrice ASC, o.id ASC';
        }else{
            return null;
        }

        $query = $this->_em->createQuery("
            SELECT o
            FROM App:OrderBook\Order o
            LEFT JOIN o.currencyPair cp
            WHERE
                o.type = :orderType AND
                o.status = :status AND
                ".$dqlWhere."
                cp.id = :currencyPairId
            ".$dqlOrderBy."
        ");
        $query->setParameter('orderType', $orderType);
        $query->setParameter('status', Order::STATUS_PENDING);
        $query->setParameter('limitPrice', $limitPrice);
        $query->setParameter('currencyPairId', $currencyPair->getId());

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * Load liquidity limit price for instant orders
     *
     * @param CurrencyPair $currencyPair
     * @param int $orderType
     * @param $requitedAmount
     * @return string|null
     */
    public function findLiquidityLimitPrice(CurrencyPair $currencyPair, int $orderType, $requitedAmount) : ?string
    {
        if($orderType === Order::TYPE_BUY){
            $dqlOrderBy = 'ORDER BY o.limitPrice DESC, o.id ASC';
        }elseif($orderType === Order::TYPE_SELL){
            $dqlOrderBy = 'ORDER BY o.limitPrice ASC, o.id ASC';
        }else{
            return null;
        }

        $query = $this->_em->createQuery("
            SELECT o.amount, o.amountFilled, o.limitPrice
            FROM App:OrderBook\Order o
            LEFT JOIN o.currencyPair cp
            WHERE
                o.type = :orderType AND
                o.status = :status AND
                cp.id = :currencyPairId
            ".$dqlOrderBy."
        ");
        $query->setParameter('orderType', $orderType);
        $query->setParameter('status', Order::STATUS_PENDING);
        $query->setParameter('currencyPairId', $currencyPair->getId());

        $result = $query->getResult();
        if($result){
            $limitPrice = null;
            $totalFreeAmount = 0;

            foreach($result as $item){
//                $totalFreeAmount = $totalFreeAmount + ($item['amount'] - $item['amountFilled']);
//                if($totalFreeAmount >= $requitedAmount){
//                    return $item['limitPrice'];
//                }

                $a = bcsub($item['amount'], $item['amountFilled'], PriceInterface::BC_SCALE);
                $totalFreeAmount = bcadd($totalFreeAmount, $a, PriceInterface::BC_SCALE);

                $comp = bccomp($totalFreeAmount, $requitedAmount, PriceInterface::BC_SCALE);
                if($comp === 0 || $comp === 1){
                    return $item['limitPrice'];
                }
            }

            return $limitPrice;
        }

        return null;
    }

    /**
     * @return int|null
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOffers24h() : ?int
    {
        $minDate = new \DateTime('now');
        $minDate->format('-24 hours');

        $query = $this->_em->createQuery("
            SELECT count(o) as total
            FROM App:OrderBook\Order o    
            WHERE 
                o.type = :typeSell AND
                o.createdAt > :minDate
            ORDER BY o.limitPrice DESC
        ");
        $query->setParameter('typeSell', Order::TYPE_SELL);
        $query->setParameter('minDate', $minDate);

        $result = $query->getOneOrNullResult();
        if(isset($result['total'])) return (int) $result['total'];

        return null;
    }

    /**
     * @return int|null
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBids24h() : ?int
    {
        $minDate = new \DateTime('now');
        $minDate->format('-24 hours');

        $query = $this->_em->createQuery("
            SELECT count(o) as total
            FROM App:OrderBook\Order o    
            WHERE 
                o.type = :typeBuy AND
                o.createdAt > :minDate
            ORDER BY o.limitPrice DESC
        ");
        $query->setParameter('typeBuy', Order::TYPE_BUY);
        $query->setParameter('minDate', $minDate);

        $result = $query->getOneOrNullResult();
        if(isset($result['total'])) return (int) $result['total'];

        return null;
    }

//    /**
//     * Find best offers for the order book
//     *
//     * @param CurrencyPair $currencyPair
//     * @param int $limit
//     * @return array|null
//     */
//    public function findOffers(CurrencyPair $currencyPair, int $limit = 100) : ?array
//    {
//        $query = $this->_em->createQuery("
//            SELECT o, cp
//            FROM App:OrderBook\Order o
//            LEFT JOIN o.currencyPair cp
//            WHERE
//                o.type = :typeSell AND
//                o.status = :statusPending AND
//                o.isFilled = false AND
//                cp.id = :currencyPairId
//            ORDER BY o.limitPrice ASC
//        ");
//        $query->setParameter('typeSell', Order::TYPE_SELL);
//        $query->setParameter('statusPending', Order::STATUS_PENDING);
//        $query->setParameter('currencyPairId', $currencyPair->getId());
//        $query->setMaxResults($limit);
//
//        $result = $query->getResult();
//
//        if(count($result) > 0) return $result;
//
//        return null;
//    }

    /**
     * Find best offers for the order book
     *
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return array|null
     */
    public function findOffersArray(CurrencyPair $currencyPair, int $limit = 100) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT o, cp, bc, qc
            FROM App:OrderBook\Order o
            LEFT JOIN o.currencyPair cp  
            LEFT JOIN cp.baseCurrency bc  
            LEFT JOIN cp.quotedCurrency qc
            WHERE 
                o.type = :typeSell AND 
                o.status = :statusPending AND 
                o.isFilled = false AND 
                cp.id = :currencyPairId
            GROUP BY o.id
            ORDER BY o.limitPrice ASC
        ");
        $query->setParameter('typeSell', Order::TYPE_SELL);
        $query->setParameter('statusPending', Order::STATUS_PENDING);
        $query->setParameter('currencyPairId', $currencyPair->getId());
//        $query->setMaxResults($limit);

        $result = $query->getArrayResult();

        if(count($result) > 0) return $result;

        return null;
    }

//    /**
//     * Find best bids for the order book
//     *
//     * @param CurrencyPair $currencyPair
//     * @param int $limit
//     * @return array|null
//     */
//    public function findBids(CurrencyPair $currencyPair, int $limit = 100) : ?array
//    {
//        $query = $this->_em->createQuery("
//            SELECT o, cp
//            FROM App:OrderBook\Order o
//            LEFT JOIN o.currencyPair cp
//            WHERE
//                o.type = :typeBuy AND
//                o.status = :statusPending AND
//                o.isFilled = false AND
//                cp.id = :currencyPairId
//            ORDER BY o.limitPrice DESC
//        ");
//        $query->setParameter('typeBuy', Order::TYPE_BUY);
//        $query->setParameter('statusPending', Order::STATUS_PENDING);
//        $query->setParameter('currencyPairId', $currencyPair->getId());
//        $query->setMaxResults($limit);
//
//        $result = $query->getResult();
//
//        if(count($result) > 0) return $result;
//
//        return null;
//    }

    /**
     * Find best bids for the order book
     *
     * @param CurrencyPair $currencyPair
     * @param int $limit
     * @return array|null
     */
    public function findBidsArray(CurrencyPair $currencyPair, int $limit = 100) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT o, cp, bc, qc
            FROM App:OrderBook\Order o  
            LEFT JOIN o.currencyPair cp  
            LEFT JOIN cp.baseCurrency bc  
            LEFT JOIN cp.quotedCurrency qc  
            WHERE 
                o.type = :typeBuy AND 
                o.status = :statusPending AND 
                o.isFilled = false AND 
                cp.id = :currencyPairId
            GROUP BY o.id
            ORDER BY o.limitPrice DESC
        ");
        $query->setParameter('typeBuy', Order::TYPE_BUY);
        $query->setParameter('statusPending', Order::STATUS_PENDING);
        $query->setParameter('currencyPairId', $currencyPair->getId());
//        $query->setMaxResults($limit);

        $result = $query->getArrayResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Order $order)
    {
        $this->_em->persist($order);
        $this->_em->flush();

        return $order;
    }

    /**
     * @param Order $order
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Order $order){
        $this->_em->remove($order);
        $this->_em->flush();

        return true;
    }

    /**
     * @param Order $order
     */
    public function detach(Order $order){
        $this->_em->detach($order);
    }

    // /**
    //  * @return Order[] Returns an array of Order objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
