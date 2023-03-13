<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;

class CurrencyController extends FOSRestController
{
    /**
     * Get list of all currency types
     *
     * @Rest\Get("/currency-types", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns array of currency types",
     * )
     * @SWG\Tag(name="Currency")
     *
     * @return View
     */
    public function getCurrencyTypes(){
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_CURRENCY);

        return $this->view(['types' => Currency::getAllowedTypes()], JsonResponse::HTTP_OK);
    }

    /**
     * Get list of all enabled currencies
     *
     * @Rest\Get("/currencies/basic", options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns array of serialized Currency objects",
     * )
     * @SWG\Tag(name="Currency")
     *
     * @param CurrencyRepository $currencyRepository
     * @return View
     * @throws \Exception
     */
    public function getCurrenciesBasic(CurrencyRepository $currencyRepository) : View
    {
        $serialized = [];

        $currencies = $currencyRepository->findEnabled();
        if($currencies){
            /** @var Currency $currency */
            foreach($currencies as $currency){
                $serialized[] = $currency->serializeBasic();

            }
        }

        return $this->view(['currencies' => $serialized], JsonResponse::HTTP_OK);
    }
}
