<?php

namespace App\Controller\Api;

use App\DataTransformer\WalletBankTransformer;
use App\Entity\Address;

use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\WalletBank;
use App\Entity\Wallet\Withdrawal;
use App\Exception\AppException;
use App\Manager\AddressManager;
use App\Manager\ListFilter\WalletListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\WalletBankManager;
use App\Manager\WalletManager;
use App\Manager\WithdrawalManager;
use App\Repository\AddressRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\Wallet\WalletBankRepository;
use App\Repository\WalletRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class WalletController extends FOSRestController
{
    /**
     * @Rest\Get("/wallets/{currencyShortName}/withdrawal-fee", options={"expose"=true})
     *
     * @param Request $request
     * @param string $currencyShortName
     * @param WalletManager $walletManager
     * @param WithdrawalManager $withdrawalManager
     * @return View
     * @throws \Exception
     */
    public function getWalletWithdrawalFee(Request $request, string $currencyShortName, WalletManager $walletManager, WithdrawalManager $withdrawalManager) : View
    {
        if(!$request->query->has('amount')) throw new AppException('error.required.amount');

        /** @var Wallet $wallet */
        $wallet = $walletManager->loadByUserAndCurrency($this->getUser(), $currencyShortName);

        $amount = $request->query->get('amount');
        $fee = $wallet->toPrecision($withdrawalManager->calculateFee($wallet, $amount));

        return $this->view(['amount' => $amount, 'fee' => $fee], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/wallets/{currencyShortName}/withdrawal-request", options={"expose"=true})
     *
     * @param string $currencyShortName
     * @param Request $request
     * @return View
     */
    public function postWalletWithdrawal(string $currencyShortName, Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\WalletController:postWalletWithdrawal', [
            'currencyShortName'  => $currencyShortName,
            'request'  => $request,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
        //return $this->view(['message' => 'ok'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Rest\Post("/wallets/{currencyShortName}/internal-transfer-request", options={"expose"=true})
     *
     * @param string $currencyShortName
     * @param Request $request
     * @return View
     */
    public function postWalletInternalTransfer(string $currencyShortName, Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\WalletController:postWalletInternalTransfer', [
            'currencyShortName'  => $currencyShortName,
            'request'  => $request,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/wallets/{currencyShortName}/address", options={"expose"=true})
     *
     * @param string $currencyShortName
     * @param WalletManager $walletManager
     * @param AddressRepository $addressRepository
     * @return View
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getWalletAddress(string $currencyShortName, WalletManager $walletManager, AddressRepository $addressRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Wallet $wallet */
        $wallet = $walletManager->loadByUserAndCurrency($user, $currencyShortName);

        // resolve access
        $this->denyAccessUnlessGranted('view', $wallet);

        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');

        /** @var Address $address */
        $address = $addressRepository->findLatestByWallet($wallet);
        if(!($address instanceof Address)) {
            throw new AppException('Address not found');
        }

        return $this->view(['address' => $address->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/wallets/{currencyShortName}/address", options={"expose"=true})
     *
     * @param string $currencyShortName
     * @return View
     */
    public function postWalletAddress(string $currencyShortName) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\WalletController:putWalletGenerateAddress', [
            'currencyShortName'  => $currencyShortName,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/wallets/{walletId}/deposits", requirements={"walletId"="\d+"}, options={"expose"=true})
     *
     * @param int $walletId
     * @param Request $request
     * @return View
     */
    public function getWalletDeposits(int $walletId, Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\WalletController:getWalletDeposits', [
            'walletId'  => $walletId,
            'request'  => $request,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/wallets/{walletId}/withdrawals", requirements={"walletId"="\d+"}, options={"expose"=true})
     *
     * @param int $walletId
     * @param Request $request
     * @return View
     */
    public function getWalletWithdrawals(int $walletId, Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\WalletController:getWalletWithdrawals', [
            'walletId'  => $walletId,
            'request'  => $request,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/wallets/{walletId}/internal-transfers", requirements={"walletId"="\d+"}, options={"expose"=true})
     *
     * @param int $walletId
     * @param Request $request
     * @return View
     */
    public function getWalletInternalTransfers(int $walletId, Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\WalletController:getWalletInternalTransfers', [
            'walletId'  => $walletId,
            'request'  => $request,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/my-wallets/{walletId}/banks", requirements={"walletId"="\d+"}, options={"expose"=true})
     *
     * @param int $walletId
     * @param WalletRepository $walletRepository
     * @param WalletBankRepository $walletBankRepository
     * @return View
     * @throws \Exception
     */
    public function getMyWalletBanks(int $walletId, WalletRepository $walletRepository, WalletBankRepository $walletBankRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Wallet $wallet */
        $wallet = $walletRepository->findOrException($walletId);
        if($wallet->getUser()->getId() !== $user->getId()) throw new AppException('Wallet not found');

        $result = [];

        $walletBanks = $walletBankRepository->findBy(['wallet' => $wallet->getId()]);
        if($walletBanks){
            /** @var WalletBank $walletBank */
            foreach($walletBanks as $walletBank){
                $result[] = $walletBank->serialize();
            }
        }

        return $this->view(['banks' => $result], JsonResponse::HTTP_OK);
    }
}
