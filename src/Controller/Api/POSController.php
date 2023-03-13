<?php

namespace App\Controller\Api;

use App\DataTransformer\EmployeeTransformer;
use App\DataTransformer\WorkspaceTransformer;
use App\Entity\POS\Employee;
use App\Entity\POS\Workspace;
use App\Entity\User;
use App\Exception\AppException;
use App\Manager\POS\EmployeeManager;
use App\Manager\POS\POSGoogleAuthenticatorManager;
use App\Manager\POS\WorkspaceManager;
use App\Repository\POS\EmployeeRepository;
use App\Repository\POS\WorkspaceRepository;
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

class POSController extends FOSRestController
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
     * @return Workspace
     * @throws AppException
     */
    private function getUserWorkspace() : Workspace
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Workspace $workspace */
        $workspace = $user->getWorkspace();
        if(!($workspace instanceof Workspace)) throw new AppException('Workspace not exists');

        return $workspace;
    }

    /**
     * Get information about Workspace Google Authenticator
     *
     * @Rest\Get("/users/me/pos/workspace/gauth")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns secret and QR code url",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="secret",         type="string",  description="Secred code for Google Authenticator", example="7asd687das68"),
     *         @SWG\Property(property="qrUrl",          type="string",  description="QR code url", example="https://chart.googleapis.com/chart?chs=200x200")
     *     )
     * )
     * @SWG\Tag(name="POS")
     *
     * @param POSGoogleAuthenticatorManager $POSGoogleAuthenticatorManager
     * @return View
     * @throws AppException
     */
    public function getWorkspaceGAuth(POSGoogleAuthenticatorManager $POSGoogleAuthenticatorManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        $secret = $POSGoogleAuthenticatorManager->generateSecret();
        $qrUrl = $POSGoogleAuthenticatorManager->generateQrUrl($workspace->getName(), $secret);

        return $this->view(['secret' => $secret, 'qrUrl' => $qrUrl], JsonResponse::HTTP_OK);
    }

    /**
     * Disable Google Authenticator for Workspace
     *
     * @Rest\Patch("/users/me/pos/workspace/gauth-disable")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params for disable",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"g-auth-code"},
     *         @SWG\Property(property="g-auth-code",  type="string",  description="Code from Google Authenticator", example="1234"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Google Authenticator disabled"
     * )
     * @SWG\Tag(name="POS")
     *
     * @param Request $request
     * @param WorkspaceManager $workspaceManager
     * @param POSGoogleAuthenticatorManager $POSGoogleAuthenticatorManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function patchMyGAuthDisable(Request $request, WorkspaceManager $workspaceManager, POSGoogleAuthenticatorManager $POSGoogleAuthenticatorManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();
        if(!$workspace->isGAuthEnabled()) throw new AppException('Workspace is not enabled');

        $POSGoogleAuthenticatorManager->verifyRequest($workspace->getGAuthSecret(), $request);
        $workspaceManager->disableGAuth($workspace);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Enable Google Authenticator for Workspace
     *
     * @Rest\Patch("/users/me/pos/workspace/gauth")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Params for enable",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"g-auth-code", "secret"},
     *         @SWG\Property(property="g-auth-code",    type="string",  description="Code from Google Authenticator", example="1234"),
     *         @SWG\Property(property="secret",         type="string",  description="Secret", example="7das6d8as1"),
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Google Authenticator enabled"
     * )
     * @SWG\Tag(name="POS")
     *
     * @param Request $request
     * @param WorkspaceManager $workspaceManager
     * @param POSGoogleAuthenticatorManager $POSGoogleAuthenticatorManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function patchMyGAuth(Request $request, WorkspaceManager $workspaceManager, POSGoogleAuthenticatorManager $POSGoogleAuthenticatorManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        $secret = (string) $request->request->get('secret', '');

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();
        if($workspace->isGAuthEnabled()) throw new AppException('Workspace is already enabled');

        $POSGoogleAuthenticatorManager->verifyRequest($secret, $request);
        $workspaceManager->enableGAuth($workspace, $secret);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Get information about my workspace
     *
     * @Rest\Get("/users/me/pos/workspace")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Workspace object"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Workspace not found"
     * )
     * @SWG\Tag(name="POS")
     *
     * @return View
     * @throws AppException
     */
    public function getWorkspace() : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var User $user */
        $user = $this->getUser();

        /** @var Workspace $workspace */
        $workspace = $user->getWorkspace();

        if($workspace instanceof Workspace){
            return $this->view(['workspace' => $workspace->serialize()], JsonResponse::HTTP_OK);
        }else{
            return $this->view([], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Get Workspace Employees
     *
     * @Rest\Get("/users/me/pos/workspace/employees")
     *
     * @SWG\Response(
     *     response=200,
     *     description="List of employees in my Workspace"
     * )
     * @SWG\Tag(name="POS")
     *
     * @param EmployeeRepository $employeeRepository
     * @return View
     * @throws AppException
     */
    public function getWorkspaceEmployees(EmployeeRepository $employeeRepository) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        $serialized = [];

        $employees = $employeeRepository->findBy(['workspace' => $workspace->getId()]);
        if($employees){
            /** @var Employee $employee */
            foreach ($employees as $employee){
                $serialized[] = $employee->serialize();
            }
        }

        return $this->view(['employees' => $serialized], JsonResponse::HTTP_OK);
    }

    /**
     * Get Workspace Employee
     *
     * @Rest\Get("/users/me/pos/workspace/employees/{employeeId}", requirements={"employeeId"="\d+"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Employee details"
     * )
     * @SWG\Tag(name="POS")
     *
     * @param int $employeeId
     * @param EmployeeRepository $employeeRepository
     * @return View
     * @throws AppException
     */
    public function getWorkspaceEmployee(int $employeeId, EmployeeRepository $employeeRepository) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        $serialized = [];

        /** @var Employee $employee */
        $employee = $employeeRepository->findOneBy(['workspace' => $workspace->getId(), 'id' => $employeeId]);
        if($employee instanceof Employee){
            $serialized = $employee->serialize();
            return $this->view($serialized, JsonResponse::HTTP_OK);
        }else{
            return $this->view([], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Create new Workspace
     *
     * @Rest\Post("/users/me/pos/workspace")
     *
     * @SWG\Response(
     *     response=201,
     *     description="Serialized Workspace object"
     * )
     * @SWG\Tag(name="POS")
     *
     * @param Request $request
     * @param WorkspaceTransformer $workspaceTransformer
     * @param WorkspaceRepository $workspaceRepository
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function postWorkspace(Request $request, WorkspaceTransformer $workspaceTransformer, WorkspaceRepository $workspaceRepository) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var User $user */
        $user = $this->getUser();
        if(!$user->isTradingEnabled()) throw new AppException('User is not allowed for workspaces');
        if(!$user->isTier3Approved()) throw new AppException('User is not allowed for workspaces');

        /** @var Workspace $workspace */
        $workspace = $workspaceTransformer->transform($user, $request);
        $workspaceTransformer->validate($workspace);

        $workspace = $workspaceRepository->save($workspace);

        return $this->view(['workspace' => $workspace->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * Update PIN for User's Workspace
     *
     * @Rest\Patch("/users/me/pos/workspace/pin")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="New PIN data",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"pin"},
     *         @SWG\Property(property="pin",   type="integer", description="New PIN", example="69696969")
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Pin updated",
     * )
     * @SWG\Tag(name="POS")
     *
     * @param Request $request
     * @param WorkspaceManager $workspaceManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchWorkspacePin(Request $request, WorkspaceManager $workspaceManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        $pin = (int) $request->request->get('pin');

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        $workspaceManager->setPin($workspace, $pin);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Create new Employee for my Workspace
     *
     * @Rest\Post("/users/me/pos/employee")
     *
     * @SWG\Response(
     *     response=201,
     *     description="Serialized Employee object"
     * )
     * @SWG\Tag(name="POS")
     *
     * @param Request $request
     * @param EmployeeTransformer $employeeTransformer
     * @param EmployeeRepository $employeeRepository
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function postEmployee(Request $request, EmployeeTransformer $employeeTransformer, EmployeeRepository $employeeRepository) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        /** @var Employee $employee */
        $employee = $employeeTransformer->transform($workspace, $request);
        $employeeTransformer->validate($employee);

        $employee = $employeeRepository->save($employee);

        return $this->view(['employee' => $employee->serialize()], JsonResponse::HTTP_CREATED);
    }

    /**
     * Update PIN for User's Workspace
     *
     * @Rest\Patch("/users/me/pos/employee/{employeeId}/pin", requirements={"employeeId"="\d+"})
     *
     * @SWG\Parameter( name="employeeId",    in="path", type="integer", description="The id of Employee" )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="New PIN data",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         required={"pin"},
     *         @SWG\Property(property="pin",   type="integer", description="New PIN", example="69696969")
     *     )
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Pin updated",
     * )
     * @SWG\Tag(name="POS")
     *
     * @param Request $request
     * @param int $employeeId
     * @param EmployeeRepository $employeeRepository
     * @param EmployeeManager $employeeManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchEmployeePin(Request $request, int $employeeId, EmployeeRepository $employeeRepository, EmployeeManager $employeeManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        $pin = (int) $request->request->get('pin');

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        /** @var Employee $employee */
        $employee = $employeeRepository->findOneBy(['workspace' => $workspace->getId(), 'id' => $employeeId]);
        if(!($employee instanceof Employee)) throw new AppException('Employee not found');

        $employeeManager->setPin($employee, $pin);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Disable employee
     *
     * @Rest\Patch("/users/me/pos/employee/{employeeId}/disable", requirements={"employeeId"="\d+"})
     *
     * @SWG\Parameter( name="employeeId",    in="path", type="integer", description="The id of Employee" )
     * @SWG\Response(
     *     response=204,
     *     description="Employee disabled",
     * )
     * @SWG\Tag(name="POS")
     *
     * @param int $employeeId
     * @param EmployeeRepository $employeeRepository
     * @param EmployeeManager $employeeManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchEmployeeDisable(int $employeeId, EmployeeRepository $employeeRepository, EmployeeManager $employeeManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        /** @var Employee $employee */
        $employee = $employeeRepository->findOneBy(['workspace' => $workspace->getId(), 'id' => $employeeId]);
        if(!($employee instanceof Employee)) throw new AppException('Employee not found');

        $employeeManager->disable($employee);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
    /**
     * Enable employee
     *
     * @Rest\Patch("/users/me/pos/employee/{employeeId}/enable", requirements={"employeeId"="\d+"})
     *
     * @SWG\Parameter( name="employeeId",    in="path", type="integer", description="The id of Employee" )
     * @SWG\Response(
     *     response=204,
     *     description="Employee enabled",
     * )
     * @SWG\Tag(name="POS")
     *
     * @param int $employeeId
     * @param EmployeeRepository $employeeRepository
     * @param EmployeeManager $employeeManager
     * @return View
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchEmployeeEnable(int $employeeId, EmployeeRepository $employeeRepository, EmployeeManager $employeeManager) : View
    {
        $this->systemTagAccessResolver->authPos();

        /** @var Workspace $workspace */
        $workspace = $this->getUserWorkspace();

        /** @var Employee $employee */
        $employee = $employeeRepository->findOneBy(['workspace' => $workspace->getId(), 'id' => $employeeId]);
        if(!($employee instanceof Employee)) throw new AppException('Employee not found');

        $employeeManager->enable($employee);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }
}
