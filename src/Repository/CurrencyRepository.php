<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Exception\AppException;
use App\Manager\ListFilter\CurrencyListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Common\Collections\Criteria;

/**
 * @method Currency|null find($id, $lockMode = null, $lockVersion = null)
 * @method Currency|null findOneBy(array $criteria, array $orderBy = null)
 * @method Currency[]    findAll()
 * @method Currency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    /**
     * @param int $id
     * @return Currency
     * @throws AppException
     */
    public function findOrException(int $id){
        /** @var Currency $currency */
        $currency = $this->find($id);
        if(!($currency instanceof Currency)) throw new AppException('Currency not found');

        return $currency;
    }

    /**
     * @return array|null
     */
    public function findEnabled() : ?array
    {
        return $this->findBy(['enabled' => true]);
    }

    /**
     * @param CurrencyListFilter $currencyListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(CurrencyListFilter $currencyListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($currencyListFilter->fullName)){
            $criteria->where(Criteria::expr()->contains('currency.fullName', $currencyListFilter->fullName));
        }

        if(!is_null($currencyListFilter->shortName)){
            $criteria->andWhere(Criteria::expr()->contains('currency.shortName', $currencyListFilter->shortName));
        }

        // query for results
        $qb = $this->createQueryBuilder('currency');
        $qb->addCriteria($criteria);
        if(!is_null($currencyListFilter->sortBy)){
            $qb->orderBy('currency.'.$currencyListFilter->sortBy, $currencyListFilter->sortType);
        }
        if($currencyListFilter->pageSize > 0) {
            $qb->setFirstResult($currencyListFilter->pageSize * ($currencyListFilter->page - 1));
            $qb->setMaxResults($currencyListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('currency');
        $qbTotal->select($qbTotal->expr()->count('currency.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $currencyListFilter->page,
            $currencyListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param string $shortName
     * @return Currency|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByShortName(string $shortName) : ?Currency
    {
        $qb = $this->createQueryBuilder('currency');
        return
            $qb->select('currency')
            ->where('currency.shortName = :shortName')
            ->setParameter('shortName', $shortName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Currency $currency
     * @return Currency
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Currency $currency)
    {
        $this->_em->persist($currency);
        $this->_em->flush();

        return $currency;
    }

    // /**
    //  * @return Currency[] Returns an array of Currency objects
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
    public function findOneBySomeField($value): ?Currency
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
