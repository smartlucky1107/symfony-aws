<?php

namespace App\Controller\Api;

use App\DataTransformer\CheckoutOrderTransformer;
use App\Entity\CheckoutOrder;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\ListFilter\CheckoutOrderListFilter;
use App\Manager\ListManager\ListManager;
use App\Manager\ListManager\Paginator;
use App\Manager\CheckoutOrderManager;
use App\Repository\CheckoutOrderRepository;
use App\Security\VoterRoleInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutOrderController extends FOSRestController
{
    /**
     * @Rest\Get("/checkout-orders", options={"expose"=true})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param ListManager $listManager
     * @return View
     * @throws \Exception
     */
    public function getCheckoutOrders(Request $request, CheckoutOrderRepository $checkoutOrderRepository, ListManager $listManager) : View
    {
        $this->denyAccessUnlessGranted(VoterRoleInterface::ACTION_LIST, VoterRoleInterface::MODULE_CHECKOUT_ORDER);

        /** @var Paginator $paginator */
        $paginator = $listManager
            ->init(new CheckoutOrderListFilter($request), $checkoutOrderRepository)
            ->load();

        return $this->view($paginator, Response::HTTP_OK);
    }

    /**
     * Create new Checkout order
     *
     * @Rest\Post("/checkout-orders", options={"expose"=true})
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Details about CheckoutOrder",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="type", type="integer", description="Type of the order", enum={1,2}),
     *         @SWG\Property(property="amount", type="string", description="Amount of the base currency", example="0.03"),
     *         @SWG\Property(property="currencyPair", type="string", description="Short name of the currency, eg. BTC-PLN", example="BTC-PLN"),
     *         @SWG\Property(property="paymentProcessor", type="integer", description="ID of selected payment processor", enum={1,2,3})
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Serialized CheckoutOrder object",
     *     @Model(type=CheckoutOrder::class, groups={"output"})
     * )
     * @SWG\Tag(name="Checkout orders")
     *
     * @param Request $request
     * @param CheckoutOrderTransformer $checkoutOrderTransformer
     * @param CheckoutOrderManager $checkoutOrderManager
     * @return View
     * @throws AppException
     * @throws \Exception
     */
    public function postCheckoutOrder(Request $request, CheckoutOrderTransformer $checkoutOrderTransformer, CheckoutOrderManager $checkoutOrderManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');

        /** @var CheckoutOrder $checkoutOrder */
        $checkoutOrder = $checkoutOrderTransformer->transform($this->getUser() , $request);
        $checkoutOrderTransformer->validate($checkoutOrder);

        try{
            /** @var CheckoutOrder $checkoutOrder */
            $checkoutOrder = $checkoutOrderManager->placeOrder($checkoutOrder);
        }catch (AppException $appException){
            throw new AppException($appException->getMessage());
        }catch (\Exception $exception){
            throw new AppException('Error occurred');
        }

        return $this->view(['order' => $checkoutOrder->serializeBasic()], Response::HTTP_CREATED);
    }

    /**
     * Pay specified CheckoutOrder - prepare the object and return real payment URL
     *
     * @Rest\Patch("/checkout-orders/{checkoutOrderId}/pay", options={"expose"=true})
     *
     * @SWG\Parameter( name="checkoutOrderId",        in="path",      type="string", description="The ID of the CheckoutOrder" )
     * @SWG\Response(response=200, description="Real payment URL")
     * @SWG\Tag(name="Checkout orders")
     *
     * @param string $checkoutOrderId
     * @param CheckoutOrderManager $checkoutOrderManager
     * @return View
     * @throws AppException
     */
    public function payCheckoutOrder(string $checkoutOrderId, CheckoutOrderManager $checkoutOrderManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');

        /** @var CheckoutOrder $checkoutOrder */
        $checkoutOrder = $checkoutOrderManager->load($checkoutOrderId);
        if(!$checkoutOrder->isUserAllowed($user)) throw new AppException('Order not found');
        // TODO change that into voters

        try{
            $checkoutOrder = $checkoutOrderManager->preparePayment($checkoutOrder);
        }catch (AppException $appException){
            throw new AppException($appException->getMessage());
        }catch (\Exception $exception){
            throw new AppException('Error occurred');
        }

        return $this->view(['paymentUrl' => $checkoutOrder->getPaymentUrl()], Response::HTTP_OK);
    }

    /**
     * Get detailed information about specified Checkout Order
     *
     * @Rest\Get("/checkout-orders/{checkoutOrderId}", options={"expose"=true})
     *
     * @SWG\Parameter( name="checkoutOrderId",        in="path",      type="string", description="The ID of the CheckoutOrder" )
     * @SWG\Response(
     *     response=200,
     *     description="Returns a CheckoutOrder for a given id",
     *     @Model(type=CheckoutOrder::class, groups={"output"})
     * )
     * @SWG\Tag(name="Checkout orders")
     *
     * @param string $checkoutOrderId
     * @param CheckoutOrderManager $checkoutOrderManager
     * @return View
     * @throws AppException
     */
    public function getCheckoutOrder(string $checkoutOrderId, CheckoutOrderManager $checkoutOrderManager) : View
    {
        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for trading');
        if(!$user->isPhoneConfirmed()) throw new AppException('User is not allowed to process the action');
        if(!$user->isTier1Approved()) throw new AppException('User is not allowed for trading');

        /** @var CheckoutOrder $checkoutOrder */
        $checkoutOrder = $checkoutOrderManager->load($checkoutOrderId);
        if(!$checkoutOrder->isUserAllowed($user)) throw new AppException('Order not found');
        // TODO change that into voters

        return $this->view(['order' => $checkoutOrder->serializeBasic()], Response::HTTP_OK);
    }
}
