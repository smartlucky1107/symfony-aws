<?php

namespace App\Manager\ListFilter;

use App\Entity\User;
use App\Entity\Wallet\Wallet;
use Symfony\Component\HttpFoundation\Request;

class WalletListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int|null */
    public $userId = null;

    /**
     * WalletListFilter constructor.
     * @param Request $request
     * @param User|null $user
     */
    public function __construct(Request $request, User $user = null)
    {
        parent::__construct($request);
        parent::initSortBy($request, Wallet::DEFAULT_SORT_FIELD, Wallet::ALLOWED_SORT_FIELDS);

        $this->id   = $request->query->get('id', null);
        $this->name = $request->query->get('name', null);

        if($user instanceof User){
            $this->userId = $user->getId();
        }
    }
}