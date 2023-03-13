<?php

namespace App\Manager;

use App\Document\Transfer;
use App\Entity\CheckoutOrder;
use App\Entity\Currency;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Entity\UserBank;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\WalletBank;
use App\Entity\Wallet\Withdrawal;
use App\Event\OrderReleaseAmountEvent;
use App\Exception\AppException;
use App\Model\SystemUserInterface;
use App\Model\PriceInterface;
use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Repository\WalletRepository;
use App\Resolver\FeeWalletResolver;
use App\Security\SystemTagAccessResolver;
use App\Security\TagAccessResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WalletManager
{
    /** @var WalletRepository */
    private $walletRepository;

    /** @var FeeWalletResolver */
    private $feeWalletResolver;

    /** @var WithdrawalManager */
    private $withdrawalManager;

    /** @var InternalTransferManager */
    private $internalTransferManager;

    /** @var TransferManager */
    private $transferManager;

    /** @var RedisSubscribeManager  */
    private $redisSubscribeManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var TagAccessResolver */
    private $tagAccessResolver;

    /** @var SystemTagAccessResolver */
    private $systemTagAccessResolver;

    /** @var FeeTransferManager */
    private $feeTransferManager;

    /** @var Wallet */
    private $wallet;

    /**
     * WalletManager constructor.
     * @param WalletRepository $walletRepository
     * @param FeeWalletResolver $feeWalletResolver
     * @param WithdrawalManager $withdrawalManager
     * @param InternalTransferManager $internalTransferManager
     * @param TransferManager $transferManager
     * @param RedisSubscribeManager $redisSubscribeManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param TagAccessResolver $tagAccessResolver
     * @param SystemTagAccessResolver $systemTagAccessResolver
     * @param FeeTransferManager $feeTransferManager
     */
    public function __construct(
        WalletRepository $walletRepository,
        FeeWalletResolver $feeWalletResolver,
        WithdrawalManager $withdrawalManager,
        InternalTransferManager $internalTransferManager,
        TransferManager $transferManager,
        RedisSubscribeManager $redisSubscribeManager,
        EventDispatcherInterface $eventDispatcher,
        TagAccessResolver $tagAccessResolver,
        SystemTagAccessResolver $systemTagAccessResolver,
        FeeTransferManager $feeTransferManager
    )
    {
        $this->walletRepository = $walletRepository;
        $this->feeWalletResolver = $feeWalletResolver;
        $this->withdrawalManager = $withdrawalManager;
        $this->internalTransferManager = $internalTransferManager;
        $this->transferManager = $transferManager;
        $this->redisSubscribeManager = $redisSubscribeManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->tagAccessResolver = $tagAccessResolver;
        $this->systemTagAccessResolver = $systemTagAccessResolver;
        $this->feeTransferManager = $feeTransferManager;
    }

    /**
     * @param Wallet $wallet
     */
    public function setWallet(Wallet $wallet): void
    {
        $this->wallet = $wallet;
    }

    /**
     * @return TransferManager
     */
    public function getTransferManager(): TransferManager
    {
        return $this->transferManager;
    }

    /**
     * Load Wallet to the class by $walletId
     *
     * @param int $walletId
     * @return Wallet
     * @throws AppException
     */
    public function load(int $walletId) : Wallet
    {
        $this->wallet = $this->walletRepository->findOrException($walletId);

        return $this->wallet;
    }

    /**
     * Load Wallet to the class by $user and $currencyShortName
     *
     * @param User $user
     * @param string $currencyShortName
     * @return Wallet
     * @throws AppException
     */
    public function loadByUserAndCurrency(User $user, string $currencyShortName) : Wallet
    {
        $this->wallet = $this->walletRepository->findByUserAndCurrencyShortName($user->getId(), $currencyShortName);
        if(!($this->wallet instanceof Wallet)) throw new AppException('error.wallet.not_found');

        return $this->wallet;
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @param UserBank|null $userBank
     * @param WalletBank|null $walletBank
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function requestFiatWithdrawal(Wallet $wallet, $amount, UserBank $userBank = null, WalletBank $walletBank = null) : Withdrawal
    {
        $feeAmount = $this->withdrawalManager->calculateFee($wallet, $amount);

        $address = '';
        if($userBank instanceof UserBank){
            $address = $userBank->getIban();
        }elseif($walletBank instanceof WalletBank){
            $address = $walletBank->getIban();
        }

        return $this->withdrawalManager->request($wallet, $amount, $feeAmount, $address, $userBank, $walletBank);
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @param string $address
     * @return Withdrawal
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function requestCryptoWithdrawal(Wallet $wallet, $amount, string $address) : Withdrawal
    {
        $feeAmount = $this->withdrawalManager->calculateFee($wallet, $amount);

        return $this->withdrawalManager->request($wallet, $amount, $feeAmount, $address, null, null);
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @param Wallet $toWallet
     * @return \App\Entity\Wallet\InternalTransfer
     * @throws AppException
     */
    public function requestInternalTransfer(Wallet $wallet, $amount, Wallet $toWallet){
        $feeAmount = $this->internalTransferManager->calculateFee();

        return $this->internalTransferManager->request($wallet, $toWallet, $amount, $feeAmount);
    }

    /**
     * Generate new empty wallet for $user by $currency
     *
     * @param User $user
     * @param Currency $currency
     * @return Wallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateWallet(User $user, Currency $currency){
        $wallet = new Wallet($user, $currency, $currency->getFullName());

        return $this->walletRepository->save($wallet);
    }

    /**
     * @param Wallet $wallet
     * @return Wallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(Wallet $wallet) : Wallet
    {
        $this->wallet = $this->walletRepository->save($wallet);

        return $this->wallet;
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @return Wallet
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function blockAmount(Wallet $wallet, $amount)
    {
        $newAmount = bcadd($wallet->getAmountPending(), $amount, PriceInterface::BC_SCALE);
        $wallet->setAmountPending($newAmount);

        $wallet = $this->walletRepository->save($wallet);

        $this->transferManager->create(Transfer::TYPE_BLOCK, $wallet, $amount);

        return $wallet;
    }

    /**
     * Release pending amount from the wallet loaded in the class
     *
     * @param Wallet $wallet
     * @param $amount
     * @return Wallet
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function releaseAmount(Wallet $wallet, $amount) : Wallet
    {
        $newAmount = bcsub($wallet->getAmountPending(), $amount, PriceInterface::BC_SCALE);
        $wallet->setAmountPending($newAmount);

        $wallet = $this->walletRepository->save($wallet);

        $this->transferManager->create(Transfer::TYPE_RELEASE, $wallet, $amount);

        return $wallet;
    }

    /**
     * Fund the wallet loaded in the class
     *
     * @param Wallet $wallet
     * @param $amount
     * @param bool $isFee
     * @param bool $isInternal
     * @param bool $isDeposit
     * @return Wallet
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fund(Wallet $wallet, $amount, bool $isFee = false, bool $isInternal = false, bool $isDeposit = false) : Wallet
    {
        $newAmount = bcadd($wallet->getAmount(), $amount, PriceInterface::BC_SCALE);
        $wallet->setAmount($newAmount);

        $wallet = $this->walletRepository->save($wallet);

        if($isFee){
            $this->transferManager->create(Transfer::TYPE_FEE_TRANSFER_TO, $wallet, $amount);
        }elseif($isInternal){
            $this->transferManager->create(Transfer::TYPE_INTERNAL_TRANSFER_TO, $wallet, $amount);
        }elseif($isDeposit){
            $this->transferManager->create(Transfer::TYPE_DEPOSIT, $wallet, $amount);
        }else{
            $this->transferManager->create(Transfer::TYPE_TRANSFER_TO, $wallet, $amount);
        }

        return $wallet;
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @return Wallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fundDepositAmount(Wallet $wallet, $amount) : Wallet
    {
        if($wallet->isFiatWalletPLN() || $wallet->isFiatWalletEUR()){
            $newAmountDeposits = bcadd($wallet->getAmountDeposits(), $amount, PriceInterface::BC_SCALE);
            $wallet->setAmountDeposits($newAmountDeposits);

            $wallet = $this->walletRepository->save($wallet);
        }

        return $wallet;
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @return Wallet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deFundDepositAmount(Wallet $wallet, $amount) : Wallet
    {
        if($wallet->isFiatWalletPLN() || $wallet->isFiatWalletEUR()){
            $newAmountDeposits = bcsub($wallet->getAmountDeposits(), $amount, PriceInterface::BC_SCALE);
            $comp = bccomp($newAmountDeposits, 0, PriceInterface::BC_SCALE);
            if($comp === 1){
                $wallet->setAmountDeposits($newAmountDeposits);
            }else{
                $wallet->setAmountDeposits(0);
            }

            $wallet = $this->walletRepository->save($wallet);
        }

        return $wallet;
    }

    /**
     * @param Wallet $wallet
     * @param $amount
     * @param bool $isFee
     * @param bool $isInternal
     * @param bool $isWithdrawal
     * @return Wallet
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deFund(Wallet $wallet, $amount, bool $isFee = false, bool $isInternal = false, bool $isWithdrawal = false) : Wallet
    {
        $newAmount = bcsub($wallet->getAmount(), $amount, PriceInterface::BC_SCALE);
        $wallet->setAmount($newAmount);

        $wallet = $this->walletRepository->save($wallet);

        if($isFee){
            $this->transferManager->create(Transfer::TYPE_FEE_TRANSFER_FROM, $wallet, $amount);
        }elseif($isInternal){
            $this->transferManager->create(Transfer::TYPE_INTERNAL_TRANSFER_FROM, $wallet, $amount);
        }elseif($isWithdrawal){
            $this->transferManager->create(Transfer::TYPE_WITHDRAWAL, $wallet, $amount);
        }else{
            $this->transferManager->create(Transfer::TYPE_TRANSFER_FROM, $wallet, $amount);
        }

        return $wallet;
    }

    /**
     * @param Wallet $fromWallet
     * @param Wallet $toWallet
     * @param $amount
     * @return bool
     * @throws AppException
     */
    public function internalTransfer(Wallet $fromWallet, Wallet $toWallet, $amount) : bool
    {
        if(!is_numeric($amount)) throw new AppException('Amount is invalid');

        if($fromWallet->getId() === $toWallet->getId()){
            throw new AppException('Internal transfer inside the same wallet is not allowed.');
        }

        if($fromWallet->getCurrency()->getId() !== $toWallet->getCurrency()->getId()) {
            throw new AppException('Internal transfer not allowed for wallets with different currencies.');
        }

        if(!$fromWallet->isTransferAllowed($amount)){
            throw new AppException('error.wallet.insufficient_funds');
        }

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_INTERNAL, $fromWallet->getId(), $amount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_INTERNAL, $toWallet->getId(), $amount);
        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        return true;
    }

    /**
     * @param Trade $trade
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function revertTheTrade(Trade $trade){
        /** @var Wallet $baseOfferWallet */
        $baseOfferWallet = $trade->getOrderSell()->getBaseCurrencyWallet();
        /** @var Wallet $baseBidWallet */
        $baseBidWallet = $trade->getOrderBuy()->getBaseCurrencyWallet();

        /** @var Wallet $quotedOfferWallet */
        $quotedOfferWallet = $trade->getOrderSell()->getQuotedCurrencyWallet();
        /** @var Wallet $quotedBidWallet */
        $quotedBidWallet = $trade->getOrderBuy()->getQuotedCurrencyWallet();

        $baseAmount = $trade->getAmount();
        $quotedAmount = $trade->getQuotedAmount();

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setTradeId($trade->getId());

        // revert transfer base currency
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND, $baseBidWallet->getId(), $baseAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND, $baseOfferWallet->getId(), $baseAmount);

        // revert transfer quoted currency
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND, $quotedOfferWallet->getId(), $quotedAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND, $quotedBidWallet->getId(), $quotedAmount);

        // transfer fees to the system wallets
        $feeOffer = $trade->getFeeOffer();
        $feeBid = $trade->getFeeBid();

        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_FEE, $quotedOfferWallet->getId(), $feeOffer);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_FEE, $baseBidWallet->getId(), $feeBid);

        /** @var Wallet $feeOfferWallet */
        $feeOfferWallet = $this->feeWalletResolver->resolveWallet($trade, true);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_FEE, $feeOfferWallet->getId(), $feeOffer);

        /** @var Wallet $feeBidWallet */
        $feeBidWallet = $this->feeWalletResolver->resolveWallet($trade);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_FEE, $feeBidWallet->getId(), $feeBid);

        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        ####### release memory

        $feeOfferWallet = null;
        unset($feeOfferWallet);

        $feeBidWallet = null;
        unset($feeBidWallet);
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function transferTheCheckoutOrder(CheckoutOrder $checkoutOrder)
    {
        $amount = $checkoutOrder->getAmount();
        /** @var Currency $currency */
        $currency = $checkoutOrder->getCurrencyPair()->getBaseCurrency();

        // resolve user's wallet by currency
        /** @var Wallet $userWallet */
        $userWallet = $this->walletRepository->getOneByCurrencyUserId($currency, $checkoutOrder->getUser()->getId());
        if(!($userWallet instanceof Wallet)) throw new AppException('User wallet not found');

        // resolve checkout user wallet by currency
        /** @var Wallet $checkoutUserWallet */
        $checkoutUserWallet = $this->walletRepository->getOneByCurrencyUserId($currency, SystemUserInterface::CHECKOUT_LIQ_USER);
        if(!($checkoutUserWallet instanceof Wallet)) throw new AppException('Checkout liquidity user wallet not found');

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setCheckoutOrderId($checkoutOrder->getId());

        // transfer bought amount
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND, $userWallet->getId(), $amount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND, $checkoutUserWallet->getId(), $amount);

        // TODO transfer FEE from $checkoutUser Quoted Wallet to Checkout Fee Wallet

        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);
    }

    /**
     * @param Trade $trade
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function transferTheTrade(Trade $trade){
        /** @var Wallet $baseOfferWallet */
        $baseOfferWallet = $trade->getOrderSell()->getBaseCurrencyWallet();
        /** @var Wallet $baseBidWallet */
        $baseBidWallet = $trade->getOrderBuy()->getBaseCurrencyWallet();

        /** @var Wallet $quotedOfferWallet */
        $quotedOfferWallet = $trade->getOrderSell()->getQuotedCurrencyWallet();
        /** @var Wallet $quotedBidWallet */
        $quotedBidWallet = $trade->getOrderBuy()->getQuotedCurrencyWallet();

        $baseAmount = $trade->getAmount();
        $quotedAmount = $trade->getQuotedAmount();

        /** @var WalletTransferBatchModel $walletTransferBatchModel */
        $walletTransferBatchModel = new WalletTransferBatchModel();
        $walletTransferBatchModel->setTradeId($trade->getId());

        // transfer and release base currency
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND, $baseBidWallet->getId(), $baseAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND, $baseOfferWallet->getId(), $baseAmount);

        if($trade->getOrderSell()->isExternalLiquidityOrder()){
            ## TODO tylko LIQ - wtedy nie robimy release
        }else{
            if(is_numeric($trade->getOrderSell()->getAmountBlocked())){
                if($trade->getOrderSell()->getisFilled()){
                    $releaseAmount = $trade->getOrderSell()->getAmountBlocked();
                }else{
                    $releaseAmount = $trade->getOrderSell()->resolveReleaseAmount($baseAmount);
                }

                $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $baseOfferWallet->getId(), $releaseAmount);
                $this->eventDispatcher->dispatch(OrderReleaseAmountEvent::NAME, new OrderReleaseAmountEvent($trade->getOrderSell(), $releaseAmount));
            }else{
                $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $baseOfferWallet->getId(), $baseAmount);
            }
        }

        // transfer and release quoted currency
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND, $quotedOfferWallet->getId(), $quotedAmount);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND, $quotedBidWallet->getId(), $quotedAmount);

        if($trade->getOrderBuy()->isExternalLiquidityOrder()){
            ## TODO tylko LIQ - wtedy nie robimy release
        }else{
            if(is_numeric($trade->getOrderBuy()->getAmountBlocked())){
                if($trade->getOrderBuy()->getisFilled()){
                    $releaseAmount = $trade->getOrderBuy()->getAmountBlocked();
                }else{
                    $releaseAmount = $trade->getOrderBuy()->resolveReleaseAmount($quotedAmount);
                }

                $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $quotedBidWallet->getId(), $releaseAmount);
                $this->eventDispatcher->dispatch(OrderReleaseAmountEvent::NAME, new OrderReleaseAmountEvent($trade->getOrderBuy(), $releaseAmount));
            }else{
                $walletTransferBatchModel->push(WalletTransferInterface::TYPE_RELEASE, $quotedBidWallet->getId(), $quotedAmount);
            }
        }

        // transfer fees to the system wallets
        $feeOffer = $trade->getFeeOffer();
        $feeBid = $trade->getFeeBid();

        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_FEE, $quotedOfferWallet->getId(), $feeOffer);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_DEFUND_FEE, $baseBidWallet->getId(), $feeBid);

        /** @var Wallet $feeOfferWallet */
        $feeOfferWallet = $this->feeWalletResolver->resolveWallet($trade, true);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_FEE, $feeOfferWallet->getId(), $feeOffer);

        /** @var Wallet $feeBidWallet */
        $feeBidWallet = $this->feeWalletResolver->resolveWallet($trade);
        $walletTransferBatchModel->push(WalletTransferInterface::TYPE_FUND_FEE, $feeBidWallet->getId(), $feeBid);

        $this->redisSubscribeManager->pushWalletTransferBatch($walletTransferBatchModel);

        ## create fees categories

        try{
            $this->feeTransferManager->createTradeFeeTransfer($trade, $feeOfferWallet->getId(), $feeOffer);
            $this->feeTransferManager->createTradeFeeTransfer($trade, $feeBidWallet->getId(), $feeBid);
        }catch (\Exception $exception){
            // TODO logować ewentualne exceptions
        }

        ####### release memory

        $feeOfferWallet = null;
        unset($feeOfferWallet);

        $feeBidWallet = null;
        unset($feeBidWallet);
    }

    /**
     * @return WalletRepository
     */
    public function getWalletRepository(): WalletRepository
    {
        return $this->walletRepository;
    }
}
