<?php

namespace App\Controller\ApiCommon;

use App\DataTransformer\POSOrderTransformer;
use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Entity\POS\Employee;
use App\Entity\POS\POSOrder;
use App\Entity\POS\Workspace;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\POS\POSOrderManager;
use App\Repository\CurrencyPairRepository;
use App\Repository\POS\EmployeeRepository;
use App\Repository\POS\POSOrderRepository;
use App\Repository\POS\WorkspaceRepository;
use App\Resolver\InstantPriceResolver;
use App\Resolver\POS\WorkspaceEmployeeResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class POSController extends AbstractController
{
    /**
     * @param string|null $workspaceName
     * @return Workspace
     * @throws AppException
     */
    private function getUserWorkspace(string $workspaceName = null) : Workspace
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Workspace $workspace */
        $workspace = $user->getWorkspace();
        if(!($workspace instanceof Workspace)) throw new AppException('Workspace not exists');

        if(!is_null($workspaceName)){
            if($workspace->getName() !== $workspaceName) throw new AppException('Workspace not exists');
        }

        return $workspace;
    }

    /**
     * @param string $workspaceName
     * @return JsonResponse
     */
    public function getWorkspaceExists(string $workspaceName) : JsonResponse
    {
        $exists = false;

        try{
            /** @var Workspace $workspace */
            $workspace = $this->getUserWorkspace($workspaceName);
            if($workspace instanceof Workspace) $exists = true;
        }catch (\Exception $exception){}

        return new JsonResponse(['exists' => $exists], JsonResponse::HTTP_OK);
    }

    /**
     * @param string $workspaceName
     * @param Request $request
     * @return JsonResponse
     */
    public function postWorkspaceAuth(string $workspaceName, Request $request) : JsonResponse
    {
        $exists = false;

        try{
            /** @var Workspace $workspace */
            $workspace = $this->getUserWorkspace($workspaceName);
            if($workspace instanceof Workspace) {
                if(!$request->headers->has('auth-workspace-pin')) throw new AppException('Workspace PIN is required');

                $workspacePin = (int) $request->headers->get('auth-workspace-pin');
                if(!$workspacePin) throw new AppException('Workspace PIN is required');

                if($workspacePin === $workspace->getPin()) $exists = true;
            }
        }catch (\Exception $exception){}

        if($exists){
            return new JsonResponse([], JsonResponse::HTTP_OK);
        }else{
            return new JsonResponse([], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @param string $workspaceName
     * @param CurrencyPairRepository $currencyPairRepository
     * @return JsonResponse
     */
    public function getWorkspaceCurrencies(string $workspaceName, CurrencyPairRepository $currencyPairRepository) : JsonResponse
    {
        $result = [];
        $pairs = $currencyPairRepository->findEnabledForPOS();

        /** @var CurrencyPair $currencyPair */
        foreach($pairs as $currencyPair){
            $result[] = $currencyPair->serializeForPOSApi();
        }

        return new JsonResponse($result, JsonResponse::HTTP_OK);
    }

    /**
     * @param string $workspaceName
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderTransformer $POSOrderTransformer
     * @param POSOrderManager $POSOrderManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Exception
     */
    public function postWorkspaceOrder(string $workspaceName, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderTransformer $POSOrderTransformer, POSOrderManager $POSOrderManager) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        /** @var POSOrder $posOrder */
        $posOrder = $POSOrderTransformer->transform($employee, $request);
        $POSOrderTransformer->validate($posOrder);

        if($request->request->has('place-order') && $request->request->get('place-order') === true){
            try{
                $POSOrderManager->placeOrder($posOrder);
            }catch (AppException $appException){
                throw new AppException($appException->getMessage());
            }catch (\Exception $exception){
                throw new AppException('Error occurred');
            }

            return new JsonResponse(['POSOrder' => $posOrder->serializeForPOSApi()], JsonResponse::HTTP_CREATED);
        }else{
            return new JsonResponse([
                'amount' => $posOrder->toPrecision($posOrder->getAmount()),
                'initiationPrice'  => $posOrder->toPrecisionQuoted($posOrder->getInitiationPrice()),
                'totalPrice' => $posOrder->toPrecisionQuoted($posOrder->getTotalPrice())
            ], JsonResponse::HTTP_OK);
        }
    }

    /**
     * @param string $workspaceName
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @return JsonResponse
     * @throws AppException
     */
    public function patchWorkspaceEmployeePing(string $workspaceName, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param string $workspaceName
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderManager $POSOrderManager
     * @return JsonResponse
     * @throws AppException
     */
    public function getWorkspaceEmployeeTransactions(string $workspaceName, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderManager $POSOrderManager) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        $serialized = [];
        $posOrders = $POSOrderManager->getPOSOrderRepository()->findBy([
            'workspace' => $workspace->getId(),
            'employee' => $employee->getId(),
        ]);
        if($posOrders){
            /** @var POSOrder $POSOrder */
            foreach($posOrders as $POSOrder){
                $serialized[] = $POSOrder->serializeForPOSApi();
            }
        }

        return new JsonResponse($serialized, JsonResponse::HTTP_OK);
    }

    /**
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderManager $POSOrderManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function confirmPOSOrder(string $workspaceName, int $POSOrderId, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderManager $POSOrderManager) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderManager->getPOSOrderRepository()->findOneBy([
            'id' => $POSOrderId,
            'workspace' => $workspace->getId(),
            'employee' => $employee->getId()
        ]);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('POS order not found');

        $confirmationCode = (string) $request->request->get('code', '');

        $POSOrderManager->confirm($POSOrder, $confirmationCode);

        // TODO dodać tutaj zeby był auto reject jesli confirm nie przejdzie
//        try{
//
//        }catch (\Exception $exception){
//            $POSOrderManager->reject($POSOrder);
//
//            return new JsonResponse([], JsonResponse::HTTP_BAD_REQUEST);
//        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderManager $POSOrderManager
     * @return JsonResponse
     * @throws AppException
     */
    public function sendPOSOrderConfirmationCode(string $workspaceName, int $POSOrderId, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderManager $POSOrderManager) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderManager->getPOSOrderRepository()->findOneBy([
            'id' => $POSOrderId,
            'workspace' => $workspace->getId(),
            'employee' => $employee->getId()
        ]);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('POS order not found');

        $POSOrderManager->sendConfirmationCode($POSOrder);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderManager $POSOrderManager
     * @return JsonResponse
     * @throws AppException
     */
    public function sendPOSOrderConfirmationSMS(string $workspaceName, int $POSOrderId, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderManager $POSOrderManager) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderManager->getPOSOrderRepository()->findOneBy([
            'id' => $POSOrderId,
            'workspace' => $workspace->getId(),
            'employee' => $employee->getId()
        ]);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('POS order not found');

        $POSOrderManager->sendConfirmationSMS($POSOrder);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderManager $POSOrderManager
     * @return JsonResponse
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function rejectPOSOrder(string $workspaceName, int $POSOrderId, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderManager $POSOrderManager) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderManager->getPOSOrderRepository()->findOneBy([
            'id' => $POSOrderId,
            'workspace' => $workspace->getId(),
            'employee' => $employee->getId()
        ]);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('POS order not found');

        $POSOrderManager->reject($POSOrder);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param string $workspaceName
     * @param int $POSOrderId
     * @param Request $request
     * @param WorkspaceEmployeeResolver $workspaceEmployeeResolver
     * @param POSOrderRepository $POSOrderRepository
     * @return JsonResponse
     * @throws AppException
     */
    public function getWorkspaceOrder(string $workspaceName, int $POSOrderId, Request $request, WorkspaceEmployeeResolver $workspaceEmployeeResolver, POSOrderRepository $POSOrderRepository) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        /** @var Employee $employee */
        $employee = $workspaceEmployeeResolver->resolve($workspace, $request);

        /** @var POSOrder $POSOrder */
        $POSOrder = $POSOrderRepository->findOneBy([
            'id' => $POSOrderId,
            'workspace' => $workspace->getId(),
            'employee' => $employee->getId()
        ]);
        if(!($POSOrder instanceof POSOrder)) throw new AppException('POS order not found');

        return new JsonResponse(['POSOrder' => $POSOrder->serializeForPOSApi()], JsonResponse::HTTP_OK);
    }

    /**
     * @param string $workspaceName
     * @param EmployeeRepository $employeeRepository
     * @return JsonResponse
     * @throws AppException
     */
    public function getWorkspaceEmployees(string $workspaceName, EmployeeRepository $employeeRepository) : JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace($workspaceName);

        $employeesSerialized = [];
        $employees = $employeeRepository->findBy(['workspace' => $workspace->getId()]);
        if($employees && count($employees) > 0){
            /** @var Employee $employee */
            foreach ($employees as $employee) {
                $employeesSerialized[] = $employee->serializeBasic();
            }
        }

        return new JsonResponse(['employees' => $employeesSerialized], JsonResponse::HTTP_OK);
    }
}
