<?php

namespace App\Repository;

use App\Entity\TradeFeeLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TradeFeeLevel|null find($id, $lockMode = null, $lockVersion = null)
 * @method TradeFeeLevel|null findOneBy(array $criteria, array $orderBy = null)
 * @method TradeFeeLevel[]    findAll()
 * @method TradeFeeLevel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeFeeLevelRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TradeFeeLevel::class);
    }

    /**
     * Find fee level for $tradingVolume
     *
     * @param float $tradingVolume
     * @return bool|TradeFeeLevel
     */
    public function findLevel(float $tradingVolume){
        $query = $this->_em->createQuery("
            SELECT tfl
            FROM App:TradeFeeLevel tfl  
            WHERE tfl.tradingVolume <= :tradingVolume
            ORDER BY tfl.tradingVolume DESC
        ");
        $query->setParameter('tradingVolume', $tradingVolume);
        $query->setMaxResults(1);

        $result = $query->getResult();

        if(count($result) > 0) return $result[0];

        return false;
    }

    // /**
    //  * @return TradeFeeLevel[] Returns an array of TradeFeeLevel objects
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
    public function findOneBySomeField($value): ?TradeFeeLevel
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
