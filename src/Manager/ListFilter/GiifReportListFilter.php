<?php

namespace App\Manager\ListFilter;

use App\Entity\GiifReport;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class GiifReportListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $userId = null;

    /**
     * GiifReportListFilter constructor.
     * @param Request $request
     * @param User|null $user
     */
    public function __construct(Request $request, User $user = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, GiifReport::DEFAULT_SORT_FIELD, GiifReport::ALLOWED_SORT_FIELDS);

        $this->id       = $request->query->get('id', null);

        if($user instanceof User){
            $this->userId = $user->getId();
        }
    }
}
