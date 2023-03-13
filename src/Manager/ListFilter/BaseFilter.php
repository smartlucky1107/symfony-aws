<?php

namespace App\Manager\ListFilter;

use Symfony\Component\HttpFoundation\Request;

class BaseFilter implements ListFilterInterface
{
    /** @var int */
    public $page;

    /** @var int */
    public $pageSize;

    /** @var int */
    public $sortType;

    /** @var string */
    public $sortBy;

    /**
     * PaginationModel constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->page         = (int) $request->query->get('page', 1);
        if($this->page < 1) $this->page = 1;
        $this->pageSize     = (int) $request->query->get('pageSize', self::DEFAULT_PAGE_SIZE);
        $this->sortType     = (bool) $request->query->get('sortType', self::DEFAULT_SORT_TYPE) ? 'ASC': 'DESC';
    }

    /**
     * @param Request $request
     * @param string $defaultField
     * @param array $allowedFields
     */
    public function initSortBy(Request $request, string $defaultField, array $allowedFields){
        $sortBy = $request->query->get('sortBy');

        if(is_null($sortBy)){
            $this->sortBy = $defaultField;
        }elseif(array_key_exists($sortBy, $allowedFields)){
            $this->sortBy = $allowedFields[$sortBy];
        }else{
            throw new \InvalidArgumentException('Sorting field is not allowed');
        }
    }
}
