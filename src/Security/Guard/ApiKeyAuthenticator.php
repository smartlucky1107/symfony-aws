<?php

namespace App\Security\Guard;

use App\Entity\Configuration\ApiKey;
use App\Entity\User;
use App\Security\Extractor\ApiKeyExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ApiKeyExtractor */
    private $apiKeyExtractor;

    /**
     * ApiKeyAuthenticator constructor.
     * @param EntityManagerInterface $em
     * @param ApiKeyExtractor $apiKeyExtractor
     */
    public function __construct(EntityManagerInterface $em, ApiKeyExtractor $apiKeyExtractor)
    {
        $this->em = $em;
        $this->apiKeyExtractor = $apiKeyExtractor;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return false !== $this->apiKeyExtractor->extract($request);
    }

    /**
     * @param Request $request
     * @return array|mixed
     */
    public function getCredentials(Request $request)
    {
        return [
            'apiKey' => $this->apiKeyExtractor->extract($request),
        ];
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider) : ?UserInterface
    {
        $key = $credentials['apiKey'];
        if (null === $key) return null;

        /** @var ApiKey $apiKey */
        $apiKey = $this->apiKeyExtractor->load($key);
        if($apiKey instanceof ApiKey && $apiKey->isRequestAllowed()){
            return $apiKey->getUser();
        }else{
            return null;
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return JsonResponse|Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = ['message' => 'Authentication Required'];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}