<?php

namespace App\Controller\ApiAdmin;

use App\DataTransformer\DepositTransformer;
use App\Entity\User;
use App\Entity\Wallet\Deposit;
use App\Manager\DepositManager;
use App\Manager\ListFilter\DepositListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\Wallet\DepositRepository;
use App\Security\SystemTagAccessResolver;
use App\Security\TagAccessResolver;
use App\Security\VoterRoleInterface;
use App\Service\AddressApp\AddressAppManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Exception\AppException;

class DepositController extends FOSRestController
{
    /**
     * @Rest\Get("/deposits/{depositId}", requirements={"depositId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param int $depositId
     * @param DepositManager $depositManager
     * @return View
     * @throws AppException
     */
    public function getDeposit(int $depositId, DepositManager $depositManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_DEPOSIT);

        $deposit = $depositManager->load($depositId);

        return $this->view(['deposit' => $deposit->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/deposits", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param DepositRepository $depositRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getDeposits(Request $request, DepositRepository $depositRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_DEPOSIT);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new DepositListFilter($request, $this->getUser()), $depositRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/deposits", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param DepositTransformer $depositTransformer
     * @param DepositManager $depositManager
     * @param SystemTagAccessResolver $systemTagAccessResolver
     * @param TagAccessResolver $tagAccessResolver
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postDeposit(Request $request, DepositTransformer $depositTransformer, DepositManager $depositManager, SystemTagAccessResolver $systemTagAccessResolver, TagAccessResolver $tagAccessResolver) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_CREATE, VoterRoleInterface::MODULE_DEPOSIT);
        $systemTagAccessResolver->authDeposit();

        /** @var Deposit $deposit */
        $deposit = $depositTransformer->transform($this->getUser(), $request);
        $depositTransformer->validate($deposit);

        // resolve user tag access
        $tagAccessResolver->authDeposit($deposit->getWallet()->getUser(), $deposit);

        /** @var Deposit $deposit */
        $deposit = $depositManager->requestDeposit($deposit);

        /** @var User $user */
        $user = $this->getUser();
        if(DepositManager::isForceApproveAllowed($user->getId())){
            $deposit = $depositManager->approve($deposit, $user);
        }

        return $this->view(['deposit' => $deposit->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/deposits/{depositId}/approve", requirements={"depositId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $depositId
     * @param DepositManager $depositManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putDepositApprove(int $depositId, DepositManager $depositManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_DEPOSIT);

        /** @var Deposit $deposit */
        $deposit = $depositManager->load($depositId);
        $depositManager->approve($deposit, $this->getUser());

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/deposits/{depositId}/decline", requirements={"depositId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $depositId
     * @param DepositManager $depositManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putDepositDecline(int $depositId, DepositManager $depositManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_DEPOSIT);

        /** @var Deposit $deposit */
        $deposit = $depositManager->load($depositId);
        $depositManager->decline($deposit, $this->getUser());

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/deposits/{depositId}/revert", requirements={"depositId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $depositId
     * @param DepositManager $depositManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putDepositRevert(int $depositId, DepositManager $depositManager){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_DEPOSIT);

        /** @var Deposit $deposit */
        $deposit = $depositManager->load($depositId);
        $depositManager->revert($deposit, $this->getUser());

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/deposits/{depositId}/blockchain-tx", requirements={"depositId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")

     * @param int $depositId
     * @param DepositManager $depositManager
     * @param AddressAppManager $addressAppManager
     * @return View
     * @throws AppException
     * @throws \App\Exception\ApiConnectionException
     */
    public function getDepositBlockchainTx(int $depositId, DepositManager $depositManager, AddressAppManager $addressAppManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_DEPOSIT);

        $deposit = $depositManager->load($depositId);

        $ethereumBlockchainTx = null;
        $bitcoinBlockchainTx = null;

        if($deposit->getBlockchainTransactionHash()) {
            if($deposit->getWallet()->isEthWallet() || $deposit->getWallet()->isErc20Wallet()){
                $response = (array) $addressAppManager->getEthereumTxBlockchainTx($deposit->getBlockchainTransactionHash());
                if(isset($response['blockchainTx'])){
                    $ethereumBlockchainTx = (array) $response['blockchainTx'];
                }
            }elseif($deposit->getWallet()->isBtcWallet()){
                $response = (array) $addressAppManager->getBitcoinTxBlockchainTx($deposit->getBlockchainTransactionHash());
                if(isset($response['blockchainTx'])){
                    $bitcoinBlockchainTx = (array) $response['blockchainTx'];
                }
            }elseif($deposit->getWallet()->isBchWallet()){
                $response = (array) $addressAppManager->getBitcoinCashTxBlockchainTx($deposit->getBlockchainTransactionHash());
                if(isset($response['blockchainTx'])){
                    $bitcoinBlockchainTx = (array) $response['blockchainTx'];
                }
            }elseif($deposit->getWallet()->isBsvWallet()){
                $response = (array) $addressAppManager->getBitcoinSvTxBlockchainTx($deposit->getBlockchainTransactionHash());
                if(isset($response['blockchainTx'])){
                    $bitcoinBlockchainTx = (array) $response['blockchainTx'];
                }
            }
        }

        return $this->view(['ethereumBlockchainTx' => $ethereumBlockchainTx, 'bitcoinBlockchainTx' => $bitcoinBlockchainTx], JsonResponse::HTTP_OK);
    }
}
