<?php

namespace App\Controller\ApiPublic;

use App\Entity\POS\POSOrder;
use App\Exception\AppException;
use App\Manager\POS\POSOrderManager;
use App\Repository\POS\POSOrderRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class POSRedeemController extends FOSRestController
{
    /** @var POSOrderRepository */
    private $POSOrderRepository;

    /**
     * POSRedeemController constructor.
     * @param POSOrderRepository $POSOrderRepository
     */
    public function __construct(POSOrderRepository $POSOrderRepository)
    {
        $this->POSOrderRepository = $POSOrderRepository;
    }

    /**
     * @param Request $request
     * @param bool $withRedeemCode
     * @param bool $withRedeemTransferCode
     * @return POSOrder
     * @throws AppException
     */
    private function getPOSOrder(Request $request, bool $withRedeemCode = false, bool $withRedeemTransferCode = false) : POSOrder
    {
        $signature = $request->get('signature');
        $redeemHash = $request->get('redeemHash');

        $searchBy = [
            'signature' => $signature,
            'redeemHash' => $redeemHash
        ];

        if($withRedeemCode) $searchBy['redeemCode'] = $request->get('code');
        if($withRedeemTransferCode) $searchBy['redeemTransferCode'] = $request->get('transferSmsCode');

        /** @var POSOrder $POSOrder */
        $POSOrder = $this->POSOrderRepository->findOneBy($searchBy);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('POS Order not found');

        return $POSOrder;
    }

    /**
     * Obtain information about POS Order by signature and redeem hash
     *
     * @Rest\Post("/pos/redeem")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about POS Order",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="signature",          type="string",  description="Signature of the POS Order", example="d8as7d98ask32mn4238940"),
     *         @SWG\Property(property="redeemHash",         type="string",  description="Redeem hash of the POS Order", example="das90d8asdasdas789d6ak3j2ne42"),
     *         @SWG\Property(property="code",               type="integer",  description="Redeem Code from SMS", example="12345667"),
     *     )
     * )
     * @SWG\Response(response=200, description="POSOrder object", @Model(type=POSOrder::class, groups={"output_redeem"}))
     * @SWG\Tag(name="POS Redeem")
     *
     * @param Request $request
     * @return View
     * @throws AppException
     */
    public function obtainInfo(Request $request) : View
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $this->getPOSOrder($request, true);

        return $this->view(['POSOrder' => $POSOrder->serializeForRedeem()], JsonResponse::HTTP_OK);
    }

    /**
     * Resend SMS with Redeem Code
     *
     * @Rest\Patch("/pos/redeem/code")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about POS Order",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="signature",          type="string",  description="Signature of the POS Order", example="d8as7d98ask32mn4238940"),
     *         @SWG\Property(property="redeemHash",         type="string",  description="Redeem hash of the POS Order", example="das90d8asdasdas789d6ak3j2ne42"),
     *     )
     * )
     * @SWG\Response(response=204, description="SMS code sent")
     * @SWG\Tag(name="POS Redeem")
     *
     * @param Request $request
     * @param POSOrderManager $POSOrderManager
     * @return View
     * @throws AppException
     */
    public function resendRedeemCode(Request $request, POSOrderManager $POSOrderManager) : View
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $this->getPOSOrder($request);

        $POSOrderManager->sendRedeemCode($POSOrder);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Resend SMS with Redeem Transfer Code
     *
     * @Rest\Patch("/pos/redeem/transfer-code")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about POS Order",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="signature",          type="string",  description="Signature of the POS Order", example="d8as7d98ask32mn4238940"),
     *         @SWG\Property(property="redeemHash",         type="string",  description="Redeem hash of the POS Order", example="das90d8asdasdas789d6ak3j2ne42"),
     *         @SWG\Property(property="code",               type="integer",  description="Redeem Code from SMS", example="12345667"),
     *     )
     * )
     * @SWG\Response(response=204, description="SMS code sent")
     * @SWG\Tag(name="POS Redeem")
     *
     * @param Request $request
     * @param POSOrderManager $POSOrderManager
     * @return View
     * @throws AppException
     */
    public function resendRedeemTransferCode(Request $request, POSOrderManager $POSOrderManager) : View
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $this->getPOSOrder($request, true);

        $POSOrderManager->sendRedeemTransferCode($POSOrder);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Transfer POS Order into External Wallet generated by the system
     *
     * @Rest\Post("/pos/redeem/transfer/external")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about POS Order",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"signature", "redeemHash", "code", "transferSmsCode"},
     *         @SWG\Property(property="signature",          type="string",  description="Signature of the POS Order", example="d8as7d98ask32mn4238940"),
     *         @SWG\Property(property="redeemHash",         type="string",  description="Redeem hash of the POS Order", example="das90d8asdasdas789d6ak3j2ne42"),
     *         @SWG\Property(property="code",               type="integer",  description="Redeem Code from SMS", example="12345667"),
     *         @SWG\Property(property="transferSmsCode",    type="integer",  description="Redeem Transfer Code from SMS", example="12345667"),
     *     )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns private key and POS Order object",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="publicKey",          type="string",  description="Public Key of the External wallet", example="kr23jh4k234bj3h24gu34g23hj4g3jh4g23jh23j4j2"),
     *         @SWG\Property(property="privateKey",         type="string",  description="Private Key of the External wallet", example="kdjhaneq897e4239ex8ue1n9xdh8237nry87gr7n2378yr23nxhx8en9yq98"),
     *         @SWG\Property(property="POSOrder",           @Model(type=POSOrder::class, groups={"output_redeem"}))
     *     )
     * )
     * @SWG\Tag(name="POS Redeem")
     *
     * @param Request $request
     * @return View
     * @throws AppException
     */
    public function transferToExternal(Request $request) : View
    {
        /** @var POSOrder $POSOrder */
        $POSOrder = $this->getPOSOrder($request, false, true);

        $result = [
            'publicKey' => md5(uniqid()),
            'privateKey' => md5(uniqid()),
            'POSOrder' => $POSOrder->serializeForRedeem()
        ];
        $result['POSOrder']['publicKey'] = md5(uniqid());
        $result['POSOrder']['privateKey'] = md5(uniqid());

        return $this->view($result, JsonResponse::HTTP_OK);
    }

    /**
     * Transfer POS Order into Internal Wallet generated by the system
     *
     * @Rest\Patch("/pos/redeem/transfer/internal")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about POS Order",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"signature", "redeemHash", "code", "transferSmsCode"},
     *         @SWG\Property(property="signature",          type="string",  description="Signature of the POS Order", example="d8as7d98ask32mn4238940"),
     *         @SWG\Property(property="redeemHash",         type="string",  description="Redeem hash of the POS Order", example="das90d8asdasdas789d6ak3j2ne42"),
     *         @SWG\Property(property="code",               type="integer",  description="Redeem Code from SMS", example="12345667"),
     *         @SWG\Property(property="transferSmsCode",    type="integer",  description="Redeem Transfer Code from SMS", example="12345667"),
     *     )
     * )
     * @SWG\Response(response=204, description="Transferred")
     * @SWG\Tag(name="POS Redeem")
     *
     * @param Request $request
     * @return View
     */
    public function transferToInternal(Request $request) : View
    {
        return $this->view([], JsonResponse::HTTP_OK);

        // TODO finish implementation

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
}
