<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Verification;
use App\Exception\AppException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Verification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Verification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Verification[]    findAll()
 * @method Verification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerificationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Verification::class);
    }

    /**
     * @param int $id
     * @param User|null $user
     * @return Verification
     * @throws AppException
     */
    public function findOrException(int $id, User $user = null) : Verification
    {
        if($user instanceof User){
            /** @var Verification $verification */
            $verification = $this->findOneBy([
                'id' => $id,
                'user' => $user->getId()
            ]);
        }else{
            /** @var Verification $verification */
            $verification = $this->find($id);
        }

        if(!($verification instanceof Verification)) throw new AppException('Verification not found');

        return $verification;
    }

    /**
     * @param User $user
     * @return Verification
     * @throws AppException
     */
    public function findRecentByUser(User $user) : Verification
    {
        /** @var Verification $verification */
        $verification = $this->findOneBy(['user' => $user->getId()], ['id' => 'desc']);

        if(!($verification instanceof Verification)) throw new AppException('Verification not found');

        return $verification;
    }

    /**
     * @param Verification $verification
     * @return Verification
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Verification $verification)
    {
        $this->_em->persist($verification);
        $this->_em->flush();

        return $verification;
    }

    // /**
    //  * @return Verification[] Returns an array of Verification objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Verification
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
