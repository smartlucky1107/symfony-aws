<?php

namespace App\Manager\ListFilter;

use App\Entity\POS\POSOrder;
use App\Entity\POS\Workspace;
use Symfony\Component\HttpFoundation\Request;

class POSOrderListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $status;

    /** @var int */
    public $workspaceId = null;

    /** @var int */
    public $currencyPairId;

    /**
     * POSOrderListFilter constructor.
     * @param Request $request
     * @param Workspace|null $workspace
     */
    public function __construct(Request $request, Workspace $workspace = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, POSOrder::DEFAULT_SORT_FIELD, POSOrder::ALLOWED_SORT_FIELDS);

        $this->id       = $request->query->get('id', null);
        $this->status   = $request->query->get('status', null);

        if($workspace instanceof Workspace){
            $this->workspaceId = $workspace->getId();
        }

        $this->currencyPairId   = $request->query->get('currencyPairId', null);
    }
}
