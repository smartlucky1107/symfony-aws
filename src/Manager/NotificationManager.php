<?php

namespace App\Manager;

use App\Document\Notification;
use App\Entity\Wallet\Deposit;
use App\Entity\OrderBook\Order;
use App\Entity\OrderBook\Trade;
use App\Entity\User;
use App\Entity\Wallet\Withdrawal;
use App\Exception\AppException;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationManager
{
    /** @var DocumentManager */
    private $dm;

    /** @var TranslatorInterface */
    private $translator;

    /** @var RedisSubscribeManager  */
    private $redisSubscribeManager;

    /** @var EmailManager */
    private $emailManager;

    /** @var string */
    private $locale = null;

    /**
     * NotificationManager constructor.
     * @param DocumentManager $dm
     * @param TranslatorInterface $translator
     * @param RedisSubscribeManager $redisSubscribeManager
     * @param EmailManager $emailManager
     */
    public function __construct(DocumentManager $dm, TranslatorInterface $translator, RedisSubscribeManager $redisSubscribeManager, EmailManager $emailManager)
    {
        $this->dm = $dm;
        $this->translator = $translator;
        $this->redisSubscribeManager = $redisSubscribeManager;
        $this->emailManager = $emailManager;
    }

    /**
     * Load not read notifications for passed $user
     *
     * @param User $user
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function loadNotRead(User $user){
        $qb = $this->dm->createQueryBuilder(Notification::class);
        $qb->field('userId')->equals($user->getId());
        $qb->field('isRead')->equals(false);

        $query = $qb->getQuery();
        return $query->execute();
    }

    /**
     * Load and return Notification by $notificationId
     *
     * @param $notificationId
     * @return Notification
     * @throws AppException
     */
    public function load($notificationId) : Notification
    {
        /** @var Notification $notification */
        $notification = $this->dm->getRepository(Notification::class)->find($notificationId);
        if(!($notification instanceof Notification)) throw new AppException('error.notification.not_found');

        return $notification;
    }

    /**
     * @param User $user
     * @param int $type
     * @param null $relatedObject
     * @return Notification
     * @throws AppException
     */
    public function create(User $user, int $type, $relatedObject = null){
        if($user->getLocale()) $this->setLocale($user->getLocale());

        $notification = new Notification($user->getId(), $type);

        if(!is_null($relatedObject)){
            if($relatedObject instanceof Order) {
                $notification->setRelatedObjectId($relatedObject->getId());
            }elseif($relatedObject instanceof Trade){
                $notification->setRelatedObjectId($relatedObject->getId());
            }elseif($relatedObject instanceof User){
                $notification->setRelatedObjectId($relatedObject->getId());
            }elseif($relatedObject instanceof Deposit){
                $notification->setRelatedObjectId($relatedObject->getId());
            }elseif($relatedObject instanceof Withdrawal){
                $notification->setRelatedObjectId($relatedObject->getId());
            }else{
                throw new AppException('error.notification.related_object_not_allowed');
            }
        }

        $notification = $this->save($notification);

        $this->push($notification);

        return $notification;
    }

    /**
     * @param User $user
     * @param int $notificationType
     * @param array $params
     * @return bool
     * @throws AppException
     */
    public function sendEmailNotification(User $user, int $notificationType, array $params = []){
        if(!$this->emailManager->isTypeAllowed($notificationType)) return false;

        try{
            $this->emailManager
                ->initType($notificationType)
                ->setBodyParams($params)
                ->send($user->getEmail());
        }catch (\Exception $exception){
            throw new AppException('System cannot send e-mail');
        }

        return true;
    }

    /**
     * @param Notification $notification
     * @return array
     * @throws \Exception
     */
    public function buildNotificationMessage(Notification $notification) : array
    {
        if($this->locale){
            $message = $this->translator->trans($notification->getTypeName(), ['%id%' => $notification->getRelatedObjectId()], null, $this->locale);
        }else{
            $message = $this->translator->trans($notification->getTypeName(), ['%id%' => $notification->getRelatedObjectId()]);
        }

        return [
            'id'        => $notification->getId(),
            'createdAt' => $notification->getCreatedAtDateTime(),
            'userId'    => $notification->getUserId(),
            'message'   => $message,
            'style'     => $notification->getTypeStyle()
        ];
    }

    /**
     * Update the $notification and set as READ
     *
     * @param Notification $notification
     * @return Notification
     */
    public function setAsRead(Notification $notification){
        $notification->setIsRead(true);

        return $this->save($notification);
    }

    /**
     * Publish notification to user - via Websocket
     *
     * @param Notification $notification
     * @throws \Exception
     */
    public function push(Notification $notification){
        $this->redisSubscribeManager->pushNotification($this->buildNotificationMessage($notification));
    }

    /**
     * @param Notification $notification
     * @return Notification
     */
    public function save(Notification $notification){

        $this->dm->persist($notification);
        $this->dm->flush();

        return $notification;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
