<?php

namespace App\Controller\Api;

use App\Document\Blockchain\BitcoinTx;
use App\Document\Blockchain\EthereumTx;
use App\Entity\Verification;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Manager\iDenfyManager;
use App\Manager\JumioManager;
use App\Manager\VerificationManager;
use App\Model\Blockchain\TxOutput;
use App\Repository\VerificationRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class VerificationController extends FOSRestController
{
    /**
     * @Rest\Post("/verification/initiate", options={"expose"=true})
     *
     * @param Request $request
     * @param VerificationManager $verificationManager
     * @param iDenfyManager $iDenfyManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postVerificationInitiate(Request $request, VerificationManager $verificationManager, iDenfyManager $iDenfyManager) : View
    {
        $verification = $verificationManager->newVerification($this->getUser());
        if(!($verification instanceof Verification)) throw new AppException('Verification cannot be created');

//        $iDenfyResponse = $iDenfyManager->initiate($verification, $request->getLocale());
        $iDenfyResponse = $iDenfyManager->initiate($verification);
        if(isset($iDenfyResponse['authToken']) && isset($iDenfyResponse['scanRef'])){
            $redirectUrl = 'https://ui.idenfy.com/?authToken=' . $iDenfyResponse['authToken'];

            $verification = $verificationManager->updateWithIdenfyInitiate($verification, $iDenfyResponse['scanRef'], $redirectUrl);
        }

        return $this->view(['verification' => $verification->serialize()],JsonResponse::HTTP_CREATED);
    }

    /**
     * @Rest\Get("/verification/recent", options={"expose"=true})
     *
     * @param VerificationRepository $verificationRepository
     * @return View
     * @throws AppException
     * @throws \Exception
     */
    public function getRecentVerification(VerificationRepository $verificationRepository, VerificationManager $verificationManager) : View
    {
        /** @var Verification $verification */
        $verification = $verificationRepository->findRecentByUser($this->getUser());
        if(!$verification->isAllowedForUser($this->getUser())) throw new AppException('Verification not found');

        $verification = $verificationManager->updateExpiredStatus($verification);

        return $this->view(['verification' => $verification->serialize()],JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/verification/{verificationId}", requirements={"verificationId"="\d+"}, options={"expose"=true})
     *
     * @param int $verificationId
     * @param VerificationRepository $verificationRepository
     * @return View
     * @throws \Exception
     */
    public function getVerification(int $verificationId, VerificationRepository $verificationRepository) : View
    {
        /** @var Verification $verification */
        $verification = $verificationRepository->findOrException($verificationId, $this->getUser());
        if(!$verification->isAllowedForUser($this->getUser())) throw new AppException('Verification not found');

        return $this->view(['verification' => $verification->serialize()],JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/verification/{verificationId}/status/{status}", requirements={"verificationId"="\d+", "status"="\d+"}, options={"expose"=true})
     *
     * @param int $verificationId
     * @param int $status
     * @param VerificationManager $verificationManager
     * @return View
     * @throws AppException
     * @throws \Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postVerificationUpdateStatus(int $verificationId, int $status, VerificationManager $verificationManager) : View
    {
        /** @var Verification $verification */
        $verification = $verificationManager->getVerificationRepository()->findOrException($verificationId, $this->getUser());
        if(!$verification->isAllowedForUser($this->getUser())) throw new AppException('Verification not found');

        $verification = $verificationManager->updateStatus($verification, $status);

        return $this->view(['verification' => $verification->serialize()],JsonResponse::HTTP_OK);
    }
}
