<?php

namespace App\Manager;

use App\Entity\Wallet\WalletBank;
use App\Repository\Wallet\WalletBankRepository;
use App\Repository\WalletRepository;

class WalletBankManager
{
    /** @var WalletRepository */
    private $walletRepository;

    /** @var WalletBankRepository */
    private $walletBankRepository;

    /**
     * WalletBankManager constructor.
     * @param WalletRepository $walletRepository
     * @param WalletBankRepository $walletBankRepository
     */
    public function __construct(WalletRepository $walletRepository, WalletBankRepository $walletBankRepository)
    {
        $this->walletRepository = $walletRepository;
        $this->walletBankRepository = $walletBankRepository;
    }

    /**
     * @param WalletBank $walletBank
     * @return WalletBank
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(WalletBank $walletBank) : WalletBank
    {
        return $this->walletBankRepository->save($walletBank);
    }
}