<?php

namespace App\Controller\ApiCommon;

use App\Document\Login;
use App\Entity\PaymentCard;
use App\Entity\PaymentCardRegistration;
use App\Entity\POS\Workspace;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\ListFilter\OrderListFilter;
use App\Manager\ListFilter\CheckoutOrderListFilter;
use App\Manager\ListFilter\POSOrderListFilter;
use App\Manager\ListFilter\TradeListFilter;
use App\Manager\ListFilter\WalletListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\LoginManager;
use App\Manager\Payment\PaywallManager;
use App\Manager\PaymentCardManager;
use App\Manager\WalletManager;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\OrderBook\TradeRepository;
use App\Repository\PaymentCardRegistrationRepository;
use App\Repository\PaymentCardRepository;
use App\Repository\CheckoutOrderRepository;
use App\Repository\POS\POSOrderRepository;
use App\Repository\WalletRepository;
use App\Service\Parser\ParserCSV;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends AbstractController
{
    /**
     * @param string $currencyShortName
     * @param WalletManager $walletManager
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyWallet(string $currencyShortName, WalletManager $walletManager): JsonResponse
    {
        $wallet = $walletManager->loadByUserAndCurrency($this->getUser(), $currencyShortName);

        return new JsonResponse(['wallet' => $wallet->serialize()], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param WalletRepository $walletRepository
     * @param ListManager $listManager
     * @param bool $isForPrivateApi
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyWallets(Request $request, WalletRepository $walletRepository, ListManager $listManager, $isForPrivateApi = false): JsonResponse
    {
        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new WalletListFilter($request, $this->getUser()), $walletRepository)
            ->load($isForPrivateApi);

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param OrderRepository $orderRepository
     * @param ListManager $listManager
     * @param bool $isForPrivateApi
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyOrders(Request $request, OrderRepository $orderRepository, ListManager $listManager, $isForPrivateApi = false): JsonResponse
    {
        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new OrderListFilter($request, $this->getUser()), $orderRepository)
            ->load($isForPrivateApi);

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param ListManager $listManager
     * @param bool $isForPrivateApi
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyCheckoutOrders(Request $request, CheckoutOrderRepository $checkoutOrderRepository, ListManager $listManager, $isForPrivateApi = false): JsonResponse
    {
        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new CheckoutOrderListFilter($request, $this->getUser()), $checkoutOrderRepository)
            ->load($isForPrivateApi);

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param ListManager $listManager
     * @param bool $isForPrivateApi
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyCheckoutOrdersHistory(Request $request, CheckoutOrderRepository $checkoutOrderRepository, ListManager $listManager, $isForPrivateApi = false, ParserCSV $parserCSV): StreamedResponse
    {
        $response = new StreamedResponse();

        $request->query->set('status', 100);
        $request->query->set('pageSize', 9999);
        $request->query->set('page', 1);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new CheckoutOrderListFilter($request, $this->getUser()), $checkoutOrderRepository)
            ->load($isForPrivateApi);

        $response->setCallback(function () use ($paginator, $parserCSV) {
            $parserCSV->generateCSV($paginator->result);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            "transactions_" . date("Ymd_Hi") . ".csv"
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @param Request $request
     * @param POSOrderRepository $POSOrderRepository
     * @param ListManager $listManager
     * @param bool $isForPrivateApi
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyPOSOrders(Request $request, POSOrderRepository $POSOrderRepository, ListManager $listManager, $isForPrivateApi = false): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Workspace $workspace */
        $workspace = $user->getWorkspace();
        if (!($workspace instanceof Workspace)) throw new AppException('Workspace not found');

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new POSOrderListFilter($request, $workspace), $POSOrderRepository)
            ->load($isForPrivateApi);

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @param ListManager $listManager
     * @param bool $isForPrivateApi
     * @return JsonResponse
     * @throws \Exception
     */
    public function getMyTrades(Request $request, TradeRepository $tradeRepository, ListManager $listManager, $isForPrivateApi = false): JsonResponse
    {
        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new TradeListFilter($request, $this->getUser()), $tradeRepository)
            ->load($isForPrivateApi, $this->getUser());

        return new JsonResponse($paginator, Response::HTTP_OK);
    }

    /**
     * @param LoginManager $loginManager
     * @return JsonResponse
     */
    public function getMyLoginHistory(LoginManager $loginManager): JsonResponse
    {
        $result = [];

        $loginHistory = $loginManager->findForUser($this->getUser()->getId());
        /** @var Login $loginHistoryItem */
        foreach ($loginHistory as $loginHistoryItem) {
            $result[] = $loginHistoryItem->serialize();
        }

        return new JsonResponse(['loginHistory' => $result], Response::HTTP_OK);
    }

    /**
     * @param PaymentCardRepository $paymentCardRepository
     * @return JsonResponse
     */
    public function getMyPaymentCards(PaymentCardRepository $paymentCardRepository): JsonResponse
    {
//        return new JsonResponse(['cards' => []], Response::HTTP_OK);

        /** @var User $user */
        $user = $this->getUser();

        $result = [];

        $paymentCards = $paymentCardRepository->findBy(['user' => $user->getId(), 'enabled' => true]);
        if ($paymentCards) {
            /** @var PaymentCard $paymentCard */
            foreach ($paymentCards as $paymentCard) {
                $result[] = $paymentCard->serialize();
            }
        }

        return new JsonResponse(['cards' => $result], Response::HTTP_OK);
    }

    /**
     * @param PaymentCardRegistrationRepository $paymentCardRegistrationRepository
     * @return JsonResponse
     */
    public function getMyPaymentCardsRegistrations(PaymentCardRegistrationRepository $paymentCardRegistrationRepository): JsonResponse
    {
//        throw new AppException('Cannot register new card');

        /** @var User $user */
        $user = $this->getUser();

        $result = [];

        $paymentCardRegistrations = $paymentCardRegistrationRepository->findBy(['user' => $user->getId(), 'status' => PaymentCardRegistration::STATUS_NEW]);
        if ($paymentCardRegistrations) {
            /** @var PaymentCardRegistration $paymentCardRegistration */
            foreach ($paymentCardRegistrations as $paymentCardRegistration) {
                $result[] = $paymentCardRegistration->serialize();
            }
        }

        return new JsonResponse(['registrations' => $result], Response::HTTP_OK);
    }

    /**
     * @param PaywallManager $paywallManager
     * @param PaymentCardManager $paymentCardManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postRegisterCard(PaywallManager $paywallManager, PaymentCardManager $paymentCardManager): JsonResponse
    {
//        throw new AppException('Cannot register new card');

        /** @var User $user */
        $user = $this->getUser();

        $cardRegistrationResponse = $paywallManager->registerCard($user);

        /** @var PaymentCardRegistration $paymentCardRegistration */
        $paymentCardRegistration = $paymentCardManager->createRegistration($user, $cardRegistrationResponse->registrationId);;

        return new JsonResponse(['redirectUrl' => $cardRegistrationResponse->redirectUrl], Response::HTTP_CREATED);
    }
}
