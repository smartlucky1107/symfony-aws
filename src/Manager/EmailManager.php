<?php

namespace App\Manager;

use App\Entity\User;
use App\Exception\AppException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailManager implements EmailInterface
{
    /** @var EngineInterface */
    private $templating;

    /** @var \Swift_Mailer */
    private $mailer;

    /** @var string */
    private $adminEmail;

    /** @var TranslatorInterface */
    private $translator;

    /** @var int */
    private $notificationType;

    /** @var array */
    private $bodyParams = [];

    /**
     * NotificationManager constructor.
     * @param EngineInterface $templating
     * @param \Swift_Mailer $mailer
     * @param string $adminEmail
     * @param TranslatorInterface $translator
     */
    public function __construct(EngineInterface $templating, \Swift_Mailer $mailer, string $adminEmail, TranslatorInterface $translator)
    {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->translator = $translator;
    }

    /**
     * Check if passed $type is allowed for notification
     *
     * @param int $type
     * @return bool
     */
    public function isTypeAllowed(int $type) : bool
    {
        if(isset(self::TYPES[$type])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Initialize notification type
     *
     * @param int $notificationType
     * @return EmailManager
     * @throws AppException
     */
    public function initType(int $notificationType) : EmailManager
    {
        if($this->isTypeAllowed($notificationType)){
            $this->notificationType = $notificationType;
        }else{
            throw new AppException('error.email.notification_type_not_supported');
        }

        return $this;
    }

    /**
     * Set parameters for mail body rendering
     *
     * @param array $params
     * @return EmailManager
     */
    public function setBodyParams(array $params = []) : EmailManager
    {
        $this->bodyParams = $params;

        return $this;
    }

    /**
     * @param string $email
     * @throws \Exception
     */
    public function send(string $email) : void
    {
        $body = $this->templating->render('email/'.self::TYPES[$this->notificationType]['twigName'].'.html.twig', $this->bodyParams);

        if(isset($this->bodyParams['id'])){
            $subject = $this->translator->trans(self::TYPES[$this->notificationType]['title'], ['%id%' => $this->bodyParams['id']], null, 'en');
        }else{
            $subject = $this->translator->trans(self::TYPES[$this->notificationType]['title'], [], null, 'en');
        }

        $message = (new \Swift_Message($subject))
            ->setFrom('support@swapcoin.today', 'swapcoin.today')
            ->setTo($email)
            ->setBody($body, 'text/html')
        ;

        $this->mailer->send($message);
    }
}
