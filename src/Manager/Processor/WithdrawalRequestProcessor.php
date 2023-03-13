<?php

namespace App\Manager\Processor;

use App\Document\NotificationInterface;
use App\Entity\Wallet\Withdrawal;
use App\Event\WalletTransferWithdrawalEvent;
use App\Exception\AppException;
use App\Manager\WithdrawalManager;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\Wallet\WithdrawalRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WithdrawalRequestProcessor
{
    /** @var Withdrawal */
    private $withdrawal;

    /** @var WithdrawalRepository */
    private $withdrawalRepository;

    /** @var WithdrawalManager */
    private $withdrawalManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * WithdrawalRequestProcessor constructor.
     * @param WithdrawalRepository $withdrawalRepository
     * @param WithdrawalManager $withdrawalManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(WithdrawalRepository $withdrawalRepository, WithdrawalManager $withdrawalManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->withdrawalRepository = $withdrawalRepository;
        $this->withdrawalManager = $withdrawalManager;
        $this->eventDispatcher = $eventDispatcher;
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process() : bool
    {
        $this->withdrawalRepository->checkConnection();

        if(!($this->withdrawal instanceof Withdrawal)) throw new AppException('Withdrawal not loaded');

        try{
            $this->withdrawalManager->verifyForRequest($this->withdrawal);
        }catch (\Exception $exception){
            $this->withdrawal = $this->withdrawalManager->reject($this->withdrawal);

            return false;
        }

        $this->withdrawalManager->setRequested($this->withdrawal);

        $totalAmount = $this->withdrawal->getTotalAmount();
        $this->eventDispatcher->dispatch(WalletTransferWithdrawalEvent::NAME, new WalletTransferWithdrawalEvent($this->withdrawal->getId(), WalletTransferInterface::TYPE_BLOCK, $this->withdrawal->getWallet()->getId(), $totalAmount));

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
