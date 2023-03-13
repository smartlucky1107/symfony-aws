<?php

namespace App\Manager\Processor;

use App\Document\NotificationInterface;
use App\Entity\Wallet\InternalTransfer;
use App\Event\WalletTransferInternalTransferEvent;
use App\Exception\AppException;
use App\Manager\InternalTransferManager;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\Wallet\InternalTransferRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InternalTransferRequestProcessor
{
    /** @var InternalTransfer */
    private $internalTransfer;

    /** @var InternalTransferRepository */
    private $internalTransferRepository;

    /** @var InternalTransferManager */
    private $internalTransferManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * InternalTransferRequestProcessor constructor.
     * @param InternalTransferRepository $internalTransferRepository
     * @param InternalTransferManager $internalTransferManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(InternalTransferRepository $internalTransferRepository, InternalTransferManager $internalTransferManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->internalTransferRepository = $internalTransferRepository;
        $this->internalTransferManager = $internalTransferManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param int $internalTransferId
     * @return InternalTransfer
     * @throws AppException
     */
    public function loadInternalTransfer(int $internalTransferId) : InternalTransfer
    {
        $this->internalTransfer = $this->internalTransferRepository->find($internalTransferId);
        if(!($this->internalTransfer instanceof InternalTransfer)) throw new AppException('Internal transfer not found');

        return $this->internalTransfer;
    }

    /**
     * @return bool
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process() : bool
    {
        $this->internalTransferRepository->checkConnection();

        if(!($this->internalTransfer instanceof InternalTransfer)) throw new AppException('Internal transfer not loaded');

        try{
            $this->internalTransferManager->verifyForRequest($this->internalTransfer);
        }catch (\Exception $exception){
            $this->internalTransfer = $this->internalTransferManager->reject($this->internalTransfer);

            return false;
        }

        $this->internalTransfer = $this->internalTransferManager->setRequested($this->internalTransfer);

        $totalAmount = $this->internalTransfer->getTotalAmount();
        $this->eventDispatcher->dispatch(WalletTransferInternalTransferEvent::NAME, new WalletTransferInternalTransferEvent($this->internalTransfer->getId(), WalletTransferInterface::TYPE_BLOCK, $this->internalTransfer->getWallet()->getId(), $totalAmount));

        // Auto approve
        $this->internalTransferManager->approve($this->internalTransfer);

        return true;
    }

    /**
     * @return InternalTransferRepository
     */
    public function getInternalTransferRepository(): InternalTransferRepository
    {
        return $this->internalTransferRepository;
    }
}
