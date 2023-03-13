<?php

namespace App\Repository\Wallet;

use App\Entity\Currency;
use App\Entity\User;
use App\Entity\Wallet\Withdrawal;
use App\Manager\ListFilter\WithdrawalListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Withdrawal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Withdrawal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Withdrawal[]    findAll()
 * @method Withdrawal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WithdrawalRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Withdrawal::class);
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
            SELECT w
            FROM App:Wallet\Withdrawal w 
            LEFT JOIN w.wallet wWallet
            LEFT JOIN wWallet.currency wWalletCurrency
            WHERE 
                (w.status = :statusRequest OR w.status = :statusExternalApproval OR w.status = :statusApproved) AND
                wWallet.user = :userId AND
                w.giifReportAssigned = FALSE AND
                wWalletCurrency.type = :currencyType
            ORDER BY w.id ASC
         ");
        $query->setParameter('statusRequest', Withdrawal::STATUS_REQUEST);
        $query->setParameter('statusExternalApproval', Withdrawal::STATUS_EXTERNAL_APPROVAL);
        $query->setParameter('statusApproved', Withdrawal::STATUS_APPROVED);
        $query->setParameter('userId', $user->getId());
        $query->setParameter('currencyType', Currency::TYPE_FIAT);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @return array|null
     */
    public function findForAutoExternalApproval() : ?array
    {
        $query = $this->_em->createQuery("
            SELECT w
            FROM App:Wallet\Withdrawal w
            WHERE 
                w.status = :status
            ORDER BY w.id DESC
         ");
        $query->setParameter('status', Withdrawal::STATUS_REQUEST);

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
            SELECT w
            FROM App:Wallet\Withdrawal w
            LEFT JOIN w.wallet wWallet
            WHERE 
                wWallet.user = :userId
            ORDER BY w.id DESC
         ");
        $query->setParameter('userId', $user->getId());

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function findNewExpired() : ?array
    {
        $nowDate = new \DateTime('now');

        $query = $this->_em->createQuery("
            SELECT w
            FROM App:Wallet\Withdrawal w
            WHERE 
                w.status = :newStatus AND
                (w.confirmationHashExpiredAt < :nowDate OR w.confirmationHashExpiredAt IS NULL)
            ORDER BY w.id ASC
        ");
        $query->setParameter('newStatus', Withdrawal::STATUS_NEW);
        $query->setParameter('nowDate', $nowDate);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param WithdrawalListFilter $withdrawalListFilter
     * @return Paginator
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPaginatedList(WithdrawalListFilter $withdrawalListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($withdrawalListFilter->id)){
            $criteria->where(Criteria::expr()->eq('withdrawal.id', $withdrawalListFilter->id));
        }

        if(!is_null($withdrawalListFilter->status)){
            $criteria->andWhere(Criteria::expr()->eq('withdrawal.status', $withdrawalListFilter->status));
        }

        if(!is_null($withdrawalListFilter->address)){
            $criteria->andWhere(Criteria::expr()->contains('withdrawal.address', $withdrawalListFilter->address));
        }

        if(!is_null($withdrawalListFilter->walletId)){
            $criteria->andWhere(Criteria::expr()->eq('withdrawal.wallet', (int) $withdrawalListFilter->walletId));
        }
        $criteria->andWhere(Criteria::expr()->neq('withdrawal.status', Withdrawal::STATUS_NEW));

        // query for results
        $qb = $this->createQueryBuilder('withdrawal');
        $qb->addCriteria($criteria);
        if(!is_null($withdrawalListFilter->sortBy)){
            $qb->orderBy('withdrawal.'.$withdrawalListFilter->sortBy, $withdrawalListFilter->sortType);
        }
        if($withdrawalListFilter->pageSize > 0) {
            $qb->setFirstResult($withdrawalListFilter->pageSize * ($withdrawalListFilter->page - 1));
            $qb->setMaxResults($withdrawalListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('withdrawal');
        $qbTotal->select($qbTotal->expr()->count('withdrawal.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $withdrawalListFilter->page,
            $withdrawalListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param Withdrawal $withdrawal
     * @return Withdrawal
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Withdrawal $withdrawal)
    {
        $this->_em->persist($withdrawal);
        $this->_em->flush();

        return $withdrawal;
    }

    // /**
    //  * @return Withdrawal[] Returns an array of Withdrawal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Withdrawal
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
