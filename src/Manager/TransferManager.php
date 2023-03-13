<?php

namespace App\Manager;

use App\Document\Transfer;
use App\Entity\CheckoutOrder;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\InternalTransfer;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\Withdrawal;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use App\Exception\AppException;

class TransferManager
{
    /** @var DocumentManager */
    private $dm;

    /** @var Trade */
    private $trade;

    /** @var Order */
    private $order;

    /** @var Deposit */
    private $deposit;

    /** @var Withdrawal */
    private $withdrawal;

    /** @var InternalTransfer */
    private $internalTransfer;

    /** @var CheckoutOrder */
    private $checkoutOrder;

    /**
     * TransferManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    private function clearManager() : void
    {
        $this->trade = null;
        $this->order = null;
        $this->deposit = null;
        $this->withdrawal = null;
        $this->internalTransfer = null;
        $this->checkoutOrder = null;
    }

    /**
     * @param Trade $trade
     */
    public function setTrade(Trade $trade): void
    {
        $this->trade = $trade;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    /**
     * @param Deposit $deposit
     */
    public function setDeposit(Deposit $deposit): void
    {
        $this->deposit = $deposit;
    }

    /**
     * @param Withdrawal $withdrawal
     */
    public function setWithdrawal(Withdrawal $withdrawal): void
    {
        $this->withdrawal = $withdrawal;
    }

    /**
     * @param InternalTransfer $internalTransfer
     */
    public function setInternalTransfer(InternalTransfer $internalTransfer): void
    {
        $this->internalTransfer = $internalTransfer;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     */
    public function setCheckoutOrder(CheckoutOrder $checkoutOrder): void
    {
        $this->checkoutOrder = $checkoutOrder;
    }

    /**
     * Load transfers by $tradeId
     *
     * @param int $tradeId
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadByTradeId(int $tradeId){
        $qb = $this->dm->createQueryBuilder(Transfer::class);
        $qb->field('tradeId')->equals($tradeId);
        $qb->sort('createdAtTime');

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param int $walletId
     * @param \DateTime $from
     * @param \DateTime $to
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findIncomesByWalletId(int $walletId, \DateTime $from, \DateTime $to){
        $qb = $this->dm->createQueryBuilder(Transfer::class);
        $qb->field('walletId')->equals($walletId);
        $qb->addAnd($qb->expr()->field('type')->in([
            Transfer::TYPE_DEPOSIT,
            Transfer::TYPE_TRANSFER_TO,
            Transfer::TYPE_FEE_TRANSFER_TO,
            Transfer::TYPE_INTERNAL_TRANSFER_TO
        ]));
        $qb->addAnd($qb->expr()->field('createdAtTime')->gt($from->getTimestamp()));
        $qb->addAnd($qb->expr()->field('createdAtTime')->lt($to->getTimestamp()));

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Load transfers by $walletId
     *
     * @param int $walletId
     * @param int|null $limit
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadByWalletId(int $walletId, int $limit = null){
        $qb = $this->dm->createQueryBuilder(Transfer::class);
        $qb->field('walletId')->equals($walletId);
        $qb->sort('createdAtTime', -1);
        if(!is_null($limit)){
            $qb->limit($limit);
        }

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * Load transfers by $walletId
     *
     * @param array $walletIds
     * @param int $type
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadByWalletIdsAndType(array $walletIds, int $type){
        $qb = $this->dm->createQueryBuilder(Transfer::class);
        $qb->field('walletId')->in($walletIds);
        $qb->addAnd($qb->expr()->field('type')->equals($type));
        $qb->sort('createdAtTime', -1);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * Load transfers by $orderId
     *
     * @param int $orderId
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadByOrderId(int $orderId){
        $qb = $this->dm->createQueryBuilder(Transfer::class);
        $qb->addOr($qb->expr()->field('bidOrderId')->equals($orderId));
        $qb->addOr($qb->expr()->field('offerOrderId')->equals($orderId));
        $qb->sort('createdAtTime');

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * @param int $transferType
     * @param Wallet $wallet
     * @param $amount
     * @return Transfer
     * @throws AppException
     */
    public function create(int $transferType, Wallet $wallet, $amount){
        $transfer = new Transfer($transferType, $wallet->getId(), $amount);

        if($this->trade instanceof Trade){
            $transfer->setTradeId($this->trade->getId());
            $transfer->setOfferOrderId($this->trade->getOrderSell()->getId());
            $transfer->setBidOrderId($this->trade->getOrderBuy()->getId());
        }

        if($this->order instanceof Order){
            $transfer->setOrderId($this->order->getId());
        }

        if($this->deposit instanceof Deposit){
            $transfer->setDepositId($this->deposit->getId());
        }

        if($this->withdrawal instanceof Withdrawal){
            $transfer->setWithdrawalId($this->withdrawal->getId());
        }

        if($this->internalTransfer instanceof InternalTransfer){
            $transfer->setInternalTransferId($this->internalTransfer->getId());
        }

        if($this->checkoutOrder instanceof CheckoutOrder){
            $transfer->setCheckoutOrderId($this->checkoutOrder->getId());
        }

        $transfer = $this->save($transfer);

        $this->clearManager();

        return $transfer;
    }

    /**
     * @param Transfer $transfer
     * @return Transfer
     */
    public function save(Transfer $transfer){

        $this->dm->persist($transfer);
        $this->dm->flush();

        return $transfer;
    }
}
