<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\ListFilter\UserListFilter;
use App\Manager\ListManager\Paginator;
use App\Model\SystemUserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return EntityManager
     */
    public function getEm(): EntityManager
    {
        return $this->_em;
    }

    /**
     * @return array|null
     */
    public function findVerified() : ?array
    {
        $query = $this->_em->createQuery("
            SELECT user
            FROM App:User user
            WHERE  
                user.verificationStatus = :approvedStatus
            ORDER BY user.id ASC
        ");
        $query->setParameter('approvedStatus', User::VERIFICATION_TIER3_APPROVED);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param int $id
     * @return User
     * @throws AppException
     */
    public function findOrException(int $id){
        /** @var User $user */
        $user = $this->find($id);
        if(!($user instanceof User)) throw new AppException('User not found');

        return $user;
    }

    /**
     * @return User|null
     */
    public function findBitbayLiquidityUser() : ?User
    {
        return $this->find(SystemUserInterface::BITBAY_LIQ_USER);
    }

    /**
     * @return User|null
     */
    public function findKrakenLiquidityUser() : ?User
    {
        return $this->find(SystemUserInterface::KRAKEN_LIQ_USER);
    }

    /**
     * @return User|null
     */
    public function findBinanceLiquidityUser() : ?User
    {
        return $this->find(SystemUserInterface::BINANCE_LIQ_USER);
    }

    /**
     * @param array $referralLinkIds
     * @return array|null
     */
    public function findReferredBy(array $referralLinkIds) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT user
            FROM App:User user
            WHERE  
                user.referredBy IN (:ids)
            ORDER BY user.id ASC
        ");
        $query->setParameter('ids', $referralLinkIds);

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function findDuplicates(User $user) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT user
            FROM App:User user
            WHERE 
                user.firstName = :firstName AND 
                user.lastName = :lastName AND
                user.id != :userId
            ORDER BY user.id ASC
        ");
        $query->setParameter('firstName', $user->getFirstName());
        $query->setParameter('lastName', $user->getLastName());
        $query->setParameter('userId', $user->getId());

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param User $user
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function duplicateExists(User $user) : bool
    {
        $query = $this->_em->createQuery("
            SELECT user
            FROM App:User user
            WHERE 
                user.firstName = :firstName AND 
                user.lastName = :lastName AND
                user.dateOfBirth = :dateOfBirth
            ORDER BY user.id ASC
        ");
        $query->setParameter('firstName', $user->getFirstName());
        $query->setParameter('lastName', $user->getLastName());
        $query->setParameter('dateOfBirth', $user->getDateOfBirth());
        $query->setMaxResults(1);

        $user = $query->getOneOrNullResult();

        if($user instanceof User){
            return true;
        }

        return false;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array|null
     */
    public function findRegisteredBetweenDates(\DateTime $from, \DateTime $to) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT user.id, user.createdAt, user.verificationStatus
            FROM App:User user
            WHERE 
                user.createdAt >= :fromDate AND 
                user.createdAt <= :toDate
            ORDER BY user.id ASC
        ");
        $query->setParameter('fromDate', $from);
        $query->setParameter('toDate', $to);

        $result = $query->getArrayResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function findRegisteredTodayCount() : int
    {
        $todayDate = new \DateTime('now');
        $todayDate->setTime(0, 0, 0);

        $query = $this->_em->createQuery("
            SELECT count(user)
            FROM App:User user
            WHERE 
                user.createdAt > :todayDate
        ");
        $query->setParameter('todayDate', $todayDate);

        return $query->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @return User|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findNextPendingUser(User $user) : ?User
    {
        $query = $this->_em->createQuery("
            SELECT user
            FROM App:User user
            WHERE 
                user.verificationStatus = :statusTier2Approved AND
                user.id > :userId
            ORDER BY user.id ASC
        ");
        $query->setParameter('userId', $user->getId());
        $query->setParameter('statusTier2Approved', User::VERIFICATION_TIER2_APPROVED);
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * @param UserListFilter $userListFilter
     * @return Paginator
     * @throws Query\QueryException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPaginatedList(UserListFilter $userListFilter) : Paginator
    {
        $criteria = new Criteria();

        if(!is_null($userListFilter->id)){
            $criteria->where(Criteria::expr()->eq('user.id', $userListFilter->id));
        }

        if(!is_null($userListFilter->email)){
            $criteria->andWhere(Criteria::expr()->contains('user.email', $userListFilter->email));
        }

        if(!is_null($userListFilter->firstName)){
            $criteria->andWhere(Criteria::expr()->contains('user.firstName', $userListFilter->firstName));
        }

        if(!is_null($userListFilter->lastName)){
            $criteria->andWhere(Criteria::expr()->contains('user.lastName', $userListFilter->lastName));
        }

        if(!is_null($userListFilter->verificationStatus)){
            $criteria->andwhere(Criteria::expr()->eq('user.verificationStatus', $userListFilter->verificationStatus));
        }

        if(!is_null($userListFilter->isFilesSent)){
            if($userListFilter->isFilesSent){
                // TODO
            }
        }

        // query for results
        $qb = $this->createQueryBuilder('user');
        $qb->addCriteria($criteria);
        if(!is_null($userListFilter->sortBy)){
            $qb->orderBy('user.'.$userListFilter->sortBy, $userListFilter->sortType);
        }
        if($userListFilter->pageSize > 0){
            $qb->setFirstResult($userListFilter->pageSize * ($userListFilter->page - 1));
            $qb->setMaxResults($userListFilter->pageSize);
        }

        // query for total items
        $qbTotal = $this->createQueryBuilder('user');
        $qbTotal->select($qbTotal->expr()->count('user.id'));
        $qbTotal->addCriteria($criteria);

        return new Paginator(
            $userListFilter->page,
            $userListFilter->pageSize,
            $qb->getQuery()->getResult(),
            (int) $qbTotal->getQuery()->getSingleScalarResult()
        );
    }

    /**
     * @param string $email
     * @return bool
     */
    public function userExists(string $email) : bool
    {
        /** @var User $user */
        $user = $this->findOneBy([
            'email' => $email
        ]);

        if($user instanceof User){
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(User $user)
    {
        $this->_em->persist($user);
        $this->_em->flush();

        return $user;
    }

    public function getUsersByOwnedCurrency(Currency $currency, $option, $value){
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user')
            ->leftJoin('user.wallets', 'wallet')
            ->leftJoin('wallet.currency','currency')
            ->where('currency = :curr')
            ->setParameter('curr',$currency)
        ;


        if('gt' === $option){
            $qb->andWhere($qb->expr()->gt('wallet.amount',$value));
        }else if('gte' === $option){
            $qb->andWhere($qb->expr()->gte('wallet.amount',$value));
        }else if('lt' === $option){
            $qb->andWhere($qb->expr()->lt('wallet.amount',$value));
        }else if('lte' === $option){
            $qb->andWhere($qb->expr()->lte('wallet.amount',$value));
        }

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
