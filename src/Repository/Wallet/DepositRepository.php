<?php

namespace App\Repository\Wallet;

use App\Entity\Currency;
use App\Entity\User;
use App\Entity\Wallet\Deposit;
use App\Manager\ListFilter\DepositListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Deposit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deposit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deposit[]    findAll()
 * @method Deposit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepositRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    public function checkConnection(){
        if($this->_em->getConnection()->ping() === false){
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function findForGiifReportByUser(User $user) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT d
                FROM App:Wallet\Deposit d
            LEFT JOIN d.wallet dWallet
            LEFT JOIN dWallet.currency dWalletCurrency
            WHERE
                (d.status = :statusRequest OR d.status = :statusApproved) AND
                dWallet.user = :userId AND
                d.giifReportAssigned = FALSE AND
                dWalletCurrency.type = :currencyType
            ORDER BY d.id ASC
         ");
        $query->setParameter('statusRequest', Deposit::STATUS_REQUEST);
        $query->setParameter('statusApproved', Deposit::STATUS_APPROVED);
        $query->setParameter('userId', $user->getId());
        $query->setParameter('currencyType', Currency::TYPE_FIAT);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function findForUser(User $user) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT d
                FROM App:Wallet\Deposit d
            LEFT JOIN d.wallet dWallet
            WHERE 
                dWallet.user = :userId
            ORDER BY d.id DESC
         ");
        $query->setParameter('userId', $user->getId());

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param DepositListFilter $depositListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(DepositListFilter $depositListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($depositListFilter->id)){
            $criteria->where(Criteria::expr()->eq('deposit.id', $depositListFilter->id));
        }

        if(!is_null($depositListFilter->status)){
            $criteria->andWhere(Criteria::expr()->eq('deposit.status', $depositListFilter->status));
        }

        if(!is_null($depositListFilter->walletId)){
            $criteria->andWhere(Criteria::expr()->eq('deposit.wallet', (int) $depositListFilter->walletId));
        }

        if(!is_null($depositListFilter->excludedUserId)){
            $criteria->andWhere(Criteria::expr()->neq('deposit.addedByUser', (int) $depositListFilter->excludedUserId));
        }

        if(!is_null($depositListFilter->bankTransaction)){
            $criteria->andWhere(Criteria::expr()->contains('deposit.bankTransactionId', $depositListFilter->bankTransaction));
        }

        // query for results
        $qb = $this->createQueryBuilder('deposit');
        $qb->addCriteria($criteria);
        if(!is_null($depositListFilter->sortBy)){
            $qb->orderBy('deposit.'.$depositListFilter->sortBy, $depositListFilter->sortType);
        }
        if($depositListFilter->pageSize > 0) {
            $qb->setFirstResult($depositListFilter->pageSize * ($depositListFilter->page - 1));
            $qb->setMaxResults($depositListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('deposit');
        $qbTotal->select($qbTotal->expr()->count('deposit.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $depositListFilter->page,
            $depositListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param Deposit $deposit
     * @return Deposit
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Deposit $deposit)
    {
        $this->_em->persist($deposit);
        $this->_em->flush();

        return $deposit;
    }

    // /**
    //  * @return Deposit[] Returns an array of Deposit objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Deposit
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
