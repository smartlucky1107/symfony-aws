<?php

namespace App\Manager\ListFilter;

use App\Entity\Configuration\SystemTag;
use Symfony\Component\HttpFoundation\Request;

class SystemTagListFilter extends BaseFilter
{
    /** @var string */
    public $type;

    /**s
     * SystemTagListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, SystemTag::DEFAULT_SORT_FIELD, SystemTag::ALLOWED_SORT_FIELDS);

        $this->type   = $request->query->get('type', null);
    }
}