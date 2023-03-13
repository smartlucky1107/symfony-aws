<?php

namespace App\Controller\ApiAdmin;

use App\DataTransformer\CurrencyTransformer;
use App\Entity\Currency;

use App\Manager\CurrencyManager;
use App\Manager\ListFilter\CurrencyListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Repository\CurrencyRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class CurrencyController extends FOSRestController
{
    /**
     * @Rest\Get("/currencies", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param CurrencyRepository $currencyRepository
     * @param ListManager $listManager
     * @return View
     * @throws \App\Exception\AppException
     */
    public function getCurrencies(Request $request, CurrencyRepository $currencyRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_CURRENCY);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new CurrencyListFilter($request), $currencyRepository)
            ->load();

        return $this->view($paginator, JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Post("/currencies", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param CurrencyTransformer $currencyTransformer
     * @param CurrencyManager $currencyManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postCurrency(Request $request, CurrencyTransformer $currencyTransformer, CurrencyManager $currencyManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_CREATE, VoterRoleInterface::MODULE_CURRENCY);

        /** @var Currency $currency */
        $currency = $currencyTransformer->transform($request);
        $currencyTransformer->validate($currency);

        $currency = $currencyManager->create($currency);

        return $this->view(['currency' => $currency->serialize()], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/currencies/{currencyId}/disable", requirements={"currencyId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $currencyId
     * @param CurrencyManager $currencyManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putCurrencyDisable(int $currencyId, CurrencyManager $currencyManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_CURRENCY);

        $currencyManager->load($currencyId);
        $currencyManager->disable();

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/currencies/{currencyId}/enable", requirements={"currencyId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $currencyId
     * @param CurrencyManager $currencyManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putCurrencyEnable(int $currencyId, CurrencyManager $currencyManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_CURRENCY);

        $currencyManager->load($currencyId);
        $currencyManager->enable();

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Put("/currencies/{currencyId}/fee", requirements={"currencyId"="\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param int $currencyId
     * @param Request $request
     * @param CurrencyManager $currencyManager
     * @return View
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function putCurrencyFee(int $currencyId, Request $request, CurrencyManager $currencyManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_MANAGE, VoterRoleInterface::MODULE_CURRENCY);

        $currencyManager->load($currencyId);

        $currencyManager->updateFee($request->request->get('fee'));

        return $this->view(['message' => 'ok'], JsonResponse::HTTP_OK);
    }
}
