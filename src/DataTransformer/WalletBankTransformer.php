<?php

namespace App\DataTransformer;

use App\Entity\Wallet\WalletBank;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Repository\Wallet\WalletBankRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WalletBankTransformer extends AppTransformer
{
    /** @var WalletBankRepository */
    private $walletBankRepository;

    /**
     * WalletBankTransformer constructor.
     * @param WalletBankRepository $walletBankRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(WalletBankRepository $walletBankRepository, ValidatorInterface $validator)
    {
        $this->walletBankRepository = $walletBankRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param Wallet $wallet
     * @param Request $request
     * @return WalletBank
     * @throws AppException
     * @throws \Exception
     */
    public function transform(Wallet $wallet, Request $request) : WalletBank
    {
        $iban = (string) $request->get('iban', '');
        if(empty($iban)) throw new AppException('Iban is required');

        $swift = (string) $request->get('swift', '');
        if(empty($swift)) throw new AppException('Swift is required');

        /** @var WalletBank $walletBank */
        $walletBank = $this->walletBankRepository->findOneBy(['iban' => $iban, 'swift' => $swift]);
        if($walletBank instanceof WalletBank) throw new AppException('Bank account already exists');

        /** @var WalletBank $walletBank */
        $walletBank = new WalletBank($wallet, $iban, $swift);

        return $walletBank;
    }
}