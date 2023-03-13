<?php

namespace App\Manager\POS;

use App\Entity\POS\Workspace;
use App\Exception\AppException;
use App\Repository\POS\WorkspaceRepository;

class WorkspaceManager
{
    /** @var WorkspaceRepository */
    private $workspaceRepository;

    /**
     * WorkspaceManager constructor.
     * @param WorkspaceRepository $workspaceRepository
     */
    public function __construct(WorkspaceRepository $workspaceRepository)
    {
        $this->workspaceRepository = $workspaceRepository;
    }

    /**
     * @param Workspace $workspace
     * @param bool|null $verified
     * @return Workspace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function toggleVerified(Workspace $workspace, bool $verified = null) : Workspace
    {
        if(is_null($verified)){
            $workspace->setVerified($verified);
        }else{
            if($workspace->isVerified()){
                $workspace->setVerified(false);
            }else{
                $workspace->setVerified(true);
            }
        }

        return $this->workspaceRepository->save($workspace);
    }

    /**
     * @param Workspace $workspace
     * @param int $pin
     * @return Workspace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setPin(Workspace $workspace, int $pin) : Workspace
    {
        $workspace->setPin($pin);

        return $this->workspaceRepository->save($workspace);
    }

    /**
     * @param Workspace $workspace
     * @param string $secret
     * @return Workspace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function enableGAuth(Workspace $workspace, string $secret) : Workspace
    {
        $workspace->setGAuthEnabled(true);
        $workspace->setGAuthSecret($secret);

        return $this->workspaceRepository->save($workspace);
    }

    /**
     * @param Workspace $workspace
     * @return Workspace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableGAuth(Workspace $workspace) : Workspace
    {
        $workspace->setGAuthEnabled(false);
        $workspace->setGAuthSecret(null);

        return $this->workspaceRepository->save($workspace);
    }
}
