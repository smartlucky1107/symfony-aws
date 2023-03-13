<?php

namespace App\Resolver\POS;

use App\Entity\POS\Employee;
use App\Entity\POS\Workspace;
use App\Exception\AppException;
use App\Repository\POS\EmployeeRepository;
use Symfony\Component\HttpFoundation\Request;

class WorkspaceEmployeeResolver
{
    /** @var EmployeeRepository */
    private $employeeRepository;

    /**
     * WorkspaceEmployeeResolver constructor.
     * @param EmployeeRepository $employeeRepository
     */
    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * @param Workspace $workspace
     * @param Request $request
     * @return Employee
     * @throws AppException
     */
    public function resolve(Workspace $workspace, Request $request) : Employee
    {
        if(!$request->headers->has('auth-employee')) throw new AppException('Employee is required');
        if(!$request->headers->has('auth-employee-pin')) throw new AppException('Employee PIN is required');

        $employeeId = (int) $request->headers->get('auth-employee');
        if(!$employeeId) throw new AppException('Employee is required');

        $employeePin = (int) $request->headers->get('auth-employee-pin');
        if(!$employeePin) throw new AppException('Employee PIN is required');

//        if(!$request->request->has('employee')) throw new AppException('Employee is required');
//        if(!$request->request->has('employeePin')) throw new AppException('Employee PIN is required');

//        $employeeId = (int) $request->request->get('employee');
//        if(!$employeeId) throw new AppException('Employee is required');
//
//        $employeePin = (int) $request->request->get('employeePin');
//        if(!$employeePin) throw new AppException('Employee PIN is required');

        /** @var Employee $employee */
        $employee = $this->employeeRepository->findOneBy(['workspace' => $workspace->getId(), 'id' => $employeeId, 'pin' => $employeePin]);
        if(!($employee instanceof Employee)) throw new AppException('Employee not found');
        if(!$employee->isEnabled()) throw new AppException('Employee not found');

        return $employee;
    }
}
