<?php

namespace App\Manager;

use App\Document\FeeTransfer;
use App\Entity\OrderBook\Trade;
use App\Entity\Wallet\Withdrawal;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use App\Exception\AppException;

class FeeTransferManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * FeeTransferManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function clearManager() : void
    {

    }

    /**
     * @param Trade $trade
     * @param int $feeWalletId
     * @param string $amount
     * @return FeeTransfer
     * @throws AppException
     */
    public function createTradeFeeTransfer(Trade $trade, int $feeWalletId, string $amount) : FeeTransfer
    {
        $feeTransfer = new FeeTransfer(FeeTransfer::TYPE_TRADE_FEE, $feeWalletId, $amount);
        $feeTransfer->setTradeId($trade->getId());

        $feeTransfer = $this->save($feeTransfer);

        $this->clearManager();

        return $feeTransfer;
    }

    /**
     * @param Withdrawal $withdrawal
     * @param int $feeWalletId
     * @param string $amount
     * @return FeeTransfer
     * @throws AppException
     */
    public function createWithdrawalFeeTransfer(Withdrawal $withdrawal, int $feeWalletId, string $amount) : FeeTransfer
    {
        $feeTransfer = new FeeTransfer(FeeTransfer::TYPE_WITHDRAWAL_FEE, $feeWalletId, $amount);
        $feeTransfer->setWithdrawalId($withdrawal->getId());

        $feeTransfer = $this->save($feeTransfer);

        $this->clearManager();

        return $feeTransfer;
    }

    /**
     * @param FeeTransfer $feeTransfer
     * @return FeeTransfer
     */
    public function save(FeeTransfer $feeTransfer){

        $this->dm->persist($feeTransfer);
        $this->dm->flush();

        return $feeTransfer;
    }
}
