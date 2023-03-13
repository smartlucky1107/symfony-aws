<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Manager\ListFilter\WalletListFilter;
use App\Manager\ListManager\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Wallet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Wallet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Wallet[]    findAll()
 * @method Wallet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WalletRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Wallet::class);
    }

    public function checkConnection(){
        if($this->_em->getConnection()->ping() === false){
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }

    /**
     * @param string|null $walletType
     * @param array $exceptUserIds
     * @return array
     * @throws AppException
     */
    public function findBalancesGroupedByCurrency(string $walletType = null, $exceptUserIds = []) : array
    {
        if($walletType === Wallet::TYPE_FEE){
            $query = $this->_em->createQuery("
                SELECT wc.fullName as currency, sum(w.amount) as balance
                FROM App:Wallet\Wallet w
                LEFT JOIN w.currency wc 
                WHERE w.type = :walletType
                GROUP BY w.currency
            ");
            $query->setParameter('walletType', Wallet::TYPE_FEE);
        }elseif($walletType === Wallet::TYPE_USER){
            $whereExceptUsers = '';
            if(count($exceptUserIds) > 0){
                $whereExceptUsers = ' AND w.user NOT IN (:ids)';
            }

            $query = $this->_em->createQuery("
                SELECT wc.fullName as currency, sum(w.amount) as balance
                FROM App:Wallet\Wallet w
                LEFT JOIN w.currency wc 
                WHERE w.type = :walletType " . $whereExceptUsers . "
                GROUP BY w.currency
            ");
            $query->setParameter('walletType', Wallet::TYPE_USER);

            if(count($exceptUserIds) > 0){
                $query->setParameter('ids', $exceptUserIds);
            }
        }else{
            throw new AppException('Wallet type not allowed');
        }

        return $query->getArrayResult();
    }

    /**
     * @param int $id
     * @return Wallet
     * @throws AppException
     */
    public function findOrException(int $id){
        /** @var Wallet $wallet */
        $wallet = $this->find($id);
        if(!($wallet instanceof Wallet)) throw new AppException('error.wallet.not_found');

        return $wallet;
    }

    /**
     * @param WalletListFilter $walletListFilter
     * @return Paginator
     * @throws Query\QueryException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPaginatedList(WalletListFilter $walletListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($walletListFilter->id)){
            $criteria->where(Criteria::expr()->eq('wallet.id', $walletListFilter->id));
        }

        if(!is_null($walletListFilter->name)){
            $criteria->andWhere(Criteria::expr()->contains('wallet.name', $walletListFilter->name));
        }

        if(!is_null($walletListFilter->userId)){
            $criteria->andWhere(Criteria::expr()->eq('wallet.user', (int) $walletListFilter->userId));
        }

        // query for results
        $qb = $this->createQueryBuilder('wallet');
        $qb->leftJoin('wallet.currency', 'walletCurrency');
        $qb->addCriteria($criteria);
        $qb->orderBy('walletCurrency.sortIndex', 'ASC');
//        if(!is_null($walletListFilter->sortBy)){
//            $qb->orderBy('wallet.'.$walletListFilter->sortBy, $walletListFilter->sortType);
//        }
        if($walletListFilter->pageSize > 0){
            $qb->setFirstResult($walletListFilter->pageSize * ($walletListFilter->page - 1));
            $qb->setMaxResults($walletListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('wallet');
        $qbTotal->select($qbTotal->expr()->count('wallet.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $walletListFilter->page,
            $walletListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

//    /**
//     * Method returns Query.
//     *
//     * @param array $filterBy
//     * @param array $orderBy
//     * @return Query
//     */
//    public function createListQuery(array $filterBy, array $orderBy) : Query
//    {
//        $qb = $this->createQueryBuilder('w');
//
//        // left joins
//        if(isset($filterBy['currency']) || isset($orderBy['currency'])) $qb->leftJoin('w.currency', 'wc');
//        if(isset($filterBy['user'])) $qb->leftJoin('w.user', 'wu');
//
//        // filters
//        if(isset($filterBy['id'])){
//            $qb
//                ->andWhere('w.id = :id')
//                ->setParameter('id', $filterBy['id']);
//        }
//
//        if(isset($filterBy['currency'])){
//            $qb
//                ->andWhere('wc.shortName = :currency')
//                ->setParameter('currency', strtoupper($filterBy['currency']));
//        }
//
//        if(isset($filterBy['user'])){
//            $qb
//                ->andWhere('wu.id = :user')
//                ->setParameter('user', strtoupper($filterBy['user']));
//        }
//
//        // order by params
//        if(isset($orderBy['id'])) $qb->orderBy('w.id', (bool) $orderBy['id'] ? 'ASC': 'DESC');
//        if(isset($orderBy['currency'])) $qb->orderBy('wc.shortName', (bool) $orderBy['currency'] ? 'ASC': 'DESC');
//
//        return $qb->getQuery();
//    }

    /**
     * @param int $userId
     * @param string $currencyShortName
     * @return Wallet|null
     */
    public function findByUserAndCurrencyShortName(int $userId, string $currencyShortName) : ?Wallet
    {
        $query = $this->_em->createQuery("
            SELECT w
            FROM App:Wallet\Wallet w
            LEFT JOIN w.currency wc 
            WHERE  
                wc.shortName = :currencyShortName AND 
                w.user = :userId
        ");
        $query->setParameter('currencyShortName', $currencyShortName);
        $query->setParameter('userId', $userId);
        $query->setMaxResults(1);

        $result = $query->getResult();

        if(count($result) > 0) return $result[0];

        return null;
    }

    /**
     * @param User $user
     * @param Currency $currency
     * @return bool
     */
    public function walletExists(User $user, Currency $currency) : bool
    {
        /** @var Wallet $wallet */
        $wallet = $this->findOneBy([
            'user' => $user->getId(),
            'currency' => $currency->getId()
        ]);

        if($wallet instanceof Wallet){
            return true;
        }

        return false;
    }

    /**
     * @param Wallet $wallet
     * @return Wallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Wallet $wallet)
    {
        $this->_em->persist($wallet);
        $this->_em->flush();

        return $wallet;
    }

    /**
     * @param Currency $currency
     * @param int $userId
     * @return Wallet|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOneByCurrencyUserId(Currency $currency, int $userId) : ?Wallet
    {
        $qb = $this->createQueryBuilder('wallet');
        $qb->select('wallet')
            ->where('wallet.user = :user')->setParameter('user', $userId)
            ->andWhere('wallet.currency = :currency')->setParameter('currency', $currency);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array
     */
    public function getFeeWallets() : array
    {
        $qb = $this->createQueryBuilder('wallet');
        $qb
            ->select('wallet')
            ->where('wallet.type = :type')
            ->setParameter('type',Wallet::TYPE_FEE);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getCheckoutFeeWallets() : array
    {
        $qb = $this->createQueryBuilder('wallet');
        $qb
            ->select('wallet')
            ->where('wallet.type = :type')
            ->setParameter('type',Wallet::TYPE_CHECKOUT_FEE);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Currency $currency
     * @return Wallet
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFeeWallet(Currency $currency) : ?Wallet
    {
        $qb = $this->createQueryBuilder('wallet');
        $qb->select('wallet')
            ->where('wallet.type = :type')->setParameter('type',Wallet::TYPE_FEE)
            ->andWhere('wallet.currency = :currency')->setParameter('currency', $currency);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Wallet $wallet
     */
    public function detach(Wallet $wallet){
        $this->_em->detach($wallet);
    }


    // /**
    //  * @return Wallet[] Returns an array of Wallet objects
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
    public function findOneBySomeField($value): ?Wallet
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
