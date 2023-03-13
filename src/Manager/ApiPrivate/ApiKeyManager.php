<?php

namespace App\Manager\ApiPrivate;

use App\Entity\Configuration\ApiKey;
use App\Entity\User;
use App\Exception\AppException;
use App\Repository\Configuration\ApiKeyRepository;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class ApiKeyManager
{
    /** @var ApiKeyRepository */
    private $apiKeyRepository;

    /** @var DocumentManager */
    private $dm;

    /**
     * ApiKeyManager constructor.
     * @param ApiKeyRepository $apiKeyRepository
     * @param DocumentManager $dm
     */
    public function __construct(ApiKeyRepository $apiKeyRepository, DocumentManager $dm)
    {
        $this->apiKeyRepository = $apiKeyRepository;
        $this->dm = $dm;
    }

    /**
     * @param string $key
     * @param User $user
     * @return ApiKey
     * @throws AppException
     */
    public function loadByKeyUser(string $key, User $user) : ApiKey
    {
        /** @var ApiKey $apiKey */
        $apiKey = $this->apiKeyRepository->findOneBy(['key' => $key, 'user' => $user->getId()]);
        if(!($apiKey instanceof ApiKey)) throw new AppException('error.api_key.not_found');

        return $apiKey;
    }

    /**
     * @param User $user
     * @param array $roles
     * @return ApiKey
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generate(User $user, array $roles = []) : ApiKey
    {
        $apiKey = new ApiKey($user, $roles);
        $apiKey = $this->apiKeyRepository->save($apiKey);

        return $apiKey;
    }

    /**
     * @param ApiKey $apiKey
     * @return ApiKey
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deactivate(ApiKey $apiKey) : ApiKey
    {
        if(!$apiKey->isEnabled()) throw new AppException('Api key already disabled');

        $apiKey->setEnabled(false);
        $apiKey->setDisabledAt(new \DateTime('now'));
        $apiKey = $this->apiKeyRepository->save($apiKey);

        return $apiKey;
    }
}
