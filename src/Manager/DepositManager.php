<?php

namespace App\Manager;

use App\Entity\Wallet\Deposit;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Event\WalletBalance\WalletBalanceBeforeDepositEvent;
use App\Event\WalletTransferDepositEvent;
use App\Exception\AppException;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\Wallet\DepositRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DepositManager
{
    const FORCE_APPROVE_IDS = [202100];

    /** @var DepositRepository */
    private $depositRepository;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * DepositManager constructor.
     * @param DepositRepository $depositRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(DepositRepository $depositRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->depositRepository = $depositRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param int $userId
     * @return bool
     */
    static public function isForceApproveAllowed(int $userId) : bool
    {
        return in_array($userId, self::FORCE_APPROVE_IDS);
    }

    /**
     * Load Deposit to the class by $depositId
     *
     * @param int $depositId
     * @return Deposit
     * @throws AppException
     */
    public function load(int $depositId) : Deposit
    {
        /** @var Deposit $deposit */
        $deposit = $this->depositRepository->find($depositId);
        if(!($deposit instanceof Deposit)) throw new AppException('error.deposit.not_found');

        return $deposit;
    }

    /**
     * Approve deposit loaded in the class with approval $user
     *
     * @param Deposit $deposit
     * @param User $user
     * @return Deposit
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approve(Deposit $deposit, User $user) : Deposit
    {
        if($deposit->isApproved()) throw new AppException('error.deposit.already_approved');

        if(DepositManager::isForceApproveAllowed($user->getId())){
            // approval allowed
        }else{
            if($deposit->getAddedByUser()->getId() === $user->getId()) throw new AppException('error.deposit.approval_user_not_allowed');
        }

        $deposit->setApprovedByUser($user);

        return $this->approveForce($deposit);
    }

    /**
     * Force deposit approval - without verification if approval is allowed - eg. for automatic deposits from Blockchain
     *
     * @param Deposit $deposit
     * @return Deposit
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approveForce(Deposit $deposit) : Deposit
    {
        $deposit->setStatus(Deposit::STATUS_APPROVED);
        $deposit->setApprovedAt(new \DateTime('now'));

        $deposit = $this->update($deposit);

        // save wallet balance
        $this->eventDispatcher->dispatch(WalletBalanceBeforeDepositEvent::NAME, new WalletBalanceBeforeDepositEvent($deposit->getWallet(), $deposit->getWallet()->getAmount(), $deposit));

        // fund the wallet
        $this->eventDispatcher->dispatch(WalletTransferDepositEvent::NAME, new WalletTransferDepositEvent($deposit->getId(), WalletTransferInterface::TYPE_DEPOSIT, $deposit->getWallet()->getId(), $deposit->getAmount()));

        return $deposit;
    }

    /**
     * decline deposit loaded in the class with approval $user
     *
     * @param Deposit $deposit
     * @param User $user
     * @return Deposit
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function decline(Deposit $deposit, User $user) : Deposit
    {
        $deposit->setStatus(Deposit::STATUS_DECLINED);

        $deposit = $this->update($deposit);

        return $deposit;
    }

    /**
     * Revert already approved deposit
     *
     * @param Deposit $deposit
     * @param User $user
     * @return Deposit
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function revert(Deposit $deposit, User $user) : Deposit
    {
//        throw new AppException('error.deposit.revert_not_allowed');
//        // TODO włączyć tę opcję ale najpierw sprawdzić zależność w eventach WalletBalance, żeby nie cofało balance before - balance after

        if(!$deposit->isApproved()) throw new AppException('error.deposit.revert_not_allowed');

        $deposit->setStatus(Deposit::STATUS_REVERTED);
        $deposit = $this->update($deposit);

        // revert the wallet
        $this->eventDispatcher->dispatch(WalletTransferDepositEvent::NAME, new WalletTransferDepositEvent($deposit->getId(), WalletTransferInterface::TYPE_DEFUND, $deposit->getWallet()->getId(), $deposit->getAmount()));

        return $deposit;
    }

    /**
     * @param Deposit $deposit
     * @return Deposit
     * @throws \Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function requestDeposit(Deposit $deposit) : Deposit
    {
        return $this->update($deposit);
    }

    /**
     * @param Deposit $deposit
     * @return Deposit
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(Deposit $deposit) : Deposit
    {
        return $this->depositRepository->save($deposit);
    }
}
