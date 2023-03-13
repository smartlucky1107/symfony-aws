<?php

namespace App\Manager\ListFilter;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class UserListFilter extends BaseFilter
{
    /** @var int */
    public $id;

    /** @var string */
    public $email;

    /** @var string */
    public $firstName;

    /** @var string */
    public $lastName;

    /** @var bool */
    public $isFilesSent;

    /** @var int */
    public $verificationStatus;

    /**
     * UserListFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        parent::initSortBy($request, User::DEFAULT_SORT_FIELD, User::ALLOWED_SORT_FIELDS);

        $this->id       = $request->query->get('id', null);
        $this->email    = $request->query->get('email', null);
        $this->firstName = $request->query->get('firstName', null);
        $this->lastName = $request->query->get('lastName', null);
        $this->isFilesSent = $request->query->get('isFilesSent', null);
        $this->verificationStatus = $request->query->get('verificationStatus', null);
    }
}