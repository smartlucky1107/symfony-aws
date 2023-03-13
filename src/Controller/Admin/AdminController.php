<?php

namespace App\Controller\Admin;

use App\Entity\CheckoutOrder;
use App\Entity\POS\Employee;
use App\Entity\POS\POSOrder;
use App\Entity\POS\Workspace;
use App\Entity\Wallet\Deposit;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Entity\Wallet\InternalTransfer;
use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\Withdrawal;
use App\Manager\TransferManager;
use App\Repository\CheckoutOrderRepository;
use App\Repository\POS\EmployeeRepository;
use App\Repository\POS\POSOrderRepository;
use App\Repository\POS\WorkspaceRepository;
use App\Repository\Wallet\DepositRepository;
use App\Repository\OrderBook\OrderRepository;
use App\Repository\OrderBook\TradeRepository;
use App\Repository\UserRepository;
use App\Repository\Wallet\InternalTransferRepository;
use App\Repository\Wallet\WithdrawalRepository;
use App\Repository\WalletRepository;
use App\Security\TokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AdminController extends AbstractController
{
    /**
     * @Route("/logout", name="admin_logout")
     *
     * @param Request $request
     * @param TokenManager $tokenManager
     * @return RedirectResponse
     */
    public function logout(Request $request, TokenManager $tokenManager) : RedirectResponse
    {
        try{
            $authToken = $request->cookies->get('authToken', null);
            if($authToken){
                $request->cookies->remove('authToken');
                $tokenManager->revokeToken($authToken);
            }
        }catch (\Exception $exception){}

        return $this->redirectToRoute('admin_login');
    }

    /**
     * @Route("/login", name="admin_login", options={"expose"=true})
     *
     * @return Response
     */
    public function login() : Response
    {
        return $this->render('admin/admin/login.html.twig');
    }

    /**
     * @Route("/p/dashboard", name="admin_dashboard", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return Response
     */
    public function dashboard(Request $request) : Response
    {
        return $this->render('admin/admin/dashboard.html.twig');
    }

##############
#### Lists
##
#
    /**
     * @Route("/p/users", name="admin_users")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function users() : Response
    {
        return $this->render('admin/admin/users.html.twig');
    }

    /**
     * @Route("/p/wallets", name="admin_wallets")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function wallets() : Response
    {
        return $this->render('admin/admin/wallets.html.twig');
    }

    /**
     * @Route("/p/orders", name="admin_orders")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function orders() : Response
    {
        return $this->render('admin/admin/orders.html.twig');
    }

    /**
     * @Route("/p/checkout-orders", name="admin_checkout_orders")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function checkoutOrders() : Response
    {
        return $this->render('admin/admin/checkout_orders.html.twig');
    }

    /**
     * @Route("/p/pos-orders", name="admin_pos_orders")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function POSOrders() : Response
    {
        return $this->render('admin/admin/pos_orders.html.twig');
    }

    /**
     * @Route("/p/workspaces", name="admin_workspaces")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function workspaces() : Response
    {
        return $this->render('admin/admin/workspaces.html.twig');
    }

    /**
     * @Route("/p/employees", name="admin_employees")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function employees() : Response
    {
        return $this->render('admin/admin/employees.html.twig');
    }

    /**
     * @Route("/p/trades", name="admin_trades")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function trades() : Response
    {
        return $this->render('admin/admin/trades.html.twig');
    }

    /**
     * @Route("/p/withdrawals", name="admin_withdrawals")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function withdrawals() : Response
    {
        return $this->render('admin/admin/withdrawals.html.twig');
    }

    /**
     * @Route("/p/internal-transfers", name="admin_internal_transfers")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function internalTransfers() : Response
    {
        return $this->render('admin/admin/internal_transfers.html.twig');
    }

    /**
     * @Route("/p/deposits/requests", name="admin_deposit_requests")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function depositRequests() : Response
    {
        return $this->render('admin/admin/deposit_requests.html.twig');
    }

    /**
     * @Route("/p/deposits", name="admin_deposits")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function deposits() : Response
    {
        return $this->render('admin/admin/deposits.html.twig');
    }

    /**
     * @Route("/p/currencies", name="admin_currencies")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function currencies() : Response
    {
        return $this->render('admin/admin/currencies.html.twig');
    }

    /**
     * @Route("/p/currency-pairs", name="admin_currency_pairs")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function currencyPairs() : Response
    {
        return $this->render('admin/admin/currency_pairs.html.twig');
    }

    /**
     * @Route("/p/deposits/new", name="admin_new_deposit")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function newDeposit() : Response
    {
        return $this->render('admin/admin/new_deposit.html.twig');
    }

    /**
     * @Route("/p/voter-roles", name="admin_voter_roles")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function voterRoles() : Response
    {
        return $this->render('admin/admin/voter_roles.html.twig');
    }

    /**
     * @Route("/p/system-tags", name="admin_system_tags")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function systemTags() : Response
    {
        return $this->render('admin/admin/system_tags.html.twig');
    }

    /**
     * @Route("/p/liquidity-reports", name="admin_liquidity_reports")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function liquidityReports() : Response
    {
        return $this->render('admin/admin/liquidity_reports.html.twig');
    }

    /**
     * @Route("/p/liquidity-transactions", name="admin_liquidity_transactions")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function liquidityTransactions() : Response
    {
        return $this->render('admin/admin/liquidity_transactions.html.twig');
    }

    /**
     * @Route("/p/statements", name="admin_statements")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function statements() : Response
    {
        return $this->render('admin/admin/statements.html.twig');
    }

    /**
     * @Route("/p/liquidity-orderbook", name="admin_liquidity_orderbook")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function liquidityOrderbook() : Response
    {
        return $this->render('admin/admin/liquidity_orderbook.html.twig');
    }

    /**
     * @Route("/p/orderbook", name="admin_orderbook")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function orderbook() : Response
    {
        return $this->render('admin/admin/orderbook.html.twig');
    }

    /**
     * @Route("/p/giif", name="admin_giif")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function giif() : Response
    {
        return $this->render('admin/admin/giif.html.twig');
    }

##############
#### Single object page
##
#
    /**
     * @Route("/p/user/{userId}", name="admin_user", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $userId
     * @param UserRepository $userRepository
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function user($userId, UserRepository $userRepository) : Response
    {
        /** @var User $user */
        $user = $userRepository->find($userId);
        if(!($user instanceof User)) return $this->render('admin/admin/not_found.html.twig');

        /** @var User $nextPendingUser */
        $nextPendingUser = $userRepository->findNextPendingUser($user);

        $duplicates = $userRepository->findDuplicates($user);

        return $this->render('admin/admin/user.html.twig', [
            'user' => $user,
            'nextPendingUser' => ($nextPendingUser instanceof User ? $nextPendingUser : null),
            'duplicates' => $duplicates
        ]);
    }

    /**
     * @Route("/p/wallet/{walletId}", name="admin_wallet", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $walletId
     * @param WalletRepository $walletRepository
     * @return Response
     */
    public function wallet($walletId, WalletRepository $walletRepository) : Response
    {
        /** @var Wallet $wallet */
        $wallet = $walletRepository->find($walletId);
        if(!($wallet instanceof Wallet)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/wallet.html.twig', [
            'wallet' => $wallet,
        ]);
    }

    /**
     * @Route("/p/order/{orderId}", name="admin_order", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $orderId
     * @param OrderRepository $orderRepository
     * @param TransferManager $transferManager
     * @return Response
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function order($orderId, OrderRepository $orderRepository, TransferManager $transferManager) : Response
    {
        /** @var Order $order */
        $order = $orderRepository->find($orderId);
        if(!($order instanceof Order)) return $this->render('admin/admin/not_found.html.twig');

        $transfers = $transferManager->loadByOrderId($orderId);

        return $this->render('admin/admin/order.html.twig', [
            'order' => $order,
            'transfers' => $transfers
        ]);
    }

    /**
     * @Route("/p/checkout-order/{checkoutOrderId}", name="admin_checkout_order", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $checkoutOrderId
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @return Response
     */
    public function checkoutOrder($checkoutOrderId, CheckoutOrderRepository $checkoutOrderRepository) : Response
    {
        /** @var CheckoutOrder $checkoutOrder */
        $checkoutOrder = $checkoutOrderRepository->find($checkoutOrderId);
        if(!($checkoutOrder instanceof CheckoutOrder)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/checkout_order.html.twig', [
            'checkoutOrder' => $checkoutOrder
        ]);
    }

    /**
     * @Route("/p/workspace/{workspaceId}", name="admin_workspace", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $workspaceId
     * @param WorkspaceRepository $workspaceRepository
     * @return Response
     */
    public function workspace($workspaceId, WorkspaceRepository $workspaceRepository) : Response
    {
        /** @var Workspace $workspace */
        $workspace = $workspaceRepository->find($workspaceId);
        if(!($workspace instanceof Workspace)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/workspace.html.twig', [
            'workspace' => $workspace
        ]);
    }

    /**
     * @Route("/p/employee/{employeeId}", name="admin_employee", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $employeeId
     * @param EmployeeRepository $employeeRepository
     * @return Response
     */
    public function employee($employeeId, EmployeeRepository $employeeRepository) : Response
    {
        /** @var Employee $employee */
        $employee = $employeeRepository->find($employeeId);
        if(!($employee instanceof Employee)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/employee.html.twig', [
            'employee' => $employee
        ]);
    }

    /**
     * @Route("/p/pos-order/{POSOrderId}", name="admin_pos_order", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $POSOrderId
     * @param POSOrderRepository $POSOrderRepository
     * @return Response
     */
    public function POSOrder($POSOrderId, POSOrderRepository $POSOrderRepository) : Response
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderRepository->find($POSOrderId);
        if(!($POSOrder instanceof POSOrder)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/pos_order.html.twig', [
            'POSOrder' => $POSOrder
        ]);
    }

    /**
     * @Route("/p/trade/{tradeId}", name="admin_trade", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $tradeId
     * @param TradeRepository $tradeRepository
     * @param TransferManager $transferManager
     * @return Response
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function trade($tradeId, TradeRepository $tradeRepository, TransferManager $transferManager) : Response
    {
        /** @var Trade $trade */
        $trade = $tradeRepository->find($tradeId);
        if(!($trade instanceof Trade)) return $this->render('admin/admin/not_found.html.twig');

        $transfers = $transferManager->loadByTradeId($tradeId);

        return $this->render('admin/admin/trade.html.twig', [
            'trade' => $trade,
            'transfers' => $transfers
        ]);
    }

    /**
     * @Route("/p/withdrawal/{withdrawalId}", name="admin_withdrawal", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $withdrawalId
     * @param WithdrawalRepository $withdrawalRepository
     * @return Response
     */
    public function withdrawal($withdrawalId, WithdrawalRepository $withdrawalRepository) : Response
    {
        /** @var Withdrawal $withdrawal */
        $withdrawal = $withdrawalRepository->find($withdrawalId);
        if(!($withdrawal instanceof Withdrawal)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/withdrawal.html.twig', [
            'withdrawal' => $withdrawal
        ]);
    }

    /**
     * @Route("/p/internal-transfer/{internalTransferId}", name="admin_internal_transfer", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $internalTransferId
     * @param InternalTransferRepository $internalTransferRepository
     * @return Response
     */
    public function internalTransfer($internalTransferId, InternalTransferRepository $internalTransferRepository) : Response
    {
        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferRepository->find($internalTransferId);
        if(!($internalTransfer instanceof InternalTransfer)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/internal_transfer.html.twig', [
            'internalTransfer' => $internalTransfer
        ]);
    }

    /**
     * @Route("/p/deposit/{depositId}", name="admin_deposit", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $depositId
     * @param DepositRepository $depositRepository
     * @return Response
     */
    public function deposit($depositId, DepositRepository $depositRepository) : Response
    {
        /** @var Deposit $deposit */
        $deposit = $depositRepository->find($depositId);
        if(!($deposit instanceof Deposit)) return $this->render('admin/admin/not_found.html.twig');

        return $this->render('admin/admin/deposit.html.twig', [
            'deposit' => $deposit
        ]);
    }
}
