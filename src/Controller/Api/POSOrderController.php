<?php

namespace App\Controller\Api;

use App\Security\SystemTagAccessResolver;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Exception\AppException;

class POSOrderController extends FOSRestController
{
    /** @var SystemTagAccessResolver */
    private $systemTagAccessResolver;

    /**
     * AuthController constructor.
     * @param SystemTagAccessResolver $systemTagAccessResolver
     */
    public function __construct(SystemTagAccessResolver $systemTagAccessResolver)
    {
        $this->systemTagAccessResolver = $systemTagAccessResolver;
    }

    /**
     * Get information about POS Order
     *
     * @Rest\Get("/pos-orders/{POSOrderId}", requirements={"POSOrderId"="\d+"}, options={"expose"=true})
     *
     * @SWG\Response(
     *     response=200,
     *     description="POSOrder object"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="POSOrder not found"
     * )
     * @SWG\Tag(name="POS Order")
     *
     * @param $POSOrderId
     * @return View
     * @throws AppException
     */
    public function getPOSOrder($POSOrderId) : View
    {
        $this->systemTagAccessResolver->authPos();

        $response = $this->forward('App\Controller\ApiCommon\POSOrderController:getPOSOrder', [
            'POSOrderId'  => $POSOrderId,
        ]);

        return $this->view(json_decode($response->getContent(), true), $response->getStatusCode());
    }
}
