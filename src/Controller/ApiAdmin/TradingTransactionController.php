<?php

namespace App\Controller\ApiAdmin;

use App\Document\TradingTransaction;
use App\Manager\TradingTransactionManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TradingTransactionController extends FOSRestController
{
    /**
     * @Rest\Get("/trading-transactions/not-processed", options={"expose"=true})
     *
     * @param TradingTransactionManager $tradingTransactionManager
     * @return View
     */
    public function getTradingTransactionsNotProcessed(TradingTransactionManager $tradingTransactionManager): View
    {
        // TODO dodać ACL

        $results = [];

        $tradingTransactions = $tradingTransactionManager->findNotProcessed();
        if ($tradingTransactions) {
            /** @var TradingTransaction $tradingTransaction */
            foreach ($tradingTransactions as $tradingTransaction) {
                $results[] = $tradingTransaction->serialize();
            }
        }

        return $this->view(['tradingTransactions' => $results], JsonResponse::HTTP_OK);
    }
}
