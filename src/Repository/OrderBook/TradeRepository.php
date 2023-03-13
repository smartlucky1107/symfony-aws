<?php

namespace App\Repository\OrderBook;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\ListFilter\TradeListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Trade|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trade|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trade[]    findAll()
 * @method Trade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Trade::class);
    }

/*
TODO
    SELECT SUM(market.trade.amount)
    FROM market.trade
    LEFT JOIN market.the_order orderBuy ON orderBuy.id = trade.order_buy_id
    LEFT JOIN market.the_order orderSell ON orderSell.id = trade.order_sell_id
    WHERE
    orderBuy.quoted_currency_wallet_id = 92750 AND orderSell.base_currency_wallet_id = 78392;
 */
    const RESULT_MODE_SUM_AMOUNT = 'sumAmount';
    const RESULT_MODE_SUM_AMOUNT_PRICE = 'sumAmountPrice';

    /**
     * @param int $id
     * @return Trade
     * @throws AppException
     */
    public function findOrException(int $id){
        /** @var Trade $trade */
        $trade = $this->find($id);
        if(!($trade instanceof Trade)) throw new AppException('Trade not found');

        return $trade;
    }

    /**
     * @param User $user
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array|null
     * @throws \Exception
     */
    public function getTradedByUser(User $user, \DateTime $from = null, \DateTime $to = null) : ?array
    {
        $minDate = new \DateTime('2020-06-01');

        $whereFrom = '';
        if($from instanceof \DateTime){
            if($from < $minDate){ $from = $minDate; }
            $whereFrom = ' AND trade.createdAt >= :fromDate';
        }else{
            $from = $minDate;
        }

        $whereTo = '';
        if($to instanceof \DateTime){
            $whereTo = ' AND trade.createdAt <= :toDate';
        }

        $query = $this->_em->createQuery("
            SELECT trade
            FROM App:OrderBook\Trade trade
            LEFT JOIN trade.orderSell tos
            LEFT JOIN trade.orderBuy tob
            LEFT JOIN tos.currencyPair cp
            LEFT JOIN cp.baseCurrency cpBase
            LEFT JOIN cp.quotedCurrency cpQuoted
            WHERE 
                (tos.user = :userId OR tob.user = :userId) " . $whereFrom . " " . $whereTo . "
        ");
        $query->setParameter('userId', $user->getId());
        if($from instanceof \DateTime){
            $query->setParameter('fromDate', $from);
        }
        if($to instanceof \DateTime){
            $query->setParameter('toDate', $to);
        }

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param User $user
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array|null
     * @throws \Exception
     */
    public function getTradedByUserGroupedByPair(User $user, \DateTime $from = null, \DateTime $to = null) : ?array
    {
        $minDate = new \DateTime('2020-06-01');

        $whereFrom = '';
        if($from instanceof \DateTime){
            if($from < $minDate){ $from = $minDate; }
            $whereFrom = ' AND trade.createdAt >= :fromDate';
        }else{
            $from = $minDate;
            $whereFrom = ' AND trade.createdAt >= :fromDate';
        }

        $whereTo = '';
        if($to instanceof \DateTime){
            $whereTo = ' AND trade.createdAt <= :toDate';
        }

        $query = $this->_em->createQuery("
            SELECT 
                SUM(trade.amount) as volume,  
                SUM(trade.feeBid) as value, 
                cpBase.fullName as fullName,
                cpBase.shortName as shortName
            FROM App:OrderBook\Trade trade
            LEFT JOIN trade.orderSell tos
            LEFT JOIN trade.orderBuy tob
            LEFT JOIN tos.currencyPair cp
            LEFT JOIN cp.baseCurrency cpBase
            LEFT JOIN cp.quotedCurrency cpQuoted
            WHERE 
                (tos.user = :userId OR tob.user = :userId) " . $whereFrom . " " . $whereTo . "
            GROUP BY cp.id
        ");
        $query->setParameter('userId', $user->getId());
        if($from instanceof \DateTime){
            $query->setParameter('fromDate', $from);
        }
        if($to instanceof \DateTime){
            $query->setParameter('toDate', $to);
        }

        $result = $query->getArrayResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param string $resultMode
     * @param int $sellWalletId
     * @param int|null $buyWalletId
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return string|null
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSoldByWallet(string $resultMode, int $sellWalletId, int $buyWalletId = null, \DateTime $from = null, \DateTime $to = null) : ?string
    {
        if($resultMode === self::RESULT_MODE_SUM_AMOUNT){
            $querySelect = 'SUM(trade.amount)';
        }elseif($resultMode === self::RESULT_MODE_SUM_AMOUNT_PRICE){
            $querySelect = 'SUM(trade.amount * trade.price)';
        }else{
            throw new AppException('Result mode not allowed');
        }

        $queryBuyWallet = '';
        if(!is_null($buyWalletId)) $queryBuyWallet = ' AND tob.quotedCurrencyWallet = :buyWalletId ';

        $queryFrom = '';
        $queryTo = '';

        if($from instanceof \DateTime) $queryFrom = ' AND trade.createdAt >= :fromDate ';
        if($to instanceof \DateTime) $queryTo = ' AND trade.createdAt <= :toDate ';

        $query = $this->_em->createQuery("
            SELECT " . $querySelect . "
            FROM App\Entity\OrderBook\Trade trade 
            LEFT JOIN trade.orderSell tos  
            LEFT JOIN trade.orderBuy tob
            WHERE
                tos.baseCurrencyWallet = :sellWalletId
                " . $queryBuyWallet . " 
                " . $queryFrom . "
                " . $queryTo . "
        ");

        $query->setParameter('sellWalletId', $sellWalletId);
        if(!is_null($buyWalletId)) $query->setParameter('buyWalletId', $buyWalletId);
        if($from instanceof \DateTime) $query->setParameter('fromDate', $from);
        if($to instanceof \DateTime) $query->setParameter('toDate', $to);

        return $query->getSingleScalarResult();
    }

    /**
     * @param TradeListFilter $tradeListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(TradeListFilter $tradeListFilter) : Paginator
    {
        if(!is_null($tradeListFilter->userId)){
            $orderBuyIdWhere = '';
            if(!is_null($tradeListFilter->orderBuyId)){
                $orderBuyIdWhere = 'AND (tob.id =:orderBuyId)';
            }

            $orderSellIdWhere = '';
            if(!is_null($tradeListFilter->orderSellId)){
                $orderSellIdWhere = 'AND (tos.id =:orderSellId)';
            }

            $currencyPairIdWhere = '';
            if(!is_null($tradeListFilter->currencyPairId)){
                $currencyPairIdWhere = 'AND (tob.currencyPair = :currencyPairId OR tos.currencyPair = :currencyPairId)';
            }

            $fromDateWhere = '';
            if(!is_null($tradeListFilter->from)){
                $fromDateWhere = 'AND (trade.createdAt > :fromDate)';
            }

            $toDateWhere = '';
            if(!is_null($tradeListFilter->to)){
                $toDateWhere = 'AND (trade.createdAt < :toDate)';
            }

            $orderSort = '';
            if(!is_null($tradeListFilter->sortBy)){
                $orderSort = ' ORDER BY trade.' . $tradeListFilter->sortBy . ' ' . $tradeListFilter->sortType;
            }

            $query = $this->_em->createQuery('
                SELECT trade 
                FROM App\Entity\OrderBook\Trade trade 
                LEFT JOIN trade.orderSell tos  
                LEFT JOIN trade.orderBuy tob 
                WHERE 
                    (tos.user = :userId OR tob.user = :userId) ' . $orderBuyIdWhere . ' ' . $orderSellIdWhere . ' ' . $currencyPairIdWhere . ' ' . $fromDateWhere . ' ' . $toDateWhere . $orderSort);
            $query->setParameters([
                'userId' => (int) $tradeListFilter->userId,
            ]);
            if(!is_null($tradeListFilter->orderBuyId)){
                $query->setParameter('orderBuyId', (int) $tradeListFilter->orderBuyId);
            }
            if(!is_null($tradeListFilter->orderSellId)){
                $query->setParameter('orderSellId', (int) $tradeListFilter->orderSellId);
            }
            if(!is_null($tradeListFilter->currencyPairId)){
                $query->setParameter('currencyPairId', (int) $tradeListFilter->currencyPairId);
            }
            if(!is_null($tradeListFilter->from)){
                $query->setParameter('fromDate', $tradeListFilter->from);
            }
            if(!is_null($tradeListFilter->to)){
                $query->setParameter('toDate', $tradeListFilter->to);
            }

            if($tradeListFilter->pageSize > 0){
                $query->setFirstResult($tradeListFilter->pageSize * ($tradeListFilter->page - 1));
                $query->setMaxResults($tradeListFilter->pageSize);
            }

            $qbTotal = $this->_em->createQuery('
                SELECT count(trade.id) 
                FROM App\Entity\OrderBook\Trade trade 
                LEFT JOIN trade.orderSell tos  
                LEFT JOIN trade.orderBuy tob 
                WHERE 
                    (tos.user = :userId OR tob.user = :userId) ' . $orderBuyIdWhere . ' ' . $orderSellIdWhere . ' ' . $currencyPairIdWhere . ' ' . $fromDateWhere . ' ' . $toDateWhere);
            $qbTotal->setParameters([
                'userId' => (int) $tradeListFilter->userId,
            ]);
            if(!is_null($tradeListFilter->orderBuyId)){
                $qbTotal->setParameter('orderBuyId', (int) $tradeListFilter->orderBuyId);
            }
            if(!is_null($tradeListFilter->orderSellId)){
                $qbTotal->setParameter('orderSellId', (int) $tradeListFilter->orderSellId);
            }
            if(!is_null($tradeListFilter->currencyPairId)){
                $qbTotal->setParameter('currencyPairId', (int) $tradeListFilter->currencyPairId);
            }
            if(!is_null($tradeListFilter->from)){
                $qbTotal->setParameter('fromDate', $tradeListFilter->from);
            }
            if(!is_null($tradeListFilter->to)){
                $qbTotal->setParameter('toDate', $tradeListFilter->to);
            }

            return new Paginator(
                $tradeListFilter->page,
                $tradeListFilter->pageSize,
                $query->getResult(),
                (int) $qbTotal->getSingleScalarResult()
            );
        }

        $criteria = new Criteria();

        if(!is_null($tradeListFilter->id)){
            $criteria->where(Criteria::expr()->eq('trade.id', $tradeListFilter->id));
        }

        // query for results
        $qb = $this->createQueryBuilder('trade');
        $qb->addCriteria($criteria);
        if(!is_null($tradeListFilter->sortBy)){
            $qb->orderBy('trade.'.$tradeListFilter->sortBy, $tradeListFilter->sortType);
        }
        if($tradeListFilter->pageSize > 0){
            $qb->setFirstResult($tradeListFilter->pageSize * ($tradeListFilter->page - 1));
            $qb->setMaxResults($tradeListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('trade');
        $qbTotal->select($qbTotal->expr()->count('trade.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $tradeListFilter->page,
            $tradeListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param int $orderId
     * @return array|null
     */
    public function findForOrderId(int $orderId) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT t
            FROM App:OrderBook\Trade t
            WHERE 
                t.orderSell = :orderId OR t.orderBuy = :orderId
            ORDER BY t.id DESC
        ");
        $query->setParameter('orderId', $orderId);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

//    /**
//     * @param CurrencyPair $currencyPair
//     * @param \DateTime $afterDate
//     * @return array|null
//     */
//    public function findForCurrencyPairAfterDate(CurrencyPair $currencyPair, \DateTime $afterDate) : ?array
//    {
//        $query = $this->_em->createQuery("
//            SELECT t
//            FROM App:OrderBook\Trade t
//            LEFT JOIN t.orderSell tos
//            LEFT JOIN tos.currencyPair cp
//            WHERE
//                cp.id = :currencyPairId AND
//                t.createdAt > :afterDate
//            ORDER BY t.id ASC
//        ");
//        $query->setParameter('currencyPairId', $currencyPair->getId());
//        $query->setParameter('afterDate', $afterDate);
//
//        $result = $query->getResult();
//
//        if(count($result) > 0) return $result;
//
//        return null;
//    }

    /**
     * @param Trade $trade
     * @return Trade
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Trade $trade)
    {
        $this->_em->persist($trade);
        $this->_em->flush();

        return $trade;
    }

    /**
     * @param Trade $trade
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Trade $trade){
        $this->_em->remove($trade);
        $this->_em->flush();

        return true;
    }

    // /**
    //  * @return Trade[] Returns an array of Trade objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Trade
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
