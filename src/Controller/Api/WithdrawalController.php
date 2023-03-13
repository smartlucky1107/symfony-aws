<?php

namespace App\Controller\Api;

use App\Entity\Wallet\Withdrawal;
use App\Manager\WithdrawalManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Exception\AppException;

class WithdrawalController extends FOSRestController
{
    /**
     * Confirm withdrawal
     *
     * @Rest\Patch("/withdrawals/{withdrawalId}/confirm-request", requirements={"withdrawalId"="\d+"}, options={"expose"=true})
     *
     * @SWG\Parameter( name="withdrawalId",    in="path", type="integer", description="The id of withdrawal" )
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
     *     description="Withdrawal confirmed",
     * )
     * @SWG\Tag(name="Withdrawal")
     *
     * @param Request $request
     * @param int $withdrawalId
     * @param WithdrawalManager $withdrawalManager
     * @return View
     * @throws AppException
     */
    public function patchWithdrawalConfirmRequest(Request $request, int $withdrawalId, WithdrawalManager $withdrawalManager) : View
    {
        $confirmationHash = (string) $request->get('confirmationHash', '');
        $gAuthCode =        (string) $request->get('gAuthCode', '');

        /** @var Withdrawal $withdrawal */
        $withdrawal = $withdrawalManager->load($withdrawalId);

        $this->denyAccessUnlessGranted('view', $withdrawal->getWallet());

        $withdrawalManager->confirmRequest($withdrawal, $confirmationHash, $gAuthCode);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
}
