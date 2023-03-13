<?php

namespace App\DataTransformer;

use App\Entity\Configuration\VoterRole;
use App\Exception\AppException;
use App\Repository\Configuration\VoterRoleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VoterRoleTransformer extends AppTransformer
{
    /** @var VoterRoleRepository */
    private $voterRoleRepository;

    /**
     * VoterRoleTransformer constructor.
     * @param VoterRoleRepository $voterRoleRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(VoterRoleRepository $voterRoleRepository, ValidatorInterface $validator)
    {
        $this->voterRoleRepository = $voterRoleRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param Request $request
     * @return VoterRole
     * @throws AppException
     */
    public function transform(Request $request) : VoterRole
    {
        $module = (string) $request->get('module');
        $action = (string) $request->get('action');

        if($this->voterRoleRepository->voterRoleExists($module, $action)) throw new AppException('Voter role already exists');

        /** @var VoterRole $voterRole */
        $voterRole = new VoterRole($module, $action);

        return $voterRole;
    }
}
