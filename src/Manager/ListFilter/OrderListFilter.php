<?php

namespace App\Manager\ListFilter;

use App\Entity\OrderBook\Order;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class OrderListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $type;

    /** @var int */
    public $status;

    /** @var bool */
    public $isFilled;

    /** @var int */
    public $userId = null;

    /** @var int */
    public $currencyPairId;

    /**
     * OrderListFilter constructor.
     * @param Request $request
     * @param User|null $user
     */
    public function __construct(Request $request, User $user = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, Order::DEFAULT_SORT_FIELD, Order::ALLOWED_SORT_FIELDS);

        $this->id       = $request->query->get('id', null);
        $this->type     = $request->query->get('type', null);
        $this->status   = $request->query->get('status', null);
        $this->isFilled = $request->query->get('isFilled', null);

        if($user instanceof User){
            $this->userId = $user->getId();
        }

        $this->currencyPairId   = $request->query->get('currencyPairId', null);
    }
}