<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\Currency;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function checkConnection(){
        if($this->_em->getConnection()->ping() === false){
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }

    /**
     * @return mixed
     */
    public function enableAll() {
        $qb = $this->createQueryBuilder('address');
        $qb
            ->update()
            ->set('address.enabled', 1);

        return $qb->getQuery()->execute();
    }

    /**
     * @return mixed
     */
    public function disableAll() {
        $qb = $this->createQueryBuilder('address');
        $qb
            ->update()
            ->set('address.enabled', 0);

        return $qb->getQuery()->execute();
    }

    /**
     * @param Currency $currency
     * @return array|null
     */
    public function findByCurrency(Currency $currency) : ?array
    {
        $query = $this->_em->createQuery("
            SELECT address
            FROM App:Address address
            LEFT JOIN address.wallet addressWallet
            WHERE 
                addressWallet.currency = :currencyId 
        ");
        $query->setParameter('currencyId', $currency->getId());

        $result = $query->getResult();

        if(count($result) > 0) return $result;

        return null;
    }

    /**
     * @param string $addressString
     * @return Address
     * @throws AppException
     */
    public function findByAddressOrException(string $addressString){
        /** @var Address $address */
        $address = $this->findOneBy(['address' => $addressString]);
        if(!($address instanceof Address)) throw new AppException('error.address.not_found');

        return $address;
    }

    /**
     * Find the latest address generated for passed $wallet
     *
     * @param Wallet $wallet
     * @return Address|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLatestByWallet(Wallet $wallet) : ?Address
    {
        $query = $this->_em->createQuery("
            SELECT addr
            FROM App:Address addr
            WHERE addr.wallet = :walletId AND addr.enabled = TRUE
            ORDER BY addr.id DESC
        ");
        $query->setParameter('walletId', $wallet->getId());
        $query->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * @param Address $address
     * @return Address
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Address $address)
    {
        $this->_em->persist($address);
        $this->_em->flush();

        return $address;
    }

    // /**
    //  * @return Address[] Returns an array of Address objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
