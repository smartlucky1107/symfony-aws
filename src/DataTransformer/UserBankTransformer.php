<?php

namespace App\DataTransformer;

use App\Entity\UserBank;
use App\Entity\User;
use App\Exception\AppException;
use App\Repository\UserBankRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserBankTransformer extends AppTransformer
{
    /** @var UserBankRepository */
    private $userBankRepository;

    /**
     * UserBankTransformer constructor.
     * @param UserBankRepository $userBankRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(UserBankRepository $userBankRepository, ValidatorInterface $validator)
    {
        $this->userBankRepository = $userBankRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param User $user
     * @param Request $request
     * @return UserBank
     * @throws AppException
     * @throws \Exception
     */
    public function transform(User $user, Request $request) : UserBank
    {
        $iban = (string) $request->get('iban', '');
        if(empty($iban)) throw new AppException('Iban is required');

        $swift = (string) $request->get('swift', '');
        if(empty($swift)) throw new AppException('Swift is required');

        /** @var UserBank $userBank */
        $userBank = $this->userBankRepository->findOneBy(['iban' => $iban, 'swift' => $swift]);
        if($userBank instanceof UserBank) throw new AppException('Bank account already exists');

        /** @var UserBank $userBank */
        $userBank = new UserBank($user, $iban, $swift);

        return $userBank;
    }
}