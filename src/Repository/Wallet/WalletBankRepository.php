<?php

namespace App\Repository\Wallet;

use App\Entity\Wallet\WalletBank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method WalletBank|null find($id, $lockMode = null, $lockVersion = null)
 * @method WalletBank|null findOneBy(array $criteria, array $orderBy = null)
 * @method WalletBank[]    findAll()
 * @method WalletBank[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WalletBankRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WalletBank::class);
    }

    /**
     * @param WalletBank $walletBank
     * @return WalletBank
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(WalletBank $walletBank)
    {
        $this->_em->persist($walletBank);
        $this->_em->flush();

        return $walletBank;
    }

    // /**
    //  * @return WalletBank[] Returns an array of WalletBank objects
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
    public function findOneBySomeField($value): ?WalletBank
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
