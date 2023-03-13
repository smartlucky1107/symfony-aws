<?php

namespace App\Manager\ListFilter;

use App\Entity\CurrencyPair;
use Symfony\Component\HttpFoundation\Request;

class CurrencyPairListFilter extends BaseFilter
{
    /**
     * CurrencyListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, CurrencyPair::DEFAULT_SORT_FIELD, CurrencyPair::ALLOWED_SORT_FIELDS);
    }
}