<?php

namespace App\Manager\ListManager;

use App\Entity\User;
use App\Exception\AppException;
use App\Manager\ListFilter\BaseFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

class ListManager
{
    /** @var BaseFilter */
    protected $filter;

    /** @var ServiceEntityRepositoryInterface */
    protected $repository;

    /**
     * Initialize the list manager
     *
     * @param BaseFilter $filter
     * @param ServiceEntityRepositoryInterface $repository
     * @return $this
     */
    public function init(BaseFilter $filter, ServiceEntityRepositoryInterface $repository)
    {
        $this->filter = $filter;
        $this->repository = $repository;

        return $this;
    }

    /**
     * Load and return the paginated list as a Paginator object
     *
     * @param bool $isForPrivateApi
     * @param User|null $user
     * @return Paginator
     * @throws AppException
     */
    public function load($isForPrivateApi = false, User $user = null) : Paginator
    {
        if(method_exists($this->repository, 'getPaginatedList')){
            /** @var Paginator $paginator */
            $paginator = $this->repository->{'getPaginatedList'}($this->filter);
            $paginator->serializeResult($isForPrivateApi, $user);

            return $paginator;
        }else{
            throw new AppException('Cannot load paginated list');
        }
    }
}
