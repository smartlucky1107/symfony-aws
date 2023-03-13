<?php

namespace App\Manager\ListFilter;

use Symfony\Component\HttpFoundation\Request;

interface ListFilterInterface
{
    const DEFAULT_PAGE_SIZE = 10;
    const DEFAULT_SORT_TYPE = 0;    // 0 - DESC | 1 - ASC

    /**
     * @param Request $request
     * @param string $defaultField
     * @param array $allowedFields
     */
    public function initSortBy(Request $request, string $defaultField, array $allowedFields);
}