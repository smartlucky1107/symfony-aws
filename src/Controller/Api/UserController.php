<?php

namespace App\Controller\Api;

use App\DataTransformer\UserTransformer;
use App\Document\Login;
use App\Entity\Configuration\ApiKey;
use App\Entity\PaymentCard;
use App\Entity\ReferralLink;
use App\Entity\User;
use App\Entity\UserBank;
use App\Exception\AppException;
use App\Manager\AffiliateManager;
use App\Manager\ApiPrivate\ApiKeyManager;
use App\Manager\GoogleAuthenticatorManager;
use App\Manager\LoginManager;
use App\Manager\PaymentCardManager;
use App\Manager\ReferralManager;
use App\Manager\UserManager;

use App\Repository\Configuration\ApiKeyRepository;
use App\Repository\OrderBook\TradeRepository;
use App\Repository\ReferralLinkRepository;
use App\Repository\UserBankRepository;
use App\Repository\Wallet\AffiliateRewardRepository;
use App\Security\ApiRoleInterface;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\DataUriNormalizer;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class UserController extends FOSRestController
{
    /**
     * Generate api key
     *
     * @Rest\Post("/users/me/pos-key")
     *
     * @SWG\Response(
     *     response=201,
     *     description="Api key created"
     * )
     * @SWG\Tag(name="User workspace")
     *
     * @param ApiKeyManager $apiKeyManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postGeneratePOSKey(ApiKeyManager $apiKeyManager) : View
    {
        $roles = [ApiRoleInterface::ROLE_POS];

        // TODO - weryfikować czy istnieje już pos key, jak istnieje to nie pozwalać na tworzenie nowego, zeby nie bylo ich duzo

        /** @var ApiKey $apiKey */
        $apiKey = $apiKeyManager->generate($this->getUser(), $roles);

        return $this->view(['apiKey' => $apiKey->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * GET api key for POS installation
     *
     * @Rest\Get("/users/me/pos-key")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Api key "
     * )
     * @SWG\Tag(name="User workspace")
     *
     * @param ApiKeyRepository $apiKeyRepository
     * @return View
     */
    public function getPOSKey(ApiKeyRepository $apiKeyRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var ApiKey|null $posApiKey */
        $posApiKey = null;

        $apiKeys = $apiKeyRepository->findBy(['user' => $user->getId(), 'enabled' => true], ['id' => 'DESC']);
        if($apiKeys){
            /** @var ApiKey $apiKey */
            foreach($apiKeys as $apiKey){
                foreach($apiKey->getApiRoles() as $apiRole) {
                    if($apiRole === ApiRoleInterface::ROLE_POS){
                        $posApiKey = $apiKey;
                        break;
                    }
                }
                if($posApiKey instanceof ApiKey) break;
            }
        }

        return $this->view(['apiKey' => $posApiKey instanceof ApiKey ? $posApiKey->serialize() : null], JsonResponse::HTTP_OK);
    }

    /**
     * Deactivate api key
     *
     * @Rest\Patch("/users/me/pos-key/{key}/deactivate")
     *
     * @SWG\Parameter( name="key", required=true, in="path", type="string", description="Api key" )
     * @SWG\Response(
     *     response=204,
     *     description="Api key deactivated"
     * )
     * @SWG\Tag(name="User workspace")
     *
     * @param string $key
     * @param ApiKeyManager $apiKeyManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchKeyDeactivate(string $key, ApiKeyManager $apiKeyManager) : View
    {
        /** @var ApiKey $apiKey */
        $apiKey = $apiKeyManager->loadByKeyUser($key, $this->getUser());
        $apiKeyManager->deactivate($apiKey);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Get currently logged user
     *
     * @Rest\Get("/users/me", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized user object"
     * )
     * @SWG\Tag(name="User")
     *
     * @return View
     */
    public function getUserLogged() : View
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->view(['user' => $user->serialize(true)], JsonResponse::HTTP_OK);
    }

    /**
     * Modify currently logged user
     *
     * @Rest\Put("/users", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description=""
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param UserTransformer $userTransformer
     * @param UserManager $userManager
     * @return JsonResponse
     */
    public function putUser(Request $request, UserTransformer $userTransformer, UserManager $userManager) : JsonResponse
    {
        try{
            /** @var User $user */
            $user = $userTransformer->transform($request, $this->getUser());
            $userTransformer->validateForm($user);

            $user = $userManager->update($user);
            $user = $userManager->approveTier1($user);

            return new JsonResponse(['user' => $user->serializeBasic()], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(json_decode($exception->getMessage()),Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get my wallet by short name
     *
     * @Rest\Get("/users/me/wallets/{currencyShortName}", options={"expose"=true})
     *
     * @SWG\Parameter( name="currencyShortName", required=true, in="path", type="string", description="Currency short name" )
     * @SWG\Response(
     *     response=200,
     *     description="Serialized wallet object"
     * )
     * @SWG\Tag(name="User")
     *
     * @param string $currencyShortName
     * @return View
     */
    public function getMyWallet(string $currencyShortName) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyWallet', [
            'currencyShortName'  => $currencyShortName
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get my wallets
     *
     * @Rest\Get("/users/me/wallets", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of wallets"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @return View
     */
    public function getMyWallets(Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyWallets', [
            'request'  => $request
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get my orders
     *
     * @Rest\Get("/users/me/orders", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of orders"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @return View
     */
    public function getMyOrders(Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyOrders', [
            'request'  => $request
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/users/me/checkout-orders", options={"expose"=true})
     *
     * @param Request $request
     * @return View
     */
    public function getMyCheckoutOrders(Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyCheckoutOrders', [
            'request'  => $request
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * @Rest\Get("/users/me/checkout-orders/history", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description=""
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @return View
     */
    public function getMyCheckoutOrdersHistory(Request $request) : StreamedResponse
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyCheckoutOrdersHistory', [
            'request'  => $request
        ]);

        return $response;
    }

    /**
     * @Rest\Get("/users/me/pos-orders", options={"expose"=true})
     *
     * @param Request $request
     * @return View
     */
    public function getMyPOSOrders(Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyPOSOrders', [
            'request'  => $request
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get my trades
     *
     * @Rest\Get("/users/me/trades", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of trades"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @return View
     */
    public function getMyTrades(Request $request) : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyTrades', [
            'request'  => $request,
            'isForPrivateApi' => true
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get my login history
     *
     * @Rest\Get("/users/me/login-history", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of login entries"
     * )
     * @SWG\Tag(name="User")
     *
     * @return View
     */
    public function getMyLoginHistory() : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyLoginHistory');

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get my Google Authenticator data
     *
     * @Rest\Get("/users/me/gauth", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns secret and QR code url",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="secret",         type="string",  description="Secred code for Google Authenticator", example="7asd687das68"),
     *         @SWG\Property(property="qrUrl",          type="string",  description="QR code url", example="https://chart.googleapis.com/chart?chs=200x200")
     *     )
     * )
     * @SWG\Tag(name="User")
     *
     * @param GoogleAuthenticatorManager $googleAuthenticatorManager
     * @return View
     */
    public function getMyGAuth(GoogleAuthenticatorManager $googleAuthenticatorManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        $secret = $googleAuthenticatorManager->generateSecret();
        $qrUrl = $googleAuthenticatorManager->generateQrUrl($user->getEmail(), $secret);

        return $this->view([
            'secret' => $secret, 'qrUrl' => $qrUrl
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Disable Google Authenticator
     *
     * @Rest\Patch("/users/me/gauth-disable", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params for disable",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"g-auth-code"},
     *         @SWG\Property(property="g-auth-code",  type="string",  description="Code from Google Authenticator", example="1234"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Google Authenticator disabled"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param UserManager $userManager
     * @param GoogleAuthenticatorManager $googleAuthenticatorManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchMyGAuthDisable(Request $request, UserManager $userManager, GoogleAuthenticatorManager $googleAuthenticatorManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        $googleAuthenticatorManager->verifyRequest($user->getGAuthSecret(), $request);

        $userManager->disableGAuth($user);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Enable Google Authenticator
     *
     * @Rest\Patch("/users/me/gauth", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params for enable",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"g-auth-code", "secret"},
     *         @SWG\Property(property="g-auth-code",    type="string",  description="Code from Google Authenticator", example="1234"),
     *         @SWG\Property(property="secret",         type="string",  description="Secret", example="7das6d8as1"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Google Authenticator enabled"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param UserManager $userManager
     * @param GoogleAuthenticatorManager $googleAuthenticatorManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchMyGAuth(Request $request, UserManager $userManager, GoogleAuthenticatorManager $googleAuthenticatorManager) : View
    {
        $secret = (string) $request->request->get('secret', '');

        /** @var User $user */
        $user = $this->getUser();

        $googleAuthenticatorManager->verifyRequest($secret, $request);

        $userManager->enableGAuth($user, $secret);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Update my default locale
     *
     * @Rest\Patch("/users/me/locale", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"locale"},
     *         @SWG\Property(property="locale",    type="string",  description="Locale", example="pl"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Locale changed"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchSetMyLocale(Request $request, UserManager $userManager) : View
    {
        $locale = (string) $request->request->get('locale', '');

        /** @var User $user */
        $user = $this->getUser();
        $userManager->updateLocale($user, $locale);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Confirm my phone with code from SMS
     *
     * @Rest\Patch("/users/me/phone/confirm", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"code"},
     *         @SWG\Property(property="code",    type="string",  description="Code", example="123123"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Phone confirmed"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchMyPhoneConfirm(Request $request, UserManager $userManager) : View
    {
        $confirmationCode = (string) $request->request->get('code', '');

        /** @var User $user */
        $user = $this->getUser();
        $userManager->confirmPhone($user, $confirmationCode);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Send confirmation code to my phone number
     *
     * @Rest\Patch("/users/me/phone/send-code", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=204,
     *     description="Confirmation code sent via SMS"
     * )
     * @SWG\Tag(name="User")
     *
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchMyPhoneSendCode(UserManager $userManager) : View
    {
        $userManager->sendPhoneCode($this->getUser());

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Set specified phone number to my account
     *
     * @Rest\Patch("/users/me/phone", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"phone"},
     *         @SWG\Property(property="phone",    type="string",  description="Phone", example="500500500"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Phone number updated"
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param UserManager $userManager
     * @return View
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchSetMyPhone(Request $request, UserManager $userManager) : View
    {
        $phone = (string) $request->request->get('phone', '');

        /** @var User $user */
        $user = $this->getUser();
        $userManager->updatePhone($user, $phone);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Get recent login data
     *
     * @Rest\Get("/users/me/login-history/recent", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description=""
     * )
     * @SWG\Tag(name="User")
     *
     * @param LoginManager $loginManager
     * @return View
     */
    public function getUserRecentLogin(LoginManager $loginManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        $serialized = [];

        /** @var Login $login */
        $login = $loginManager->findRecentForUser($user->getId());
        if($login instanceof Login){
            $serialized = $login->serialize();
        }

        return $this->view(['login' => $serialized], JsonResponse::HTTP_OK);
    }

    /**
     * Get my bank list
     *
     * @Rest\Get("/users/me/banks", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description=""
     * )
     * @SWG\Tag(name="User")
     *
     * @param UserBankRepository $userBankRepository
     * @return View
     */
    public function getMyBanks(UserBankRepository $userBankRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();

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
     * Generate my referral link
     *
     * @Rest\Post("/users/me/referral-link", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=201,
     *     description="Referral link data",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="referralLink", type="string", description="Referral link")
     *     )
     * )
     * @SWG\Tag(name="User")
     *
     * @param ReferralLinkRepository $referralLinkRepository
     * @param ReferralManager $referralManager
     * @return View
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postReferralLink(ReferralLinkRepository $referralLinkRepository, ReferralManager $referralManager) : View
    {
        /** @var ReferralLink $referralLink */
        $referralLink = $referralLinkRepository->findLatestByUser($this->getUser());
        if(!($referralLink instanceof ReferralLink)) {
            /** @var ReferralLink $referralLink */
            $referralLink = $referralManager->generateUserReferral($this->getUser());
        }

        return $this->view(['referralLink' => $referralLink->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * Get my referral link
     *
     * @Rest\Get("/users/me/referral-link", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Referral link data",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="referralLink", type="string", description="Referral link")
     *     )
     * )
     * @SWG\Tag(name="User")
     *
     * @param ReferralLinkRepository $referralLinkRepository
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMyReferralLink(ReferralLinkRepository $referralLinkRepository) : View
    {
        /** @var ReferralLink $referralLink */
        $referralLink = $referralLinkRepository->findLatestByUser($this->getUser());
        if(!($referralLink instanceof ReferralLink)) {
            throw new AppException('Referral link not found');
        }

        return $this->view(['referralLink' => $referralLink->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * Get list of my affiliates
     *
     * @Rest\Get("/users/me/affiliates", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description=""
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     * @param AffiliateManager $affiliateManager
     * @param TradeRepository $tradeRepository
     * @return View
     * @throws AppException
     */
    public function getMyAffiliates(Request $request, AffiliateManager $affiliateManager, TradeRepository $tradeRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        try{
            /** @var \DateTime $from */
            $from   = ($request->query->has('from') && $request->query->get('from')) ? new \DateTime($request->query->get('from')) : null;
            /** @var \DateTime $to */
            $to     = ($request->query->has('to') && $request->query->get('to')) ? new \DateTime($request->query->get('to')) : null;
        }catch (\Exception $exception){
            throw new AppException('Invalid date passed');
        }

        $affiliatesEncrypted = [];
        $affiliates = $affiliateManager->getUserAffiliates($user);
        if($affiliates){
            /** @var User $affiliateUser */
            foreach($affiliates as $affiliateUser){

                $referralEarnings = $tradeRepository->getTradedByUserGroupedByPair($affiliateUser, $from, $to);
                if($referralEarnings){
                    foreach ($referralEarnings as &$referralEarning) {
                        $referralEarning['value'] = bcdiv($referralEarning['value'], 4, 8);
                    }
                }

                $item = [
                    'email' => $affiliateUser->getEmailEncrypted(),
                    'referralEarnings' => is_array($referralEarnings) ? $referralEarnings : []
                ];

                $affiliatesEncrypted[] = $item;
            }
        }

        return $this->view([
            'affiliates' => $affiliatesEncrypted,

        ], JsonResponse::HTTP_OK);
    }

    /**
     * Get my affiliate rewards
     *
     * @Rest\Get("/users/me/affiliate-rewards", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description=""
     * )
     * @SWG\Tag(name="User")
     *
     * @param AffiliateRewardRepository $affiliateRewardRepository
     * @return View
     */
    public function getMyAffiliateRewards(AffiliateRewardRepository $affiliateRewardRepository) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        $affiliateRewards = $affiliateRewardRepository->findByUserGroupedByCurrency($user);

        return $this->view(['affiliateRewards' => $affiliateRewards], JsonResponse::HTTP_OK);
    }

    /**
     * Get my Payment Cards
     *
     * @Rest\Get("/users/me/payment-cards", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of PaymentCards"
     * )
     * @SWG\Tag(name="User Payment Cards")
     *
     * @return View
     */
    public function getMyPaymentCards() : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyPaymentCards');

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Get my Payment Cards Registrations
     *
     * @Rest\Get("/users/me/payment-cards-registrations", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Serialized list of PaymentCardsRegistrations"
     * )
     * @SWG\Tag(name="User Payment Cards Registrations")
     *
     * @return View
     */
    public function getMyPaymentCardsRegistrations() : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:getMyPaymentCardsRegistrations');

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Create Payment Card registration
     *
     * @Rest\Post("/users/me/payment-cards", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=201,
     *     description=""
     * )
     * @SWG\Tag(name="User Payment Cards")
     *
     * @return View
     */
    public function postRegisterCard() : View
    {
        $response = $this->forward('App\Controller\ApiCommon\UserController:postRegisterCard');

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }

    /**
     * Disable specified payment card
     *
     * @Rest\Patch("/users/me/payment-cards/{paymentCardId}/disable", requirements={"paymentCardId"="[a-zA-Z0-9-]+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="paymentCardId",              in="path", type="string", description="ID of the Payment Card", required=true)
     * @SWG\Response(
     *     response=204,
     *     description="Payment Card disabled"
     * )
     * @SWG\Tag(name="User Payment Cards")
     *
     * @param string $paymentCardId
     * @param PaymentCardManager $paymentCardManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchDisablePaymentCard(string $paymentCardId, PaymentCardManager $paymentCardManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var PaymentCard $paymentCard */
        $paymentCard = $paymentCardManager->loadCard($paymentCardId);
        if($paymentCard->getUser()->getId() !== $user->getId()) throw new AppException('Card not found');

        $paymentCardManager->disableCard($paymentCard);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
    /**
     * Enable specified payment card
     *
     * @Rest\Patch("/users/me/payment-cards/{paymentCardId}/enable", requirements={"paymentCardId"="[a-zA-Z0-9-]+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="paymentCardId",              in="path", type="string", description="ID of the Payment Card", required=true)
     * @SWG\Response(
     *     response=204,
     *     description="Payment Card enabled"
     * )
     * @SWG\Tag(name="User Payment Cards")
     *
     * @param string $paymentCardId
     * @param PaymentCardManager $paymentCardManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchEnablePaymentCard(string $paymentCardId, PaymentCardManager $paymentCardManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var PaymentCard $paymentCard */
        $paymentCard = $paymentCardManager->loadCard($paymentCardId);
        if($paymentCard->getUser()->getId() !== $user->getId()) throw new AppException('Card not found');

        $paymentCardManager->enableCard($paymentCard);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Set virtual wallet status for the user
     *
     * @Rest\Patch("/users/me/virtual-wallet/{status}", requirements={"status"="\d+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="status",              in="path", type="string", description="New status of the virtual wallet", required=true)
     * @SWG\Response(
     *     response=204,
     *     description="Virtual wallet status updated"
     * )
     * @SWG\Tag(name="User")
     *
     * @param int $status
     * @param UserManager $userManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchVirtualWalletStatus(int $status, UserManager $userManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();

        $userManager->setVirtualWalletStatus($user, $status);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
}
