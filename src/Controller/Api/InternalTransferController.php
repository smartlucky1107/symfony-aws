<?php

namespace App\Controller\Api;

use App\Entity\Wallet\InternalTransfer;
use App\Manager\InternalTransferManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Exception\AppException;

class InternalTransferController extends FOSRestController
{
    /**
     * Confirm internal transfer
     *
     * @Rest\Patch("/internal-transfers/{internalTransferId}/confirm-request", requirements={"internalTransferId"="\d+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="internalTransferId",    in="path", type="integer", description="The id of internal transfer" )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Hash and google auth code",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"confirmationHash"},
     *         @SWG\Property(property="confirmationHash",   type="string", description="Confirmation hash from the e-mail message"),
     *         @SWG\Property(property="gAuthCode",          type="string", description="Google Authenticator Code")
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Internal transfer confirmed",
     * )
     * @SWG\Tag(name="Internal transfer")
     *
     * @param Request $request
     * @param int $internalTransferId
     * @param InternalTransferManager $internalTransferManager
     * @return View
     * @throws AppException
     */
    public function patchInternalTransferConfirmRequest(Request $request, int $internalTransferId, InternalTransferManager $internalTransferManager) : View
    {
        $confirmationHash = (string) $request->get('confirmationHash', '');
        $gAuthCode =        (string) $request->get('gAuthCode', '');

        /** @var InternalTransfer $internalTransfer */
        $internalTransfer = $internalTransferManager->load($internalTransferId);

        $this->denyAccessUnlessGranted('view', $internalTransfer->getWallet());

        $internalTransferManager->confirmRequest($internalTransfer, $confirmationHash, $gAuthCode);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
}
