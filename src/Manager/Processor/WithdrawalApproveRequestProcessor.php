<?php

namespace App\Manager\Processor;

use App\Document\NotificationInterface;
use App\Entity\Wallet\Withdrawal;
use App\Event\WalletTransferWithdrawalEvent;
use App\Exception\AppException;
use App\Manager\WithdrawalManager;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\Wallet\WithdrawalRepository;

class WithdrawalApproveRequestProcessor
{
    /** @var Withdrawal */
    private $withdrawal;

    /** @var WithdrawalRepository */
    private $withdrawalRepository;

    /** @var WithdrawalManager */
    private $withdrawalManager;

    /**
     * WithdrawalApproveRequestProcessor constructor.
     * @param WithdrawalRepository $withdrawalRepository
     * @param WithdrawalManager $withdrawalManager
     */
    public function __construct(WithdrawalRepository $withdrawalRepository, WithdrawalManager $withdrawalManager)
    {
        $this->withdrawalRepository = $withdrawalRepository;
        $this->withdrawalManager = $withdrawalManager;
    }

    /**
     * @param int $withdrawalId
     * @return Withdrawal
     * @throws AppException
     */
    public function loadWithdrawal(int $withdrawalId) : Withdrawal
    {
        $this->withdrawal = $this->withdrawalRepository->find($withdrawalId);
        if(!($this->withdrawal instanceof Withdrawal)) throw new AppException('Withdrawal not found');

        return $this->withdrawal;
    }

    /**
     * @return bool
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process() : bool
    {
        $this->withdrawalRepository->checkConnection();

        if(!($this->withdrawal instanceof Withdrawal)) throw new AppException('Withdrawal not loaded');

        try{
            $this->withdrawalManager->verifyForApprove($this->withdrawal);
        }catch (\Exception $exception){
            // DO NOTHING ?? or maybe decline

            return false;
        }

        $this->withdrawalManager->approve($this->withdrawal);

        return true;
    }

    /**
     * @return WithdrawalRepository
     */
    public function getWithdrawalRepository(): WithdrawalRepository
    {
        return $this->withdrawalRepository;
    }
}
