<?php

namespace App\Repository\Liquidity;

use App\Entity\Currency;
use App\Entity\Liquidity\ExternalMarketWallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExternalMarketWallet|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalMarketWallet|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalMarketWallet[]    findAll()
 * @method ExternalMarketWallet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalMarketWalletRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExternalMarketWallet::class);
    }

    /**
     * @param int $externalMarketId
     * @param Currency $currency
     * @return bool
     */
    public function walletExists(int $externalMarketId, Currency $currency) : bool
    {
        /** @var ExternalMarketWallet $externalMarketWallet */
        $externalMarketWallet = $this->findOneBy([
            'externalMarket' => $externalMarketId,
            'currency' => $currency->getId()
        ]);

        if($externalMarketWallet instanceof ExternalMarketWallet){
            return true;
        }

        return false;
    }

    /**
     * @param ExternalMarketWallet $externalMarketWallet
     * @return ExternalMarketWallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(ExternalMarketWallet $externalMarketWallet)
    {
        $this->_em->persist($externalMarketWallet);
        $this->_em->flush();

        return $externalMarketWallet;
    }

    // /**
    //  * @return ExternalMarketWallet[] Returns an array of ExternalMarketWallet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExternalMarketWallet
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
