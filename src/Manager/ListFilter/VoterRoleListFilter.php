<?php

namespace App\Manager\ListFilter;

use App\Entity\Configuration\VoterRole;
use Symfony\Component\HttpFoundation\Request;

class VoterRoleListFilter extends BaseFilter
{
    /** @var string */
    public $module;

    /** @var string */
    public $action;

    /**
     * VoterRoleListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, VoterRole::DEFAULT_SORT_FIELD, VoterRole::ALLOWED_SORT_FIELDS);

        $this->module   = $request->query->get('module', null);
        $this->action   = $request->query->get('action', null);
    }
}