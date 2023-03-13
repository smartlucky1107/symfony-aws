<?php

namespace App\Controller\ApiPublic;

use App\Document\OHLC;
use App\Entity\User;
use App\Manager\Charting\OHLCManager;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends FOSRestController
{
    /**
     * @Rest\Get("/statistics/registered-users", options={"expose"=true})
     *
     * @param Request $request
     * @param UserRepository $userRepository
     * @return View
     * @throws \Exception
     */
    public function getRegisteredUsersDaily(Request $request, UserRepository $userRepository) : View
    {
        $dateStart = new \DateTime($request->get('dateStart'));
        $dateEnd = new \DateTime($request->get('dateEnd'));

        $result = [
            'registered'    => [],
            'tier2Verified' => [],
            'tier3Verified' => [],
        ];

        $registered = $userRepository->findRegisteredBetweenDates($dateStart, $dateEnd);

        while($dateStart <= $dateEnd){
            $countRegistered = 0;
            $countTier2Verified = 0;
            $countTier3Verified = 0;

            /** @var \DateTime $minDate */
            $minDate = (clone $dateStart)->setTime(0, 0, 0);
            /** @var \DateTime $maxDate */
            $maxDate = (clone $dateStart)->setTime(0, 0, 0)->modify('+1 day');

            foreach($registered as $item){
                if(isset($item['createdAt']) && $item['createdAt'] instanceof \DateTime){
                    if($item['createdAt'] >= $minDate && $item['createdAt'] <= $maxDate){
                        $countRegistered++;

                        if(isset($item['verificationStatus'])){
                            if($item['verificationStatus'] === User::VERIFICATION_TIER2_APPROVED){
                                $countTier2Verified++;
                            }elseif($item['verificationStatus'] === User::VERIFICATION_TIER3_APPROVED){
                                $countTier3Verified++;
                            }
                        }

                        continue;
                    }
                }
            }

            $result['registered'][] = $countRegistered;
            $result['tier2Verified'][] = $countTier2Verified;
            $result['tier3Verified'][] = $countTier3Verified;

            $dateStart->modify('+1 day');
        }

        return $this->view($result, JsonResponse::HTTP_OK);
    }
}
