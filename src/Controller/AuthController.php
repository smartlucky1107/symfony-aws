<?php

namespace App\Controller;

use App\DataTransformer\UserTransformer;
use App\Document\NotificationInterface;
use App\Entity\Configuration\SystemTag;
use App\Entity\POS\Workspace;
use App\Exception\AppException;
use App\Manager\NotificationManager;
use App\Manager\reCaptchaManager;
use App\Manager\SMS\SerwerSMSManager;
use App\Manager\UserManager;
use App\Repository\Configuration\SystemTagRepository;
use App\Repository\POS\WorkspaceRepository;
use App\Repository\UserRepository;
use App\Security\SystemTagAccessResolver;
use App\Security\TokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthController extends AbstractController
{
    /** @var SystemTagAccessResolver */
    private $systemTagAccessResolver;

    /**
     * AuthController constructor.
     * @param SystemTagAccessResolver $systemTagAccessResolver
     */
    public function __construct(SystemTagAccessResolver $systemTagAccessResolver)
    {
        $this->systemTagAccessResolver = $systemTagAccessResolver;
    }

    /**
     * @Route("/pre-auth-pos", name="pre_auth_pos", methods={"POST"})
     *
     * @param Request $request
     * @param WorkspaceRepository $workspaceRepository
     * @return JsonResponse
     */
    public function preAuthWorkspace(Request $request, WorkspaceRepository $workspaceRepository) : JsonResponse
    {
        try{
            $name = (string) $request->request->get('name', '');
            if(empty($name)) throw new AppException('Name is required');

            /** @var Workspace $workspace */
            $workspace = $workspaceRepository->findOneBy(['name' => $name]);
            if(!($workspace instanceof Workspace)) throw new AppException('Workspace not found');

            return new JsonResponse(['isGAuth' => ($workspace->isGAuthEnabled()) ? true : false], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['isGAuth' => false], Response::HTTP_OK);
        }
    }

    /**
     * @Route("/pre-auth", name="pre_auth", methods={"POST"})
     *
     * @param Request $request
     * @param UserManager $userManager
     * @return JsonResponse
     */
    public function preAuth(Request $request, UserManager $userManager) : JsonResponse
    {
        try{
            $email = $request->request->get('email', '');

            /** @var User $user */
            $user = $userManager->loadByEmail($email);

            $isGAuth = false;
            if($user->isGAuthEnabled()){
                $isGAuth = true;
            }

            return new JsonResponse(['isGAuth' => $isGAuth], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['isGAuth' => false], Response::HTTP_OK);
        }
    }

    /**
     * @Route("/config", name="config", methods={"GET"})
     *
     * @param SystemTagRepository $systemTagRepository
     * @return JsonResponse
     */
    public function config(SystemTagRepository $systemTagRepository) : JsonResponse
    {
        $result = [];

        try{
            $systemTags = $systemTagRepository->findActivated();
            if(is_array($systemTags) && count($systemTags) > 0){
                /** @var SystemTag $systemTag */
                foreach ($systemTags as $systemTag){
                    $result[] = $systemTag->getId();
                }
            }
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse($result, Response::HTTP_OK);
        }
    }

    /**
     * @Route("/logout", name="logout", methods={"POST"})
     *
     * @param Request $request
     * @param TokenManager $tokenManager
     * @return JsonResponse
     */
    public function logout(Request $request, TokenManager $tokenManager) : JsonResponse
    {
        try{
            $token = (string) $request->request->get('token');

            // TODO przerobić na SQL bo na redis strasznie długo działą jak jest dużo zapomnianych tokneów
            // $tokenManager->revokeToken($token);
            //

            return new JsonResponse(['ok'], Response::HTTP_CREATED);
        } catch (\Exception $exception){
            return new JsonResponse(json_decode($exception->getMessage()),Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     *
     * @param Request $request
     * @param UserTransformer $userTransformer
     * @param reCaptchaManager $reCaptchaManager
     * @param UserManager $userManager
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    public function register(Request $request, UserTransformer $userTransformer, reCaptchaManager $reCaptchaManager, UserManager $userManager, TranslatorInterface $translator) : JsonResponse
    {
        try{
            $this->systemTagAccessResolver->authRegister();

            $reCaptchaManager->verifyRequest($request);

            /** @var User $user */
            $user = $userTransformer->transform($request);
            $userTransformer->validateForm($user);

            $user = $userManager->register($user);

            return new JsonResponse(['user' => $user->serializeBasic()], Response::HTTP_CREATED);
        } catch (\Exception $exception){
            if($this->isJson($exception->getMessage())){
                return new JsonResponse(json_decode($exception->getMessage()),Response::HTTP_BAD_REQUEST);
            }else{
                return new JsonResponse(['message' => $translator->trans($exception->getMessage())],Response::HTTP_BAD_REQUEST);
            }
        }
    }

    /**
     * @Route("/resend-confirmation", name="resend_confirmation", methods={"POST"})
     *
     * @param Request $request
     * @param UserManager $userManager
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    public function resendConfirmation(Request $request, UserManager $userManager, TranslatorInterface $translator) : JsonResponse
    {
        try{
            $email = $request->request->get('email', '');

            /** @var User $user */
            $user = $userManager->loadByEmail($email);
            $userManager->resendConfirmation($user);

            return new JsonResponse(['message' => 'ok'], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['message' => $translator->trans($exception->getMessage())],Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/resetting/confirm", name="resetting_confirm", methods={"POST"})
     *
     * @param Request $request
     * @param UserManager $userManager
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    public function resettingConfirm(Request $request, UserManager $userManager, TranslatorInterface $translator){
        try{
            $this->systemTagAccessResolver->authPasswordResetting();

            $email = (string) $request->get('email', '');
            $confirmationToken = (string) $request->get('confirmationToken', '');
            $password = (string) $request->get('password', '');

            if(empty($email)) throw new AppException('email cannot be empty');
            if(empty($confirmationToken)) throw new AppException('confirmationToken cannot be empty');
            if(empty($password)) throw new AppException('password cannot be empty');

            /** @var User $user */
            $user = $userManager->loadByEmailConfirmationToken($email, $confirmationToken);
            $userManager->resetPassword($user, $password);

            return new JsonResponse(['ok'], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['message' => $translator->trans($exception->getMessage())],Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/resetting/request", name="resetting_request", methods={"POST"})
     *
     * @param Request $request
     * @param UserManager $userManager
     * @param reCaptchaManager $reCaptchaManager
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    public function resettingRequest(Request $request, UserManager $userManager, reCaptchaManager $reCaptchaManager, TranslatorInterface $translator){
        try{
            $this->systemTagAccessResolver->authPasswordResetting();

            $reCaptchaManager->verifyRequest($request);

            $email = (string) $request->request->get('email', '');

            /** @var User $user */
            $user = $userManager->loadByEmail($email);
            $userManager->requestPassword($user);

            return new JsonResponse(['ok'], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['message' => $translator->trans($exception->getMessage())],Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/user/confirm", name="user_confirm", methods={"POST"})
     *
     * @param Request $request
     * @param UserManager $userManager
     * @param TranslatorInterface $translator
     * @return JsonResponse
     */
    public function userConfirm(Request $request, UserManager $userManager, TranslatorInterface $translator) : JsonResponse
    {
        try{
            $email = (string) $request->request->get('email', '');
            $confirmationToken = (string) $request->request->get('confirmationToken', '');

            /** @var User $user */
            $user = $userManager->loadByEmailConfirmationToken($email, $confirmationToken);

            /** @var User $user */
            $user = $userManager->confirmEmail($user, $email);

            return new JsonResponse(['user' => $user->serializeBasic()], Response::HTTP_OK);
        } catch (\Exception $exception){
            return new JsonResponse(['message' => $translator->trans($exception->getMessage())],Response::HTTP_BAD_REQUEST);
        }
    }

########################################################
#######             Private methods              #######
########################################################

    private function isJson($string) {
        $decoded = json_decode($string); // decode our JSON string
        if ( !is_object($decoded) && !is_array($decoded) ) {
            /*
            If our string doesn't produce an object or array
            it's invalid, so we should return false
            */
            return false;
        }
        /*
        If the following line resolves to true, then there was
        no error and our JSON is valid, so we return true.
        Otherwise it isn't, so we return false.
        */
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
