<?php

namespace App\Manager;

use App\Entity\Configuration\VoterRole;
use App\Exception\AppException;
use App\Repository\Configuration\VoterRoleRepository;

class VoterRoleManager
{
    /** @var VoterRoleRepository */
    private $voterRoleRepository;

    /** @var VoterRole */
    private $voterRole;

    /**
     * VoterRoleManager constructor.
     * @param VoterRoleRepository $voterRoleRepository
     */
    public function __construct(VoterRoleRepository $voterRoleRepository)
    {
        $this->voterRoleRepository = $voterRoleRepository;
    }

    /**
     * Load VoterRole to the class by $voterRoleId
     *
     * @param int $voterRoleId
     * @return VoterRole
     * @throws AppException
     */
    public function load(int $voterRoleId) : VoterRole
    {
        $this->voterRole = $this->voterRoleRepository->find($voterRoleId);
        if(!($this->voterRole instanceof VoterRole)) throw new AppException('error.voterRole.not_found');

        return $this->voterRole;
    }

    /**
     * @param VoterRole $voterRole
     * @return VoterRole
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(VoterRole $voterRole) : VoterRole
    {
        $this->voterRole = $this->voterRoleRepository->save($voterRole);

        return $this->voterRole;
    }
}
