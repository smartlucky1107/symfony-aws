<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserBank;
use App\Repository\UserBankRepository;
use App\Repository\UserRepository;

class UserBankManager
{
    /** @var UserRepository */
    private $userRepository;

    /** @var UserBankRepository */
    private $userBankRepository;

    /**
     * UserBankManager constructor.
     * @param UserRepository $userRepository
     * @param UserBankRepository $userBankRepository
     */
    public function __construct(UserRepository $userRepository, UserBankRepository $userBankRepository)
    {
        $this->userRepository = $userRepository;
        $this->userBankRepository = $userBankRepository;
    }

    /**
     * @param UserBank $userBank
     * @return UserBank
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(UserBank $userBank) : UserBank
    {
        return $this->userBankRepository->save($userBank);
    }
}