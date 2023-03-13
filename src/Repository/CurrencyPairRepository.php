<?php

namespace App\Repository;

use App\Entity\CurrencyPair;
use App\Manager\ListFilter\CurrencyPairListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CurrencyPair|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrencyPair|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrencyPair[]    findAll()
 * @method CurrencyPair[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyPairRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CurrencyPair::class);
    }

    /**
     * @return array|null
     */
    public function findEnabledForPOS() : ?array
    {
        return $this->findBy(['enabled' => true, 'posOrderAllowed' => true]);
    }

    /**
     * @param int $limit
     * @return CurrencyPair[]
     */
    public function findGainers(int $limit = 6){
        return $this->findBy(['enabled' => true], [
            'growth24h' => 'DESC'
        ], $limit);
    }

    /**
     * @param int $limit
     * @return CurrencyPair[]
     */
    public function findLosers(int $limit = 6){
        return $this->findBy(['enabled' => true], [
            'growth24h' => 'ASC'
        ], $limit);
    }

    /**
     * @param string $currencyPairShortName
     * @return CurrencyPair|null
     */
    public function findByShortName(string $currencyPairShortName) : ?CurrencyPair
    {
        $currencyPairArray = explode('-', $currencyPairShortName);

        $query = $this->_em->createQuery("
            SELECT cp, bc, qc
            FROM App:CurrencyPair cp
            LEFt JOIN cp.baseCurrency bc
            LEFt JOIN cp.quotedCurrency qc
            WHERE bc.shortName = :baseCurrencyShortName AND qc.shortName = :quotedCurrencyShortName
        ");
        $query->setParameter('baseCurrencyShortName', $currencyPairArray[0]);
        $query->setParameter('quotedCurrencyShortName', $currencyPairArray[1]);

        $result = $query->getResult();

        if(count($result) > 0) return $result[0];

        return null;
    }

    /**
     * @param CurrencyPairListFilter $currencyPairListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(CurrencyPairListFilter $currencyPairListFilter) : Paginator
    {
        $criteria = new Criteria();

        // query for results
        $qb = $this->createQueryBuilder('currencyPair');
        $qb->addCriteria($criteria);
        if(!is_null($currencyPairListFilter->sortBy)){
            $qb->orderBy('currencyPair.'.$currencyPairListFilter->sortBy, $currencyPairListFilter->sortType);
        }
        if($currencyPairListFilter->pageSize > 0) {
            $qb->setFirstResult($currencyPairListFilter->pageSize * ($currencyPairListFilter->page - 1));
            $qb->setMaxResults($currencyPairListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('currencyPair');
        $qbTotal->select($qbTotal->expr()->count('currencyPair.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $currencyPairListFilter->page,
            $currencyPairListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return CurrencyPair
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(CurrencyPair $currencyPair)
    {
        $this->_em->persist($currencyPair);
        $this->_em->flush();

        return $currencyPair;
    }

    /**
     * @param CurrencyPair $currencyPair
     */
    public function detach(CurrencyPair $currencyPair){
        $this->_em->detach($currencyPair);
    }

    // /**
    //  * @return CurrencyPair[] Returns an array of CurrencyPair objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CurrencyPair
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
