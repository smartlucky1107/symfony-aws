<?php

namespace App\Controller\ApiAdmin;

use App\DataTransformer\UserBankTransformer;
use App\DataTransformer\UserTransformer;
use App\Document\Login;
use App\Document\NotificationInterface;
use App\Entity\Configuration\ApiKey;
use App\Entity\Configuration\VoterRole;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Entity\UserBank;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Withdrawal;
use App\Exception\AppException;
use App\Manager\ApiPrivate\ApiKeyManager;
use App\Manager\ListFilter\UserListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\LoginManager;
use App\Manager\NotificationManager;
use App\Manager\ReferralManager;
use App\Manager\Aml\iAmlManager;
use App\Manager\UserBankManager;
use App\Manager\UserManager;

use App\Manager\WalletManager;
use App\Model\PriceInterface;
use App\Repository\Configuration\ApiKeyRepository;
use App\Repository\Configuration\VoterRoleRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\UserBankRepository;
use App\Repository\UserRepository;
use App\Repository\Wallet\DepositRepository;
use App\Repository\Wallet\WithdrawalRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\DataUriNormalizer;

class UserController extends FOSRestController
{
##
#### is_granted('ROLE_ADMIN')
##
    /**
     * @Rest\Get("/users/today-statistics", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param UserRepository $userRepository
     * @return View
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTodayStatistics(UserRepository $userRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        $registered = (int) $userRepository->findRegisteredTodayCount();

        return $this->view(['registered' => $registered], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/users/{userId}/banks", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param int $userId
     * @param UserBankTransformer $userBankTransformer
     * @param UserBankManager $userBankManager
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postUserBank(Request $request, int $userId, UserBankTransformer $userBankTransformer, UserBankManager $userBankManager, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        /** @var UserBank $userBank */
        $userBank = $userBankTransformer->transform($user, $request);
        $userBankTransformer->validate($userBank);

        $userBank = $userBankManager->save($userBank);

