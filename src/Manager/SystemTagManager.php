<?php

namespace App\Manager;

use App\Entity\Configuration\SystemTag;
use App\Exception\AppException;
use App\Repository\Configuration\SystemTagRepository;

class SystemTagManager
{
    /** @var SystemTagRepository */
    private $systemTagRepository;

    /** @var SystemTag */
    private $systemTag;

    /**
     * SystemTagManager constructor.
     * @param SystemTagRepository $systemTagRepository
     */
    public function __construct(SystemTagRepository $systemTagRepository)
    {
        $this->systemTagRepository = $systemTagRepository;
    }

    /**
     * Load SystemTag to the class by $systemTagId
     *
     * @param int $systemTagId
     * @return SystemTag
     * @throws AppException
     */
    public function load(int $systemTagId) : SystemTag
    {
        $this->systemTag = $this->systemTagRepository->find($systemTagId);
        if(!($this->systemTag instanceof SystemTag)) throw new AppException('error.systemTag.not_found');

        return $this->systemTag;
    }

    /**
     * @return SystemTag
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function toggle() : SystemTag
    {
        if($this->systemTag->isActivated()){
            $this->systemTag->setActivated(false);
        }else{
            $this->systemTag->setActivated(true);
        }

        $this->systemTag = $this->systemTagRepository->save($this->systemTag);

        return $this->systemTag;
    }
}
