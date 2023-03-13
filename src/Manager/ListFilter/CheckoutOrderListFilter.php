<?php

namespace App\Manager\ListFilter;

use App\Entity\CheckoutOrder;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class CheckoutOrderListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $type;

    /** @var int */
    public $status;

    /** @var int */
    public $userId = null;

    /** @var int */
    public $currencyPairId;

    /** @var \DateTime|null */
    public $from = null;

    /** @var \DateTime|null */
    public $to = null;

    /**
     * CheckoutOrderListFilter constructor.
     * @param Request $request
     * @param User|null $user
     */
    public function __construct(Request $request, User $user = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, CheckoutOrder::DEFAULT_SORT_FIELD, CheckoutOrder::ALLOWED_SORT_FIELDS);

        $this->id       = $request->query->get('id', null);
        $this->type     = $request->query->get('type', null);
        $this->status   = $request->query->get('status', null);

        if($user instanceof User){
            $this->userId = $user->getId();
        }

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
