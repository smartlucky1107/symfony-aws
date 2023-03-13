<?php

namespace App\DataTransformer;

use App\Entity\Wallet\Deposit;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Repository\WalletRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DepositTransformer extends AppTransformer
{
    /** @var WalletRepository */
    private $walletRepository;

    /**
     * DepositTransformer constructor.
     * @param WalletRepository $walletRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(WalletRepository $walletRepository, ValidatorInterface $validator)
    {
        $this->walletRepository = $walletRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param User $user
     * @param Request $request
     * @return Deposit
     * @throws AppException
     */
    public function transform(User $user, Request $request) : Deposit
    {
        $walletId = (int) $request->get('walletId');
        $amount = $request->get('amount');
        if(!is_numeric($amount)) throw new AppException('Amount is invalid');

        $bankTransactionDate = (string) $request->get('bankTransactionDate');
        $bankTransactionId = (string) $request->get('bankTransactionId');

        /** @var Wallet $wallet */
        $wallet = $this->walletRepository->find($walletId);
        if(!($wallet instanceof Wallet)) throw new AppException('Wallet not found');

        if(!$wallet->isDepositAmountAllowed($amount)) throw new AppException('Deposit with specified amount is not allowed');

        /** @var Deposit $deposit */
        $deposit = new Deposit($wallet, $amount, $user, $bankTransactionDate, $bankTransactionId);

        return $deposit;
    }
}
