<?php

namespace App\Controller\ApiAdmin;

use App\DataTransformer\WalletBankTransformer;
use App\Entity\Address;
use App\Entity\OrderBook\Order;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\WalletBank;
use App\Exception\AppException;
use App\Manager\AddressManager;
use App\Manager\ListFilter\WalletListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\WalletBankManager;
use App\Manager\WalletManager;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WalletController extends FOSRestController
{
    /**
     * @Rest\Post("/wallets/{walletId}/address", requirements={"walletId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $walletId
     * @param WalletRepository $walletRepository
     * @param AddressManager $addressManager
     * @return View
     * @throws AppException
     */
    public function postUserWalletAddress(int $walletId, WalletRepository $walletRepository, AddressManager $addressManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WALLET);

        /** @var Wallet $wallet */
        $wallet = $walletRepository->findOrException($walletId);

        try{
            /** @var Address $address */
            $address = $addressManager->generate($wallet);
        }catch (\Exception $exception){
            throw new AppException('Address cannot be generated');
        }

        return $this->view(['message' => $address->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Rest\Post("/wallets/{walletId}/banks", requirements={"walletId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param int $walletId
     * @param WalletBankTransformer $walletBankTransformer
     * @param WalletBankManager $walletBankManager
     * @param WalletManager $walletManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postWalletBank(Request $request, int $walletId, WalletBankTransformer $walletBankTransformer, WalletBankManager $walletBankManager, WalletManager $walletManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WALLET);

        /** @var Wallet $wallet */
        $wallet = $walletManager->load($walletId);

        /** @var WalletBank $walletBank */
        $walletBank = $walletBankTransformer->transform($wallet, $request);
        $walletBankTransformer->validate($walletBank);

        $walletBank = $walletBankManager->save($walletBank);

        return $this->view(['walletBank' => $walletBank->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Rest\Get("/wallets/{walletId}/banks", requirements={"walletId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $walletId
     * @param WalletManager $walletManager
     * @param WalletBankRepository $walletBankRepository
     * @return View
     * @throws AppException
     */
    public function getWalletBanks(int $walletId, WalletManager $walletManager, WalletBankRepository $walletBankRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WALLET);

        /** @var Wallet $wallet */
        $wallet = $walletManager->load($walletId);

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

    /**
     * @Rest\Get("/wallets/{walletId}/analyze", requirements={"walletId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $walletId
     * @return View
     */
    public function getWalletAnalyze(int $walletId){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_ANALYZE, VoterRoleInterface::MODULE_WALLET);

        $response = $this->forward('App\Controller\ApiCommon\WalletController:getWalletAnalyze', [
            'walletId'  => $walletId,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Put("/wallets/{walletId}/release-blocked/{amount}", requirements={"walletId"="\d+", "amount"="[0-9]*\.?[0-9]+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $walletId
     * @param $amount
     * @param WalletManager $walletManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putWalletReleaseBlocked(int $walletId, $amount, WalletManager $walletManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WALLET);

        /** @var Wallet $wallet */
        $wallet = $walletManager->getWalletRepository()->find($walletId);
        if(!($wallet instanceof Wallet)) throw new AppException('error.wallet.not_found');

        $walletManager->releaseAmount($wallet, $amount);

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/wallets/{walletId}/internal-transfer/{toWalletId}/{amount}", requirements={"walletId"="\d+", "toWalletId"="\d+", "amount"="[0-9]*\.?[0-9]+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $walletId
     * @param int $toWalletId
     * @param $amount
     * @param WalletManager $walletManager
     * @return View
     * @throws AppException
     */
    public function putWalletInternalTransfer(int $walletId, int $toWalletId, $amount, WalletManager $walletManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_WALLET);

        /** @var Wallet $wallet */
        $wallet = $walletManager->getWalletRepository()->find($walletId);
        if(!($wallet instanceof Wallet)) throw new AppException('error.wallet.not_found');

        /** @var Wallet $toWallet */
        $toWallet = $walletManager->getWalletRepository()->find($toWalletId);
        if(!($toWallet instanceof Wallet)) throw new AppException('error.wallet.not_found');

        $walletManager->internalTransfer($wallet, $toWallet, $amount);

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/wallets/{walletId}", requirements={"walletId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param int $walletId
     * @param WalletManager $walletManager
     * @return View
     * @throws AppException
     */
    public function getWallet(int $walletId, WalletManager $walletManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_WALLET);

        $wallet = $walletManager->load($walletId);

        return $this->view(['wallet' => $wallet->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/wallets", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getWallets(Request $request, WalletRepository $walletRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_WALLET);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new WalletListFilter($request), $walletRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/wallets/{walletId}/pending-orders", requirements={"walletId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $walletId
     * @param WalletRepository $walletRepository
     * @param OrderRepository $orderRepository
     * @return View
     * @throws AppException
     */
    public function getWalletPendingOrders(int $walletId, WalletRepository $walletRepository, OrderRepository $orderRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_WALLET);

        /** @var Wallet $wallet */
        $wallet = $walletRepository->findOrException($walletId);

        $result = [];

        $pendingOrders = $orderRepository->findWalletPendingOrders($wallet);
        if($pendingOrders){
            /** @var Order $order */
            foreach($pendingOrders as $order){
                $result[] = $order->serialize();
            }
        }

        return $this->view(['pendingOrders' => $result], JsonResponse::HTTP_OK);
    }
}
