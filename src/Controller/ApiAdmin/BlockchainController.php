<?php

namespace App\Controller\ApiAdmin;

use App\Document\Blockchain\BitcoinTx;
use App\Document\Blockchain\EthereumTx;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Manager\BlockchairManager;
use App\Model\Blockchain\TxOutput;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class BlockchainController extends FOSRestController
{
//    /**
//     * @Rest\Post("/blockchain/bitcoin-tx", options={"expose"=true})
//     * @Security("is_granted('ROLE_ADMIN')")
//     *
//     * @param Request $request
//     * @return View
//     */
//    public function postBlockchainBitcoinTx(Request $request) : View
//    {
//        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_CREATE, VoterRoleInterface::MODULE_BLOCKCHAIN);
//
//        $response = $this->forward('App\Controller\ApiCommon\BlockchainController:postBlockchainBitcoinTx', [
//            'request'  => $request,
//        ]);
//
//        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
//    }
//
//    /**
//     * @Rest\Post("/blockchain/ethereum-tx", options={"expose"=true})
//     * @Security("is_granted('ROLE_ADMIN')")
//     *
//     * @param Request $request
//     * @return View
//     */
//    public function postBlockchainEthereumTx(Request $request) : View
//    {
//        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_CREATE, VoterRoleInterface::MODULE_BLOCKCHAIN);
//
//        $response = $this->forward('App\Controller\ApiCommon\BlockchainController:postBlockchainEthereumTx', [
//            'request'  => $request,
//        ]);
//
//        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
//    }

    /**
     * @Rest\Get("/blockchain/{blockchain}/transactions/{address}", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param $blockchain
     * @param $address
     * @param Request $request
     * @param BlockchairManager $blockchairManager
     * @return View
     * @throws AppException
     */
    public function getBlockchainTransactions($blockchain, $address, Request $request, BlockchairManager $blockchairManager) : View
    {
        try{
            /** @var \DateTime $from */
            $from   = ($request->query->has('from') && $request->query->get('from')) ? new \DateTime($request->query->get('from')) : null;
            /** @var \DateTime $to */
            $to     = ($request->query->has('to') && $request->query->get('to')) ? new \DateTime($request->query->get('to')) : null;
        }catch (\Exception $exception){
            throw new AppException('Invalid date passed');
        }

        $transactions = $blockchairManager->loadTransactions($blockchain, $address, $from, $to);

        return $this->view(['transactions' => $transactions], JsonResponse::HTTP_OK);
    }
}
