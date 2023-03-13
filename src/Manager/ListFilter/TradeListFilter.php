<?php

namespace App\Manager\ListFilter;

use App\Entity\OrderBook\Trade;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class TradeListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $userId = null;

    /** @var int */
    public $type = null;

    /** @var int|null */
    public $orderBuyId = null;

    /** @var int|null */
    public $orderSellId = null;

    /** @var int|null */
    public $currencyPairId = null;

    /** @var \DateTime|null */
    public $from = null;

    /** @var \DateTime|null */
    public $to = null;

    /**
     * TradeListFilter constructor.
     * @param Request $request
     * @param User|null $user
     */
    public function __construct(Request $request, User $user = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, Trade::DEFAULT_SORT_FIELD, Trade::ALLOWED_SORT_FIELDS);

        $this->id               = $request->query->get('id', null);

        if($user instanceof User){
            $this->userId = $user->getId();
        }

        $this->type             = $request->query->get('type', null);

        $this->orderBuyId       = $request->query->get('orderBuyId', null);
        $this->orderSellId      = $request->query->get('orderSellId', null);
        $this->currencyPairId   = $request->query->get('currencyPairId', null);

        try{
            if($request->query->has('from')){
                $this->from = new \DateTime($request->query->get('from'));
            }
            if($request->query->has('to')){
                $this->to = new \DateTime($request->query->get('to'));
            }
        }catch (\Exception $exception){
        }
    }
}
