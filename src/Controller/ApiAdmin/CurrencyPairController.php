<?php

namespace App\Controller\ApiAdmin;

use App\DataTransformer\CurrencyPairTransformer;
use App\Entity\CurrencyPair;
use App\Manager\CurrencyPairManager;
use App\Manager\ListFilter\CurrencyPairListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\CurrencyPairRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class CurrencyPairController extends FOSRestController
{
    /**
     * @Rest\Get("/currency-pairs", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param CurrencyPairRepository $currencyPairRepository
     * @param ListManager $listManager
     * @return View
     * @throws \App\Exception\AppException
     */
    public function getCurrencyPairs(Request $request, CurrencyPairRepository $currencyPairRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_CURRENCY_PAIR);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new CurrencyPairListFilter($request), $currencyPairRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/currency-pairs", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param CurrencyPairTransformer $currencyPairTransformer
     * @param CurrencyPairManager $currencyPairManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postCurrencyPair(Request $request, CurrencyPairTransformer $currencyPairTransformer, CurrencyPairManager $currencyPairManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_CREATE, VoterRoleInterface::MODULE_CURRENCY_PAIR);

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $currencyPairTransformer->transform($request);
        $currencyPairTransformer->validate($currencyPair);

        $currencyPair = $currencyPairManager->update($currencyPair);

        return $this->view(['currencyPair' => $currencyPair->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/currency-pairs/{currencyPairId}/disable", requirements={"currencyPairId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $currencyPairId
     * @param CurrencyPairManager $currencyPairManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putCurrencyPairDisable(int $currencyPairId, CurrencyPairManager $currencyPairManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_CURRENCY_PAIR);

        $currencyPairManager->load($currencyPairId);
        $currencyPairManager->disable();

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/currency-pairs/{currencyPairId}/enable", requirements={"currencyPairId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $currencyPairId
     * @param CurrencyPairManager $currencyPairManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putCurrencyPairEnable(int $currencyPairId, CurrencyPairManager $currencyPairManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_CURRENCY_PAIR);

        $currencyPairManager->load($currencyPairId);
        $currencyPairManager->enable();

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }
}
