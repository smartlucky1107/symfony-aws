<?php

namespace App\Manager;

use App\Document\NotificationInterface;
use App\Entity\Currency;
use App\Entity\Wallet\InternalTransfer;
use App\Entity\Wallet\Wallet;
use App\Event\WalletTransferInternalTransferEvent;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\Wallet\InternalTransferRepository;
use App\Resolver\FeeWalletResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InternalTransferManager
{
    /** @var InternalTransferRepository */
    private $internalTransferRepository;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var RedisSubscribeManager */
    private $redisSubscribeManager;

    /** @var FeeWalletResolver */
    private $feeWalletResolver;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var GoogleAuthenticatorManager */
    private $googleAuthenticatorManager;

    /** @var FeeTransferManager */
    private $feeTransferManager;

    /**
     * InternalTransferManager constructor.
     * @param InternalTransferRepository $internalTransferRepository
     * @param NotificationManager $notificationManager
     * @param RedisSubscribeManager $redisSubscribeManager
     * @param FeeWalletResolver $feeWalletResolver
     * @param EventDispatcherInterface $eventDispatcher
     * @param GoogleAuthenticatorManager $googleAuthenticatorManager
     * @param FeeTransferManager $feeTransferManager
     */
    public function __construct(InternalTransferRepository $internalTransferRepository, NotificationManager $notificationManager, RedisSubscribeManager $redisSubscribeManager, FeeWalletResolver $feeWalletResolver, EventDispatcherInterface $eventDispatcher, GoogleAuthenticatorManager $googleAuthenticatorManager, FeeTransferManager $feeTransferManager)
    {
        $this->internalTransferRepository = $internalTransferRepository;
        $this->notificationManager = $notificationManager;
        $this->redisSubscribeManager = $redisSubscribeManager;
        $this->feeWalletResolver = $feeWalletResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->googleAuthenticatorManager = $googleAuthenticatorManager;
        $this->feeTransferManager = $feeTransferManager;
    }

    /**
     * Load InternalTransfer to the class by $internalTransferId
     *
     * @param int $internalTransferId
     * @return InternalTransfer
     * @throws AppException
     */
    public function load(int $internalTransferId) : InternalTransfer
    {
        $internalTransfer = $this->internalTransferRepository->find($internalTransferId);
        if(!($internalTransfer instanceof InternalTransfer)) throw new AppException('error.internal_transfer.not_found');

        return $internalTransfer;
    }

    /**
     * @return string
     */
    public function calculateFee() : string
    {
        $fee = 0;
        $fee = bcadd($fee, 0, PriceInterface::BC_SCALE);

        return (string) $fee;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return bool
     * @throws AppException
     */
    public function verifyForRequest(InternalTransfer $internalTransfer) : bool
    {
        /** @var Wallet $fromWallet */
        $fromWallet = $internalTransfer->getWallet();

        /** @var Wallet $toWallet */
        $toWallet = $internalTransfer->getToWallet();

        $totalAmount = $internalTransfer->getTotalAmount();

        if(!$internalTransfer->isNew()) throw new AppException('error.internal_transfer.status_does_not_allow');
        if(!is_numeric($totalAmount)) throw new AppException('Amount is invalid');
        if($fromWallet->getId() === $toWallet->getId()) throw new AppException('Internal transfer inside the same wallet is not allowed.');
        if($fromWallet->getCurrency()->getId() !== $toWallet->getCurrency()->getId()) throw new AppException('Internal transfer not allowed for wallets with different currencies.');
        if(!$fromWallet->isTransferAllowed($totalAmount)) throw new AppException('error.wallet.insufficient_funds');

        return true;
    }

    /**
     * @param Wallet $wallet
     * @param Wallet $toWallet
     * @param string $amount
     * @param string $fee
     * @return InternalTransfer
     * @throws AppException
     * @throws \Exception
     */
    public function request(Wallet $wallet, Wallet $toWallet, string $amount, string $fee) : InternalTransfer
    {
        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = new InternalTransfer($wallet, $toWallet, $amount, $fee);

        $this->verifyForRequest($internalTransfer);

        $internalTransfer = $this->internalTransferRepository->save($internalTransfer);

        if(!$internalTransfer->getWallet()->getUser()->isGAuthEnabled()){
            // TODO
            // $this->notificationManager->create($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_CREATED, $internalTransfer);
        }

        $this->notificationManager->sendEmailNotification($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_CREATED, ['internalTransfer' => $internalTransfer, 'id' => $internalTransfer->getId()]);

        return $internalTransfer;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @param string $confirmationHash
     * @param string $gAuthCode
     * @throws AppException
     */
    public function confirmRequest(InternalTransfer $internalTransfer, string $confirmationHash, string $gAuthCode){
        if(!$internalTransfer->isNew()) throw new AppException('error.internal_transfer.status_does_not_allow');

        if(!$internalTransfer->isConfirmationHashValid($confirmationHash)) throw new AppException('error.internal_transfer.invalid_confirmation_code');
        if($internalTransfer->isConfirmationHashExpired()) throw new AppException('error.internal_transfer.expired_confirmation_code');

        if($internalTransfer->getWallet()->getUser()->isGAuthEnabled()){
            $this->googleAuthenticatorManager->verifyCode($internalTransfer->getWallet()->getUser()->getGAuthSecret(), $gAuthCode);
        }

        $this->pushForInternalTransferRequest($internalTransfer);
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return bool
     * @throws \Exception
     */
    public function pushForInternalTransferRequest(InternalTransfer $internalTransfer) : bool
    {
        $this->redisSubscribeManager->pushInternalTransferRequest($internalTransfer->getId());

        return true;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return InternalTransfer
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function reject(InternalTransfer $internalTransfer) : InternalTransfer
    {
        if(!$internalTransfer->isNew()) throw new AppException('error.internal_transfer.status_does_not_allow');

        $internalTransfer->setStatus(InternalTransfer::STATUS_REJECTED);
        $internalTransfer = $this->internalTransferRepository->save($internalTransfer);

        // TODO
        // $this->notificationManager->create($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_REJECTED, $internalTransfer);
        $this->notificationManager->sendEmailNotification($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_REJECTED, ['internalTransfer' => $internalTransfer, 'id' => $internalTransfer->getId()]);

        return $internalTransfer;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return InternalTransfer
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setRequested(InternalTransfer $internalTransfer) : InternalTransfer
    {
        if(!$internalTransfer->isNew()) throw new AppException('error.internal_transfer.status_does_not_allow');

        $internalTransfer->setStatus(InternalTransfer::STATUS_REQUEST);
        $internalTransfer = $this->internalTransferRepository->save($internalTransfer);

        return $internalTransfer;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return InternalTransfer
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function decline(InternalTransfer $internalTransfer) : InternalTransfer
    {
        if(!$internalTransfer->isRequest()) throw new AppException('error.internal_transfer.status_does_not_allow');

        $internalTransfer->setStatus(InternalTransfer::STATUS_DECLINED);
        $internalTransfer = $this->internalTransferRepository->save($internalTransfer);

        $totalAmount = $internalTransfer->getTotalAmount();
        $this->eventDispatcher->dispatch(WalletTransferInternalTransferEvent::NAME, new WalletTransferInternalTransferEvent($internalTransfer->getId(), WalletTransferInterface::TYPE_RELEASE, $internalTransfer->getWallet()->getId(), $totalAmount));

        // TODO
        // $this->notificationManager->create($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_DECLINED, $internalTransfer);
        $this->notificationManager->sendEmailNotification($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_DECLINED, ['internalTransfer' => $internalTransfer, 'id' => $internalTransfer->getId()]);

        return $internalTransfer;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return InternalTransfer
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approve(InternalTransfer $internalTransfer) : InternalTransfer
    {
        if(!$internalTransfer->isRequest()) throw new AppException('error.internal_transfer.status_does_not_allow');

        $internalTransfer->setStatus(InternalTransfer::STATUS_APPROVED);
        $internalTransfer = $this->internalTransferRepository->save($internalTransfer);

        $fromWallet = $internalTransfer->getWallet();
        $toWallet = $internalTransfer->getToWallet();

        $totalAmount = $internalTransfer->getTotalAmount();

        // TODO - save wallet balance

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setInternalTransferId($internalTransfer->getId());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $fromWallet->getId(), $totalAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_INTERNAL, $fromWallet->getId(), $totalAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_INTERNAL, $toWallet->getId(), $totalAmount);
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        // TODO zrobić powiadomienia mailowe
//        $this->notificationManager->create($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_APPROVED, $internalTransfer);
        $this->notificationManager->sendEmailNotification($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_APPROVED, ['internalTransfer' => $internalTransfer, 'id' => $internalTransfer->getId()]);

        return $internalTransfer;
    }

    /**
     * @param InternalTransfer $internalTransfer
     * @return InternalTransfer
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function revert(InternalTransfer $internalTransfer) : InternalTransfer
    {
        if(!$internalTransfer->isApproved()) throw new AppException('error.internal_transfer.status_does_not_allow');

        $internalTransfer->setStatus(InternalTransfer::STATUS_REVERTED);
        $internalTransfer = $this->internalTransferRepository->save($internalTransfer);

        $fromWallet = $internalTransfer->getWallet();
        $toWallet = $internalTransfer->getToWallet();

        $totalAmount = $internalTransfer->getTotalAmount();

        // TODO - save wallet balance

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setInternalTransferId($internalTransfer->getId());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_INTERNAL, $toWallet->getId(), $totalAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_INTERNAL, $fromWallet->getId(), $totalAmount);
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        // TODO zrobić powiadomienia mailowe
//        $this->notificationManager->create($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_REVERTED, $internalTransfer);
//        $this->notificationManager->sendEmailNotification($internalTransfer->getWallet()->getUser(), NotificationInterface::TYPE_INTERNAL_TRANSFER_REVERTED, ['internalTransfer' => $internalTransfer, 'id' => $internalTransfer->getId()]);

        return $internalTransfer;
    }
}
