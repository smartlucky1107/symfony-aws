<?php

namespace App\Manager\Processor;

use App\Entity\CheckoutOrder;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\InternalTransfer;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\Withdrawal;
use App\Event\WalletBalance\WalletBalanceAfterDepositEvent;
use App\Event\WalletBalance\WalletBalanceAfterOrderEvent;
use App\Event\WalletBalance\WalletBalanceAfterTradeEvent;
use App\Event\WalletBalance\WalletBalanceAfterWithdrawalEvent;
use App\Exception\AppException;
use App\Manager\WalletManager;
use App\Model\WalletTransfer\WalletTransferBatchModel;
use App\Model\WalletTransfer\WalletTransferInterface;
use App\Model\WalletTransfer\WalletTransferItem;
use App\Repository\CheckoutOrderRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\OrderBook\TradeRepository;
use App\Repository\Wallet\DepositRepository;
use App\Repository\Wallet\InternalTransferRepository;
use App\Repository\Wallet\WithdrawalRepository;
use App\Repository\WalletRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class WalletTransferProcessor
{
    /** @var WalletRepository */
    private $walletRepository;

    /** @var TradeRepository */
    private $tradeRepository;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var DepositRepository */
    private $depositRepository;

    /** @var WithdrawalRepository */
    private $withdrawalRepository;

    /** @var InternalTransferRepository */
    private $internalTransferRepository;

    /** @var CheckoutOrderRepository */
    private $checkoutOrderRepository;

    /** @var WalletManager */
    private $walletManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * WalletTransferProcessor constructor.
     * @param WalletRepository $walletRepository
     * @param TradeRepository $tradeRepository
     * @param OrderRepository $orderRepository
     * @param DepositRepository $depositRepository
     * @param WithdrawalRepository $withdrawalRepository
     * @param InternalTransferRepository $internalTransferRepository
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param WalletManager $walletManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(WalletRepository $walletRepository, TradeRepository $tradeRepository, OrderRepository $orderRepository, DepositRepository $depositRepository, WithdrawalRepository $withdrawalRepository, InternalTransferRepository $internalTransferRepository, CheckoutOrderRepository $checkoutOrderRepository, WalletManager $walletManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->walletRepository = $walletRepository;
        $this->tradeRepository = $tradeRepository;
        $this->orderRepository = $orderRepository;
        $this->depositRepository = $depositRepository;
        $this->withdrawalRepository = $withdrawalRepository;
        $this->internalTransferRepository = $internalTransferRepository;
        $this->checkoutOrderRepository = $checkoutOrderRepository;
        $this->walletManager = $walletManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param WalletTransferInterface $walletTransfer
     * @param null $relatedObject
     * @return bool
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process(WalletTransferInterface $walletTransfer, $relatedObject = null) : bool
    {
        $stopwatch = new Stopwatch(true);
        $stopwatch->start('process');

        $this->walletRepository->checkConnection();

        /** @var Wallet $wallet */
        $wallet = $this->walletRepository->find($walletTransfer->getWalletId());
        if(!($wallet instanceof Wallet)) throw new AppException('wallet '.$walletTransfer->getWalletId().' not found');

        $stopwatch->lap('process');

        if($relatedObject instanceof Trade) {
            $this->walletManager->getTransferManager()->setTrade($relatedObject);
        }elseif($relatedObject instanceof Order){
            $this->walletManager->getTransferManager()->setOrder($relatedObject);
        }elseif($relatedObject instanceof Deposit){
            $this->walletManager->getTransferManager()->setDeposit($relatedObject);
        }elseif($relatedObject instanceof Withdrawal){
            $this->walletManager->getTransferManager()->setWithdrawal($relatedObject);
        }elseif($relatedObject instanceof InternalTransfer){
            $this->walletManager->getTransferManager()->setInternalTransfer($relatedObject);
        }elseif($relatedObject instanceof CheckoutOrder){
            $this->walletManager->getTransferManager()->setCheckoutOrder($relatedObject);
        }

        $stopwatch->lap('process');

//        dump('transfer type ' . $walletTransfer->getType());
        switch ($walletTransfer->getType()) {
            case WalletTransferInterface::TYPE_BLOCK:
                $wallet = $this->walletManager->blockAmount($wallet, $walletTransfer->getAmount());
                break;
            case WalletTransferInterface::TYPE_RELEASE:
                $wallet = $this->walletManager->releaseAmount($wallet, $walletTransfer->getAmount());
                break;
            case WalletTransferInterface::TYPE_FUND:
                $wallet = $this->walletManager->fund($wallet, $walletTransfer->getAmount());
                break;
            case WalletTransferInterface::TYPE_FUND_FEE:
                $wallet = $this->walletManager->fund($wallet, $walletTransfer->getAmount(), true);
                break;
            case WalletTransferInterface::TYPE_DEFUND:
                $wallet = $this->walletManager->deFund($wallet, $walletTransfer->getAmount());
                break;
            case WalletTransferInterface::TYPE_DEFUND_FEE:
                $wallet = $this->walletManager->deFund($wallet, $walletTransfer->getAmount(), true);
                break;
            case WalletTransferInterface::TYPE_DEPOSIT:
                $wallet = $this->walletManager->fundDepositAmount($wallet, $walletTransfer->getAmount());
                $wallet = $this->walletManager->fund($wallet, $walletTransfer->getAmount(), false, false, true);
                break;
            case WalletTransferInterface::TYPE_WITHDRAWAL:
                $wallet = $this->walletManager->deFundDepositAmount($wallet, $walletTransfer->getAmount());
                $wallet = $this->walletManager->deFund($wallet, $walletTransfer->getAmount(), false, false, true);
                break;
            case WalletTransferInterface::TYPE_FUND_INTERNAL:
                $wallet = $this->walletManager->fund($wallet, $walletTransfer->getAmount(), false, true);
                break;
            case WalletTransferInterface::TYPE_DEFUND_INTERNAL:
                $wallet = $this->walletManager->deFund($wallet, $walletTransfer->getAmount(), false, true);
                break;
            default:
                throw new AppException('type not allowed: ' . $walletTransfer->getType());
        }

        $stopwatch->lap('process');

        if($relatedObject instanceof Trade) {
            /** @var Trade $trade */
            $trade = $relatedObject;

            // save wallet balance for trade
            $this->eventDispatcher->dispatch(WalletBalanceAfterTradeEvent::NAME, new WalletBalanceAfterTradeEvent($wallet, $wallet->getAmount(), $trade));

            // save wallet balance for orders
            $this->eventDispatcher->dispatch(WalletBalanceAfterOrderEvent::NAME, new WalletBalanceAfterOrderEvent($wallet, $wallet->getAmount(), $trade->getOrderSell()));
            $this->eventDispatcher->dispatch(WalletBalanceAfterOrderEvent::NAME, new WalletBalanceAfterOrderEvent($wallet, $wallet->getAmount(), $trade->getOrderBuy()));
        }elseif($relatedObject instanceof Order){
            // wallet balance saving is not required
        }elseif($relatedObject instanceof Deposit){
            /** @var Deposit $deposit */
            $deposit = $relatedObject;

            // save wallet balance for deposit
            $this->eventDispatcher->dispatch(WalletBalanceAfterDepositEvent::NAME, new WalletBalanceAfterDepositEvent($wallet, $wallet->getAmount(), $deposit));
        }elseif($relatedObject instanceof Withdrawal){
            /** @var Withdrawal $withdrawal */
            $withdrawal = $relatedObject;

            // save wallet balance for withdrawal
            $this->eventDispatcher->dispatch(WalletBalanceAfterWithdrawalEvent::NAME, new WalletBalanceAfterWithdrawalEvent($wallet, $wallet->getAmount(), $withdrawal));
        }elseif($relatedObject instanceof InternalTransfer){
            // TODO
            // TODO
            // TODO
        }elseif($relatedObject instanceof CheckoutOrder){
            // TODO
            // TODO
            // TODO
        }

//        dump('======== process STOPWATCH');
//        dump($stopwatch->stop('process')->getPeriods());

        return true;
    }

    /**
     * @param WalletTransferBatchModel $walletTransferBatchModel
     * @return WalletTransferBatchModel
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processBatch(WalletTransferBatchModel $walletTransferBatchModel) : WalletTransferBatchModel
    {
        $stopwatch = new Stopwatch(true);
        $stopwatch->start('processBatch');

        $this->walletRepository->checkConnection();

        $relatedObject = null;
        if($walletTransferBatchModel->getTradeId()) {
            /** @var Trade $trade */
            $trade = $this->tradeRepository->find($walletTransferBatchModel->getTradeId());
            if ($trade instanceof Trade) $relatedObject = $trade;
        }elseif($walletTransferBatchModel->getOrderId()){
            /** @var Order $order */
            $order = $this->orderRepository->find($walletTransferBatchModel->getOrderId());
            if($order instanceof Order) $relatedObject = $order;
        }elseif($walletTransferBatchModel->getDepositId()){
            /** @var Deposit $deposit */
            $deposit = $this->depositRepository->find($walletTransferBatchModel->getDepositId());
            if($deposit instanceof Deposit) $relatedObject = $deposit;
        }elseif($walletTransferBatchModel->getWithdrawalId()){
            /** @var Withdrawal $withdrawal */
            $withdrawal = $this->withdrawalRepository->find($walletTransferBatchModel->getWithdrawalId());
            if($withdrawal instanceof Withdrawal) $relatedObject = $withdrawal;
        }elseif($walletTransferBatchModel->getInternalTransferId()){
            /** @var InternalTransfer $internalTransfer */
            $internalTransfer = $this->internalTransferRepository->find($walletTransferBatchModel->getInternalTransferId());
            if($internalTransfer instanceof InternalTransfer) $relatedObject = $internalTransfer;
        }elseif($walletTransferBatchModel->getCheckoutOrderId()){
            /** @var CheckoutOrder $checkoutOrder */
            $checkoutOrder = $this->checkoutOrderRepository->find($walletTransferBatchModel->getCheckoutOrderId());
            if($checkoutOrder instanceof CheckoutOrder) $relatedObject = $checkoutOrder;
        }

        $stopwatch->lap('processBatch');

        /** @var WalletTransferItem $walletTransferItem */
        foreach($walletTransferBatchModel->getWalletTransfers() as $walletTransferItem){
            $result = $this->process($walletTransferItem, $relatedObject);

            if($result){
                $walletTransferBatchModel->addWalletTransferProcessed($walletTransferItem);
            }
        }

//        dump('======== processBatch STOPWATCH');
//        dump($stopwatch->stop('processBatch')->getDuration());

        $relatedObject = null;
        unset($relatedObject);

        return $walletTransferBatchModel;
    }
}
