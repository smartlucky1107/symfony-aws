<?php

namespace App\Manager\ListFilter;

use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\Withdrawal;
use Symfony\Component\HttpFoundation\Request;

class WithdrawalListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $status;

    /** @var string */
    public $address;

    /** @var int|null */
    public $walletId = null;

    /**
     * WithdrawalListFilter constructor.
     * @param Request $request
     * @param Wallet|null $wallet
     */
    public function __construct(Request $request, Wallet $wallet = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, Withdrawal::DEFAULT_SORT_FIELD, Withdrawal::ALLOWED_SORT_FIELDS);

        $this->id        = $request->query->get('id', null);
        $this->status    = $request->query->get('status', null);
        $this->address   = $request->query->get('address', null);

        if($wallet instanceof Wallet){
            $this->walletId = $wallet->getId();
        }
    }
}