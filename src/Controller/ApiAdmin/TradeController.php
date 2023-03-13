<?php

namespace App\Controller\ApiAdmin;

use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Manager\ListFilter\TradeListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\WalletManager;
use App\Repository\CurrencyRepository;

use App\Repository\OrderBook\TradeRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class TradeController extends FOSRestController
{
    /**
     * @Rest\Get("/trades", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @param ListManager $listManager
     * @return View
     * @throws \Exception
     */
    public function getTrades(Request $request, TradeRepository $tradeRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_TRADE);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new TradeListFilter($request), $tradeRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }
}
