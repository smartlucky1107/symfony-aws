<?php

namespace App\Manager\ListFilter;

use App\Entity\POS\Workspace;
use Symfony\Component\HttpFoundation\Request;

class WorkspaceListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $userId;

    /**
     * WorkspaceListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, Workspace::DEFAULT_SORT_FIELD, Workspace::ALLOWED_SORT_FIELDS);

        $this->id       = $request->query->get('id', null);
        $this->name     = $request->query->get('name', null);
        $this->userId     = $request->query->get('userId', null);
    }
}