        return $this->view(['userBank' => $userBank->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Rest\Get("/users/{userId}/banks", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param UserBankRepository $userBankRepository
     * @return View
     * @throws AppException
     */
    public function getUserBanks(int $userId, UserManager $userManager, UserBankRepository $userBankRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $result = [];

        $userBanks = $userBankRepository->findBy(['user' => $user->getId()]);
        if($userBanks){
            /** @var UserBank $userBank */
            foreach($userBanks as $userBank){
                $result[] = $userBank->serialize();
            }
        }

        return $this->view(['banks' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/gauth-disable", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserGAuthDisable(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $user = $userManager->disableGAuth($user);

        return $this->view(['user' => $user->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/email-notification/{notificationType}/resend", requirements={"userId"="\d+", "notificationType"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param int $notificationType
     * @param UserManager $userManager
     * @param NotificationManager $notificationManager
     * @return View
     * @throws AppException
     */
    public function putUserResendEmailNotification(int $userId, int $notificationType, UserManager $userManager, NotificationManager $notificationManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        if(!in_array($notificationType, NotificationInterface::ALLOWED_TYPES_USER)){
            throw new AppException('Notification type not allowed for resend');
        }

        $notificationManager->sendEmailNotification($user, $notificationType, ['user' => $user, 'id' => $user->getId()]);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/resend-confirmation", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     */
    public function putUserResendConfirmation(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->resendConfirmation($user);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/toggle-trading-enabled", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserToggleTradingEnabled(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->toggleTradingEnabled($user);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/toggle-email-confirmed", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserToggleEmailConfirmed(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->toggleEmailConfirmed($user);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/tag/{tag}/unassign", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param string $tag
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserTagUnassign(int $userId, string $tag, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->unassignTag($user, $tag);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/tag/{tag}/assign", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param string $tag
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserTagAssign(int $userId, string $tag, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->assignTag($user, $tag);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/api-keys", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param ApiKeyRepository $apiKeyRepository
     * @return View
     * @throws AppException
     */
    public function getUserApiKeys(int $userId, UserManager $userManager, ApiKeyRepository $apiKeyRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $apiKeys = $apiKeyRepository->findBy(['user' => $user->getId()]);

        $result = [];
        if($apiKeys){
            /** @var ApiKey $apiKey */
            foreach($apiKeys as $apiKey){
                $result[] = $apiKey->serializeBasic();
            }
        }

        return $this->view(['apiKeys' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/api-keys/{key}/deactivate", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param string $key
     * @param UserManager $userManager
     * @param ApiKeyManager $apiKeyManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserApiKeyDeactivate(int $userId, string $key, UserManager $userManager, ApiKeyManager $apiKeyManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        /** @var ApiKey $apiKey */
        $apiKey = $apiKeyManager->loadByKeyUser($key, $user);
        $apiKeyManager->deactivate($apiKey);

        return $this->view(['status' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/voter-roles", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     */
    public function getUserVoterRoles(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $result = [];

        $voterRoles = $user->getVoterRoles();
        /** @var VoterRole $voterRole */
        foreach($voterRoles as $voterRole){
            $result[] = $voterRole->serialize();
        }

        return $this->view(['voterRoles' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/login-history", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param LoginManager $loginManager
     * @return View
     * @throws AppException
     */
    public function getUserLoginHistory(int $userId, UserManager $userManager, LoginManager $loginManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $result = [];

        $loginHistory = $loginManager->findForUser($user->getId());
        /** @var Login $loginHistoryItem */
        foreach($loginHistory as $loginHistoryItem){
            $result[] = $loginHistoryItem->serialize();
        }

        return $this->view(['loginHistory' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/pending-orders", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param OrderRepository $orderRepository
     * @return View
     * @throws AppException
     */
    public function getUserPendingOrders(int $userId, UserManager $userManager, OrderRepository $orderRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $result = [];

        $pendingOrders = $orderRepository->findUserPendingOrders($user->getId());
        if($pendingOrders){
            /** @var Order $order */
            foreach($pendingOrders as $order){
                $result[] = $order->serialize();
            }
        }

        return $this->view(['pendingOrders' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/deposits", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param DepositRepository $depositRepository
     * @return View
     * @throws AppException
     */
    public function getUserDeposits(int $userId, UserManager $userManager, DepositRepository $depositRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $result = [];

        $deposits = $depositRepository->findForUser($user);
        if($deposits){
            /** @var Deposit $deposit */
            foreach($deposits as $deposit){
                $result[] = $deposit->serialize();
            }
        }

        return $this->view(['deposits' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/withdrawals", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param WithdrawalRepository $withdrawalRepository
     * @return View
     * @throws AppException
     */
    public function getUserWithdrawals(int $userId, UserManager $userManager, WithdrawalRepository $withdrawalRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        $result = [];

        $withdrawals = $withdrawalRepository->findForUser($user);
        if($withdrawals){
            /** @var Withdrawal $withdrawal */
            foreach($withdrawals as $withdrawal){
                $result[] = $withdrawal->serialize();
            }
        }

        return $this->view(['withdrawals' => $result], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     */
    public function getSingleUser(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        $user = $userManager->load($userId);

        return $this->view(['user' => $user->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ListManager $listManager
     * @return View
     * @throws AppException
     */
    public function getUsers(Request $request, UserRepository $userRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_USER);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new UserListFilter($request), $userRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/update-data", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param int $userId
     * @param UserTransformer $userTransformer
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserUpdateData(Request $request, int $userId, UserTransformer $userTransformer, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VERIFY, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        /** @var User $user */
        $user = $userTransformer->transform($request, $user, true);
        //$userTransformer->validateForm($user); TODO przemnyślec to i zmienić

        $user = $userManager->update($user);
        $user = $userManager->approveTier1($user);

        return $this->view(['user' => $user->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/verification-status/{status}", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param int $status
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putVerificationStatus(int $userId, int $status, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VERIFY, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        /** @var User $user */
        $user = $userManager->setVerificationStatus($user, $status);

        return $this->view(['user' => $user->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Patch("/users/{userId}/remove", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchRemoveUser(int $userId, UserManager $userManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->removeUser($user);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/users/{userId}/voter-roles/grant/{voterRoleId}", requirements={"userId"="\d+", "voterRoleId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param int $voterRoleId
     * @param UserManager $userManager
     * @param VoterRoleRepository $voterRoleRepository
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserVoterRolesGrant(int $userId, int $voterRoleId, UserManager $userManager, VoterRoleRepository $voterRoleRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var VoterRole $voterRole */
        $voterRole = $voterRoleRepository->find($voterRoleId);
        if(!($voterRole instanceof VoterRole)) throw new AppException('error.voter_role.not_found');

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->voterRoleGrant($user, $voterRole);

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/users/{userId}/voter-roles/deny/{voterRoleId}", requirements={"userId"="\d+", "voterRoleId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param int $voterRoleId
     * @param UserManager $userManager
     * @param VoterRoleRepository $voterRoleRepository
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putUserVoterRolesDeny(int $userId, int $voterRoleId, UserManager $userManager, VoterRoleRepository $voterRoleRepository) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_USER);

        /** @var VoterRole $voterRole */
        $voterRole = $voterRoleRepository->find($voterRoleId);
        if(!($voterRole instanceof VoterRole)) throw new AppException('error.voter_role.not_found');

        /** @var User $user */
        $user = $userManager->load($userId);
        $userManager->voterRoleDeny($user, $voterRole);

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/users/{userId}/pep-info", requirements={"userId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $userId
     * @param UserManager $userManager
     * @param iAmlManager $iAmlManager
     * @return View
     * @throws AppException
     */
    public function getUserPepInfo(int $userId, UserManager $userManager, iAmlManager $iAmlManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_VIEW, VoterRoleInterface::MODULE_USER);

        /** @var User $user */
        $user = $userManager->load($userId);

        if(is_null($user->getIAmlPepInfo())){
            try{
                $pepInfo = $iAmlManager->getPepInfo($user->getFullName());

                $user->setIAmlPepInfo($pepInfo);
                $user = $userManager->update($user);
            }catch (\Exception $exception){}
        }

        return $this->view($user->getIAmlPepInfo(), JsonResponse::HTTP_OK);
    }
}
