<?php

namespace App\Manager\ListFilter;

use App\Entity\Wallet\Deposit;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use Symfony\Component\HttpFoundation\Request;

class DepositListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $status;

    /** @var int|null */
    public $walletId = null;

    /** @var bool */
    public $excludeUser;

    /** @var int */
    public $excludedUserId = null;

    /** @var string */
    public $bankTransaction;

    /**
     * DepositListFilter constructor.
     * @param Request $request
     * @param User $excludeUser
     * @param Wallet|null $wallet
     */
    public function __construct(Request $request, User $excludeUser, Wallet $wallet = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, Deposit::DEFAULT_SORT_FIELD, Deposit::ALLOWED_SORT_FIELDS);

        $this->id               = $request->query->get('id', null);
        $this->status           = $request->query->get('status', null);
        $this->walletId         = $request->query->get('walletId', null);

        $this->excludeUser      = (bool) $request->query->get('excludeUser', null);
        if($this->excludeUser){
            $this->excludedUserId   = $excludeUser->getId();
        }

        $this->bankTransaction  = $request->query->get('bankTransaction', null);

        if($wallet instanceof Wallet){
            $this->walletId = $wallet->getId();
        }
    }
}
