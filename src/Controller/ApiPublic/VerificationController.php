<?php

namespace App\Controller\ApiPublic;

use App\Entity\User;
use App\Entity\Verification;
use App\Manager\UserManager;
use App\Repository\VerificationRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends FOSRestController
{
//    /**
//     * @Rest\Post("/verification/callback/jumio", options={"expose"=true})
//     *
//     * @param Request $request
//     * @param VerificationRepository $verificationRepository
//     * @param UserManager $userManager
//     * @return View
//     * @throws \Doctrine\ORM\ORMException
//     * @throws \Doctrine\ORM\OptimisticLockException
//     */
//    public function postVerificationCallbackJumioCallback(Request $request, VerificationRepository $verificationRepository, UserManager $userManager) : View
//    {
//        $customerId = $request->get('customerId');
//        $merchantIdScanReference = $request->get('merchantIdScanReference');
//        $jumioIdScanReference = $request->get('jumioIdScanReference');
//        $verificationStatus = $request->get('verificationStatus');
//
//        $idScanStatus   = $request->get('idScanStatus');
//        $idFirstName    = $request->get('idFirstName');
//        $idLastName     = $request->get('idLastName');
//        $idDob          = $request->get('idDob');
//        $idExpiry       = $request->get('idExpiry');
//
//        $identityVerification = $request->get('identityVerification');
//
//        if($customerId && $merchantIdScanReference && $jumioIdScanReference && $verificationStatus){
//            $userId = (int) str_replace('user_', '', $customerId);
//            $verificationId = (int) str_replace('verification_', '', $merchantIdScanReference);
//
//            /** @var Verification $verification */
//            $verification = $verificationRepository->findOneBy([
//                'id'    => $verificationId,
//                'user'  => $userId,
//                'transactionReference' => $jumioIdScanReference
//            ]);
//            if($verification instanceof Verification) {
//                $identityVerification = (array) json_decode($identityVerification);
//
//                $isVerificationValid = false;
//
////                if($verificationStatus === 'APPROVED_VERIFIED' && $idScanStatus === 'SUCCESS'){
////                    if(isset($identityVerification['similarity']) && isset($identityVerification['validity'])){
////                        if($identityVerification['similarity'] === 'MATCH' && strtolower((string)$identityVerification['validity']) === 'true'){
////                            $isVerificationValid = true;
////                        }
////                    }
////                }
//
//                if($verificationStatus === 'APPROVED_VERIFIED'){
//                    $isVerificationValid = true;
//                }
//
//                if($isVerificationValid){
//                    try{
//                        /** @var User $user */
//                        $user = $verification->getUser();
//
//                        $userManager->approveTier2($user);
//                    }catch (\Exception $exception){
//
//                    }
//                }else{
//                    $verification->setStatus(Verification::STATUS_ERROR);
//                    $verificationRepository->save($verification);
//                }
//            }
//        }
//
//        return $this->view(['status' => 'OK'],JsonResponse::HTTP_OK);
//    }

    /**
     * @Rest\Post("/verification/callback/idenfy", options={"expose"=true})
     *
     * @param Request $request
     * @param VerificationRepository $verificationRepository
     * @param UserManager $userManager
     * @return Response
     */
    public function postVerificationCallbackIdenfy(Request $request, VerificationRepository $verificationRepository, UserManager $userManager)
    {
        $requestData = $request->request->all();

        try{
            $final = (bool) $requestData['final'];
            $scanRef = $requestData['scanRef'];
            $status = (array) $requestData['status'];
            $statusOverall = $status['overall'];

            $verification = $verificationRepository->findOneBy(['transactionReference' => $scanRef]);
            if($verification instanceof Verification){
                $isVerificationValid = false;

                if($final && $statusOverall === 'APPROVED'){
                    $isVerificationValid = true;
                }

                if($isVerificationValid){
                    try{
                        $user = $verification->getUser();

                        $userManager->approveTier3($user);

                        $verification->setStatus(Verification::STATUS_SUCCESS);
                        $verificationRepository->save($verification);
                    }catch (\Exception $exception){

                    }
                }else{
                    $verification->setStatus(Verification::STATUS_ERROR);
                    $verificationRepository->save($verification);
                }
            }
        }catch (\Exception $exception){

        }

        return new Response('');
    }
}
