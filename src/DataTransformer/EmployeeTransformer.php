<?php

namespace App\DataTransformer;

use App\Entity\POS\Employee;
use App\Entity\POS\Workspace;
use App\Entity\User;
use App\Exception\AppException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmployeeTransformer extends AppTransformer
{
    /**
     * EmployeeTransformer constructor.
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param Workspace $workspace
     * @param Request $request
     * @return Employee
     * @throws AppException
     */
    public function transform(Workspace $workspace, Request $request) : Employee
    {
        $firstName = (string) $request->request->get('firstName', '');
        if(empty($firstName)) throw new AppException('First name is required');

        $lastName = (string) $request->request->get('lastName', '');
        if(empty($lastName)) throw new AppException('Last name is required');

        $pin = (int) $request->request->get('pin', 0);

        /** @var Employee $employee */
        $employee = new Employee($workspace, $firstName, $lastName, $pin);

        return $employee;
    }
}
