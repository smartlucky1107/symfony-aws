<?php

namespace App\Manager\ListFilter;

use App\Entity\Wallet\Wallet;
use App\Entity\Wallet\InternalTransfer;
use Symfony\Component\HttpFoundation\Request;

class InternalTransferListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var int */
    public $status;

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
        parent::initSortBy($request, InternalTransfer::DEFAULT_SORT_FIELD, InternalTransfer::ALLOWED_SORT_FIELDS);

        $this->id        = $request->query->get('id', null);
        $this->status    = $request->query->get('status', null);

        if($wallet instanceof Wallet){
            $this->walletId = $wallet->getId();
        }
    }
}
