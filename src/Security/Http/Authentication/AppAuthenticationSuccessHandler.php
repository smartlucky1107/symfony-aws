<?php

namespace App\Security\Http\Authentication;

use App\Entity\User;
use App\Event\LoginEvent;
use App\Manager\GoogleAuthenticatorManager;
use App\Manager\reCaptchaManager;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;


class AppAuthenticationSuccessHandler extends AuthenticationSuccessHandler
{
    /** @var reCaptchaManager */
    private $reCaptchaManager;

    /** @var GoogleAuthenticatorManager */
    private $googleAuthenticatorManager;

    /**
     * @param reCaptchaManager $reCaptchaManager
     */
    public function setReCaptchaManager(reCaptchaManager $reCaptchaManager): void
    {
        $this->reCaptchaManager = $reCaptchaManager;
    }

    /**
     * @return GoogleAuthenticatorManager
     */
    public function getGoogleAuthenticatorManager(): GoogleAuthenticatorManager
    {
        return $this->googleAuthenticatorManager;
    }

    /**
     * @param GoogleAuthenticatorManager $googleAuthenticatorManager
     */
    public function setGoogleAuthenticatorManager(GoogleAuthenticatorManager $googleAuthenticatorManager): void
    {
        $this->googleAuthenticatorManager = $googleAuthenticatorManager;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return \Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse|JsonResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        try{
            $this->reCaptchaManager->verifyRequest($request);

            /** @var User $user */
            $user = $token->getUser();
            if($user->isGAuthEnabled()){
                $this->googleAuthenticatorManager->verifyRequest($user->getGAuthSecret(), $request);
            }
        }catch (\Exception $exception){
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return parent::onAuthenticationSuccess($request, $token);
    }

    public function handleAuthenticationSuccess(UserInterface $user, $jwt = null)
    {
        $this->dispatcher->dispatch(LoginEvent::NAME, new LoginEvent($user));

        return parent::handleAuthenticationSuccess($user, $jwt);
    }
}
