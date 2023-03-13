<?php

namespace App\Controller\ApiCommon;

use App\Entity\Address;
use App\Entity\User;
use App\Entity\UserBank;
use App\Entity\Wallet\InternalTransfer;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\WalletBank;
use App\Entity\Wallet\Withdrawal;
use App\Exception\AppException;
use App\Manager\AddressManager;
use App\Manager\AddressValidator;
use App\Manager\ListFilter\DepositListFilter;
use App\Manager\ListFilter\InternalTransferListFilter;
use App\Manager\ListFilter\WithdrawalListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\WalletAnalyzer;
use App\Manager\WalletManager;
use App\Model\Analysis\WalletAnalysis;
use App\Repository\UserBankRepository;
use App\Repository\UserRepository;
use App\Repository\Wallet\DepositRepository;
use App\Repository\Wallet\InternalTransferRepository;
use App\Repository\Wallet\WalletBankRepository;
use App\Repository\Wallet\WithdrawalRepository;
use App\Repository\WalletRepository;
use App\Security\SystemTagAccessResolver;
use App\Security\TagAccessResolver;
use App\Service\AddressApp\AddressAppManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletController extends AbstractController
{
    /** @var SystemTagAccessResolver */
    private $systemTagAccessResolver;

    /**
     * AuthController constructor.
     * @param SystemTagAccessResolver $systemTagAccessResolver
     */
    public function __construct(SystemTagAccessResolver $systemTagAccessResolver)
    {
        $this->systemTagAccessResolver = $systemTagAccessResolver;
    }

    /**
     * @param string $currencyShortName
     * @param WalletManager $walletManager
     * @param AddressManager $addressManager
     * @return JsonResponse
     * @throws AppException
     */
    public function putWalletGenerateAddress(string $currencyShortName, WalletManager $walletManager, AddressManager $addressManager) : JsonResponse
    {
        $this->systemTagAccessResolver->authDeposit();

        /** @var User $user */
        $user = $this->getUser();

        /** @var Wallet $wallet */
        $wallet = $walletManager->loadByUserAndCurrency($user, $currencyShortName);

        // resolve access
        $this->denyAccessUnlessGranted('edit', $wallet);

        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');

        try{
            /** @var Address $address */
            $address = $addressManager->generate($wallet);
        }catch (\Exception $exception){
            throw new AppException('Address cannot be generated');
        }

        return new JsonResponse(['message' => $address->serialize()], Response::HTTP_CREATED);
    }

    /**
     * @param string $currencyShortName
     * @param Request $request
     * @param WalletManager $walletManager
     * @param TagAccessResolver $tagAccessResolver
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws AppException
     */
    public function postWalletInternalTransfer(string $currencyShortName, Request $request, WalletManager $walletManager, TagAccessResolver $tagAccessResolver, UserRepository $userRepository)
    {
        $this->systemTagAccessResolver->authInternalTransfer();

        /** @var Wallet $wallet */
        $wallet = $walletManager->loadByUserAndCurrency($this->getUser(), $currencyShortName);

        $this->denyAccessUnlessGranted('edit', $wallet);                    // resolve access
        $tagAccessResolver->authInternalTransfer($wallet->getUser(), $wallet);       // resolve user tag access

        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to request the withdrawal');

        if(!$request->request->has('amount')) throw new AppException('error.required.amount');
        $amount = (string) $request->get('amount', '');
        if(!is_numeric($amount)) throw new AppException('error.invalid.amount');

        if($wallet->isFiatWallet()){
            throw new AppException('Internal transfer not allowed for selected currency');
        }else{
            $toUser = null;
            if($request->request->has('email')){
                $email = (string) $request->get('email', '');

                /** @var User $toUser */
                $toUser = $userRepository->findOneBy(['email' => $email]);
            }elseif($request->request->has('userId')){
                $userId = (int) $request->get('userId', '');

                /** @var User $toUser */
                $toUser = $userRepository->find($userId);
            }
            if(!($toUser instanceof User)) throw new AppException('Invalid transfer email or ID');
            if(!$toUser->isTier1Approved()) throw new AppException('Invalid transfer email');

            /** @var Wallet $toWallet */
            $toWallet = null;

            $userWallets = $toUser->getWallets();
            /** @var Wallet $userWallet */
            foreach($userWallets as $userWallet){
                if($wallet->getCurrency()->getId() === $userWallet->getCurrency()->getId()){
                    $toWallet = $userWallet;
                }
            }

            if(!($toWallet instanceof Wallet)) throw new AppException('Invalid transfer email');

            /** @var InternalTransfer $internalTransfer */
            $internalTransfer = $walletManager->requestInternalTransfer($wallet, $amount, $toWallet);
        }

        return new JsonResponse(['internalTransfer' => $internalTransfer->serializeBasic()], Response::HTTP_CREATED);
    }

    /**
     * @param string $currencyShortName
     * @param Request $request
     * @param WalletManager $walletManager
     * @param TagAccessResolver $tagAccessResolver
     * @param UserBankRepository $userBankRepository
     * @param WalletBankRepository $walletBankRepository
     * @param AddressValidator $addressValidator
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postWalletWithdrawal(
        string $currencyShortName, Request $request,
        WalletManager $walletManager, TagAccessResolver $tagAccessResolver, UserBankRepository $userBankRepository, WalletBankRepository $walletBankRepository, AddressValidator $addressValidator
    ) : JsonResponse
    {
        $this->systemTagAccessResolver->authWithdrawal();

        /** @var Wallet $wallet */
        $wallet = $walletManager->loadByUserAndCurrency($this->getUser(), $currencyShortName);

        $this->denyAccessUnlessGranted('edit', $wallet);             // resolve access
        $tagAccessResolver->authWithdrawal($wallet->getUser(), $wallet);      // resolve user tag access

        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed to request the withdrawal');
        if(!$user->isTier3Approved()) throw new AppException('User is not allowed to request the withdrawal');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to request the withdrawal');

        if(!$request->request->has('amount')) throw new AppException('error.required.amount');
        $amount = (string) $request->get('amount', '');
        if(!is_numeric($amount)) throw new AppException('error.invalid.amount');

        if($wallet->isFiatWallet()){
            if(!($request->request->has('userBankId') || $request->request->has('walletBankId'))){
                throw new AppException('Bank account not found');
            }

            $userBank = null;
            $walletBank = null;

            if($request->request->has('userBankId')){
                $userBankId = $request->get('userBankId');

                /** @var UserBank $userBank */
                $userBank = $userBankRepository->find($userBankId);
                if(!($userBank instanceof UserBank)) throw new AppException('Bank account not found');
                if(!$userBank->isUserAllowed($user)) throw new AppException('Bank account not found');
            }

            if($request->request->has('walletBankId')){
                $walletBankId = $request->get('walletBankId');

                /** @var WalletBank $walletBank */
                $walletBank = $walletBankRepository->find($walletBankId);
                if(!($walletBank instanceof WalletBank)) throw new AppException('Bank account not found');
                if(!$walletBank->isWalletAllowed($wallet)) throw new AppException('Bank account not found');
            }

            /** @var Withdrawal $withdrawal */
            $withdrawal = $walletManager->requestFiatWithdrawal($wallet, $amount, $userBank, $walletBank);
        }else{
            if(!$request->request->has('address')) throw new AppException('error.required.address');
            $address = (string) $request->get('address', '');

//            if(!$addressValidator->isValid($address, $wallet)) throw new AppException('Invalid withdrawal address');

            /** @var Withdrawal $withdrawal */
            $withdrawal = $walletManager->requestCryptoWithdrawal($wallet, $amount, $address);
        }

        return new JsonResponse(['withdrawal' => $withdrawal->serializeBasic()], Response::HTTP_CREATED);
    }

    /**
     * @param int $walletId
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param WithdrawalRepository $withdrawalRepository
     * @param ListManager $listManager
     * @return JsonResponse
     * @throws AppException
     */
    public function getWalletWithdrawals(int $walletId, Request $request, WalletRepository $walletRepository, WithdrawalRepository $withdrawalRepository, ListManager $listManager) : JsonResponse
    {
        /** @var Wallet $wallet */
        $wallet = $walletRepository->find($walletId);
        if(!($wallet instanceof Wallet)) throw new AppException('Wallet not found');
        if($wallet->getUser()->getId() !== $this->getUser()->getId()) throw new AppException('Wallet not found');

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new WithdrawalListFilter($request, $wallet), $withdrawalRepository)
            ->load();

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param int $walletId
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param InternalTransferRepository $internalTransferRepository
     * @param ListManager $listManager
     * @return JsonResponse
     * @throws AppException
     */
    public function getWalletInternalTransfers(int $walletId, Request $request, WalletRepository $walletRepository, InternalTransferRepository $internalTransferRepository, ListManager $listManager) : JsonResponse
    {
        /** @var Wallet $wallet */
        $wallet = $walletRepository->find($walletId);
        if(!($wallet instanceof Wallet)) throw new AppException('Wallet not found');
        if($wallet->getUser()->getId() !== $this->getUser()->getId()) throw new AppException('Wallet not found');

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new InternalTransferListFilter($request, $wallet), $internalTransferRepository)
            ->load();

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param int $walletId
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param DepositRepository $depositRepository
     * @param ListManager $listManager
     * @return JsonResponse
     * @throws AppException
     */
    public function getWalletDeposits(int $walletId, Request $request, WalletRepository $walletRepository, DepositRepository $depositRepository, ListManager $listManager) : JsonResponse
    {
        /** @var Wallet $wallet */
        $wallet = $walletRepository->find($walletId);
        if(!($wallet instanceof Wallet)) throw new AppException('Wallet not found');
        if($wallet->getUser()->getId() !== $this->getUser()->getId()) throw new AppException('Wallet not found');

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new DepositListFilter($request, $this->getUser(), $wallet), $depositRepository)
            ->load();

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param int $walletId
     * @param WalletManager $walletManager
     * @param WalletAnalyzer $walletAnalyzer
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getWalletAnalyze(int $walletId, WalletManager $walletManager, WalletAnalyzer $walletAnalyzer) : JsonResponse
    {
        /** @var Wallet $wallet */
        $wallet = $walletManager->load($walletId);

        /** @var WalletAnalysis $walletAnalysis */
        $walletAnalysis = $walletAnalyzer->analyzeTransfers($wallet);

        return new JsonResponse(['walletAnalysis' => $walletAnalysis], Response::HTTP_OK);
    }
}
