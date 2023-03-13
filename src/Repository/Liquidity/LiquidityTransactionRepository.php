<?php

namespace App\Repository\Liquidity;

use App\Entity\CurrencyPair;
use App\Entity\Liquidity\LiquidityTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LiquidityTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LiquidityTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LiquidityTransaction[]    findAll()
 * @method LiquidityTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LiquidityTransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LiquidityTransaction::class);
    }

    /**
     * @param CurrencyPair $currencyPair
     * @param \DateTime $from
     * @param \DateTime $to
     * @param int $limit
     * @return array|null
     */
    public function findForCurrencyPairBetweenDates(CurrencyPair $currencyPair, \DateTime $from, \DateTime $to, int $limit = 50) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT liqt as liquidityTransaction, liqtOrder.id as orderId
            FROM App\Entity\Liquidity\LiquidityTransaction liqt
            LEFT JOIN liqt.order liqtOrder  
            WHERE
                liqtOrder.currencyPair = :currencyPairId AND
                liqtOrder.createdAt >= :fromDate AND 
                liqtOrder.createdAt <= :toDate
            ORDER BY liqt.id ASC
        ");
        $query->setParameter('currencyPairId', $currencyPair->getId());
        $query->setParameter('fromDate', $from);
        $query->setParameter('toDate', $to);
        $query->setMaxResults($limit);

        $result = $query->getArrayResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param int $limit
     * @return array|null
     */
    public function findForExternalRealization(int $limit = 50) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT liqt
            FROM App\Entity\Liquidity\LiquidityTransaction liqt 
            WHERE
                liqt.marketType = :marketTypeExternal AND
                liqt.realized = false
            ORDER BY liqt.id ASC
        ");
        $query->setParameter('marketTypeExternal', LiquidityTransaction::MARKET_TYPE_EXTERNAL);
        $query->setMaxResults($limit);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param LiquidityTransaction $liquidityTransaction
     * @return LiquidityTransaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(LiquidityTransaction $liquidityTransaction)
    {
        $this->_em->persist($liquidityTransaction);
        $this->_em->flush();

        return $liquidityTransaction;
    }

    // /**
    //  * @return LiquidityTransaction[] Returns an array of LiquidityTransaction objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LiquidityTransaction
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
