<?php

namespace App\Manager;

use App\Document\NotificationInterface;
use App\Document\WithdrawalApproveRequest;
use App\Entity\Currency;
use App\Entity\User;
use App\Entity\UserBank;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\WalletBank;
use App\Entity\Wallet\Withdrawal;
use App\Event\WalletBalance\WalletBalanceBeforeWithdrawalEvent;
use App\Event\WalletTransferEvent;
use App\Event\WalletTransferWithdrawalEvent;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\Wallet\WithdrawalRepository;
use App\Resolver\FeeWalletResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WithdrawalManager
{
    /** @var WithdrawalRepository */
    private $withdrawalRepository;

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

    /** @var Withdrawal */
    private $withdrawal;

    /**
     * WithdrawalManager constructor.
     * @param WithdrawalRepository $withdrawalRepository
     * @param NotificationManager $notificationManager
     * @param RedisSubscribeManager $redisSubscribeManager
     * @param FeeWalletResolver $feeWalletResolver
     * @param EventDispatcherInterface $eventDispatcher
     * @param GoogleAuthenticatorManager $googleAuthenticatorManager
     * @param FeeTransferManager $feeTransferManager
     */
    public function __construct(WithdrawalRepository $withdrawalRepository, NotificationManager $notificationManager, RedisSubscribeManager $redisSubscribeManager, FeeWalletResolver $feeWalletResolver, EventDispatcherInterface $eventDispatcher, GoogleAuthenticatorManager $googleAuthenticatorManager, FeeTransferManager $feeTransferManager)
    {
        $this->withdrawalRepository = $withdrawalRepository;
        $this->notificationManager = $notificationManager;
        $this->redisSubscribeManager = $redisSubscribeManager;
        $this->feeWalletResolver = $feeWalletResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->googleAuthenticatorManager = $googleAuthenticatorManager;
        $this->feeTransferManager = $feeTransferManager;
    }

    /**
     * Load Withdrawal to the class by $withdrawalId
     *
     * @param int $withdrawalId
     * @return Withdrawal
     * @throws AppException
     */
    public function load(int $withdrawalId) : Withdrawal
    {
        $this->withdrawal = $this->withdrawalRepository->find($withdrawalId);
        if(!($this->withdrawal instanceof Withdrawal)) throw new AppException('error.withdrawal.not_found');

        return $this->withdrawal;
    }

    /**
     * @param Wallet $wallet
     * @param string $amount
     * @return string
     */
    public function calculateFee(Wallet $wallet, string $amount) : string
    {
        $fee = 0;

        $feeType = $wallet->getCurrency()->getFeeType();
        if($feeType === Currency::FEE_TYPE_FIXED){
            $fee = $wallet->getCurrency()->getFee();
        }elseif($feeType === Currency::FEE_TYPE_PERCENTAGE){
            $mul = bcmul($wallet->getCurrency()->getFee(), $amount, PriceInterface::BC_SCALE);
            $fee = bcdiv($mul, '100', PriceInterface::BC_SCALE);
        }

//        $comp = bccomp($fee, $wallet->getCurrency()->getMinFee(), PriceInterface::BC_SCALE);
//        if($comp === -1){
//            $fee = $wallet->getCurrency()->getMinFee();
//        }

        return (string) $fee;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return bool
     * @throws AppException
     */
    public function verifyForRequest(Withdrawal $withdrawal) : bool
    {
        if(empty($withdrawal->getAddress())) throw new AppException('error.invalid.address');

        $totalAmount = $withdrawal->getTotalAmount();

        if(!$withdrawal->isNew()) throw new AppException('error.withdrawal.status_does_not_allow');
        if(!$withdrawal->getWallet()->isTransferAllowed($totalAmount)) throw new AppException('error.wallet.insufficient_funds');
        if(!$withdrawal->getWallet()->isWithdrawalAllowed($totalAmount)) throw new AppException('error.wallet.withdrawal_not_allowed');

        return true;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return bool
     */
    public function verifyForApprove(Withdrawal $withdrawal) : bool
    {
        // TODO

        return true;
    }

    /**
     * @param Wallet $wallet
     * @param string $amount
     * @param string $fee
     * @param string $address
     * @param UserBank|null $userBank
     * @param WalletBank|null $walletBank
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function request(Wallet $wallet, string $amount, string $fee, string $address, UserBank $userBank = null, WalletBank $walletBank = null) : Withdrawal
    {
        /** @var Withdrawal $withdrawal */
        $withdrawal = new Withdrawal($wallet, $amount, $fee, $address);
        if($userBank instanceof UserBank){
            $withdrawal->setUserBank($userBank);
        }
        if($walletBank instanceof WalletBank){
            $withdrawal->setWalletBank($walletBank);
        }

        $this->verifyForRequest($withdrawal);

        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        if(!$withdrawal->getWallet()->getUser()->isGAuthEnabled()){
            $this->notificationManager->create($withdrawal->getWallet()->getUser(), NotificationInterface::TYPE_WITHDRAWAL_CREATED, $withdrawal);
        }

        $this->notificationManager->sendEmailNotification($withdrawal->getWallet()->getUser(), NotificationInterface::TYPE_WITHDRAWAL_CREATED, ['withdrawal' => $withdrawal, 'id' => $withdrawal->getId()]);

        return $withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     * @param string $confirmationHash
     * @param string $gAuthCode
     * @param bool $gAuthCodeVerification
     * @throws AppException
     */
    public function confirmRequest(Withdrawal $withdrawal, string $confirmationHash, string $gAuthCode, bool $gAuthCodeVerification = true)
    {
        if(!$withdrawal->isConfirmationHashValid($confirmationHash)) throw new AppException('error.withdrawal.invalid_confirmation_code');
        if($withdrawal->isConfirmationHashExpired()) throw new AppException('error.withdrawal.expired_confirmation_code');

        if($gAuthCodeVerification){
            if($withdrawal->getWallet()->getUser()->isGAuthEnabled()){
                $this->googleAuthenticatorManager->verifyCode($withdrawal->getWallet()->getUser()->getGAuthSecret(), $gAuthCode);
            }
        }

        $this->pushForWithdrawalRequest($withdrawal);
    }

    /**
     * @param Withdrawal $withdrawal
     * @return bool
     * @throws AppException
     */
    public function pushForWithdrawalRequest(Withdrawal $withdrawal) : bool
    {
        if(!$withdrawal->isNew()) throw new AppException('error.withdrawal.status_does_not_allow');

        try{
            $this->redisSubscribeManager->pushWithdrawalRequest($withdrawal->getId());
        }catch (\Exception $exception){}

        return true;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function reject(Withdrawal $withdrawal) : Withdrawal
    {
        if(!$withdrawal->isNew()) throw new AppException('error.withdrawal.status_does_not_allow');

        $withdrawal->setStatus(Withdrawal::STATUS_REJECTED);
        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        $this->notificationManager->create($withdrawal->getWallet()->getUser(), NotificationInterface::TYPE_WITHDRAWAL_REJECTED, $withdrawal);
        $this->notificationManager->sendEmailNotification($withdrawal->getWallet()->getUser(), NotificationInterface::TYPE_WITHDRAWAL_REJECTED, ['withdrawal' => $withdrawal, 'id' => $withdrawal->getId()]);

        return $withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setRequested(Withdrawal $withdrawal) : Withdrawal
    {
        if(!$withdrawal->isNew()) throw new AppException('error.withdrawal.status_does_not_allow');

        $withdrawal->setStatus(Withdrawal::STATUS_REQUEST);
        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        return $withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendForExternalApproval(Withdrawal $withdrawal) : Withdrawal
    {
        if(!$withdrawal->isRequest()) throw new AppException('error.withdrawal.status_does_not_allow');

        $withdrawal->setStatus(Withdrawal::STATUS_EXTERNAL_APPROVAL);
        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        return $withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return bool
     * @throws AppException
     */
    public function pushForWithdrawalApproveRequest(Withdrawal $withdrawal) : bool
    {
        if(!($withdrawal->isExternalApproval() || $withdrawal->isRequest())) throw new AppException('error.withdrawal.status_does_not_allow');

        try{
            $this->redisSubscribeManager->pushWithdrawalApproveRequest($withdrawal->getId());
        }catch (\Exception $exception){}

        return true;
    }

    /**
     * @param Withdrawal $withdrawal
     * @param User $user
     * @return Withdrawal
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function setApprovedBy(Withdrawal $withdrawal, User $user) : Withdrawal
    {
        $withdrawal->setApprovedByUser($user);
        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        return $withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function approve(Withdrawal $withdrawal) : Withdrawal
    {
        if(!($withdrawal->isExternalApproval() || $withdrawal->isRequest())) throw new AppException('error.withdrawal.status_does_not_allow');

        $withdrawal->setStatus(Withdrawal::STATUS_APPROVED);
        $withdrawal->setApprovedAt(new \DateTime('now'));
        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        /** @var Wallet $wallet */
        $wallet = $withdrawal->getWallet();

        // save wallet balance
        $this->eventDispatcher->dispatch(WalletBalanceBeforeWithdrawalEvent::NAME, new WalletBalanceBeforeWithdrawalEvent($withdrawal->getWallet(), $withdrawal->getWallet()->getAmount(), $withdrawal));

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setWithdrawalId($withdrawal->getId());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $wallet->getId(), $withdrawal->getAmount());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_WITHDRAWAL, $wallet->getId(), $withdrawal->getAmount());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $wallet->getId(), $withdrawal->getFee());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_FEE, $wallet->getId(), $withdrawal->getFee());

        /** @var Wallet $feeWallet */
        $feeWallet = $this->feeWalletResolver->resolveByCurrency($wallet->getCurrency());
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_FEE, $feeWallet->getId(), $withdrawal->getFee());

        try{
            $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);
        }catch (\Exception $exception){
            // TODO logować ewentualne exceptions
        }

        try{
            $this->feeTransferManager->createWithdrawalFeeTransfer($withdrawal, $feeWallet->getId(), $withdrawal->getFee());
        }catch (\Exception $exception){
            // TODO logować ewentualne exceptions
        }

        $this->notificationManager->create($wallet->getUser(), NotificationInterface::TYPE_WITHDRAWAL_APPROVED, $withdrawal);
        $this->notificationManager->sendEmailNotification($wallet->getUser(), NotificationInterface::TYPE_WITHDRAWAL_APPROVED, ['withdrawal' => $withdrawal, 'id' => $withdrawal->getId()]);

        return $withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function decline(Withdrawal $withdrawal) : Withdrawal
    {
        if(!($withdrawal->isExternalApproval() || $withdrawal->isRequest())) throw new AppException('error.withdrawal.status_does_not_allow');

//        if(!$withdrawal->isRequest()) throw new AppException('error.withdrawal.status_does_not_allow');

        $withdrawal->setStatus(Withdrawal::STATUS_DECLINED);
        $withdrawal = $this->withdrawalRepository->save($withdrawal);

        $totalAmount = $withdrawal->getTotalAmount();
        $this->eventDispatcher->dispatch(WalletTransferWithdrawalEvent::NAME, new WalletTransferWithdrawalEvent($withdrawal->getId(), WalletTransferInterface::TYPE_RELEASE, $withdrawal->getWallet()->getId(), $totalAmount));

        $this->notificationManager->create($withdrawal->getWallet()->getUser(), NotificationInterface::TYPE_WITHDRAWAL_DECLINED, $withdrawal);
        $this->notificationManager->sendEmailNotification($withdrawal->getWallet()->getUser(), NotificationInterface::TYPE_WITHDRAWAL_DECLINED, ['withdrawal' => $withdrawal, 'id' => $withdrawal->getId()]);

        return $withdrawal;
    }
}
