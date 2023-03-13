<?php

namespace App\Manager\POS;

use App\Entity\POS\Employee;
use App\Repository\POS\EmployeeRepository;

class EmployeeManager
{
    /** @var EmployeeRepository */
    private $employeeRepository;

    /**
     * EmployeeManager constructor.
     * @param EmployeeRepository $employeeRepository
     */
    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * @param Employee $employee
     * @param int $pin
     * @return Employee
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setPin(Employee $employee, int $pin) : Employee
    {
        $employee->setPin($pin);

        return $this->employeeRepository->save($employee);
    }

    /**
     * @param Employee $employee
     * @return Employee
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disable(Employee $employee) : Employee
    {
        $employee->setEnabled(false);

        return $this->employeeRepository->save($employee);
    }

    /**
     * @param Employee $employee
     * @return Employee
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enable(Employee $employee) : Employee
    {
        $employee->setEnabled(true);

        return $this->employeeRepository->save($employee);
    }
}
