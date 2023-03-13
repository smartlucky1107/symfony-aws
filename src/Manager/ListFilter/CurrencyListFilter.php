<?php

namespace App\Manager\ListFilter;

use App\Entity\Currency;
use Symfony\Component\HttpFoundation\Request;

class CurrencyListFilter extends BaseFilter
{
    /** @var string */
    public $fullName;

    /** @var string */
    public $shortName;

    /**
     * CurrencyListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, Currency::DEFAULT_SORT_FIELD, Currency::ALLOWED_SORT_FIELDS);

        $this->fullName   = $request->query->get('fullName', null);
        $this->shortName   = $request->query->get('shortName', null);
    }
}