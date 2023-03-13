<?php

namespace App\Manager\ListFilter;

use App\Entity\POS\Employee;
use Symfony\Component\HttpFoundation\Request;

class EmployeeListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $workspaceId;

    /** @var string */
    public $firstName;

    /** @var string */
    public $lastName;

    /**
     * EmployeeListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, Employee::DEFAULT_SORT_FIELD, Employee::ALLOWED_SORT_FIELDS);

        $this->id           = $request->query->get('id', null);
        $this->workspaceId  = $request->query->get('workspaceId', null);
        $this->firstName    = $request->query->get('firstName', null);
        $this->lastName     = $request->query->get('lastName', null);
    }
}
