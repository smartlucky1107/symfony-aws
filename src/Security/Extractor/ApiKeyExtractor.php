<?php

namespace App\Security\Extractor;

use App\Entity\Configuration\ApiKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiKeyExtractor
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var string */
    private $authHeaderName = 'Authorization';

    /** @var string */
    private $authHeaderPrefix = 'apiKey';

    /**
     * ApiKeyExtractor constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Request $request
     * @return bool|string|string[]|null
     */
    public function extract(Request $request)
    {
        if (!$request->headers->has($this->authHeaderName)) {
            return false;
        }

        $authorizationHeader = $request->headers->get($this->authHeaderName);

        if (empty($this->authHeaderPrefix)) {
            return $authorizationHeader;
        }

        $headerParts = explode(' ', $authorizationHeader);

        if (!(2 === count($headerParts) && 0 === strcasecmp($headerParts[0], $this->authHeaderPrefix))) {
            return false;
        }

        return $headerParts[1];
    }

    /**
     * @param string $key
     * @return ApiKey|null
     */
    public function load(string $key) : ?ApiKey
    {
        /** @var ApiKey $apiKey */
        $apiKey = $this->em->getRepository(ApiKey::class)->findOneBy(['key' => $key]);
        if($apiKey instanceof ApiKey){
            return $apiKey;
        }

        return null;
    }
}