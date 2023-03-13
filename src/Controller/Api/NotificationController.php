<?php

namespace App\Controller\Api;

use App\Document\Notification;
use App\Exception\AppException;
use App\Manager\NotificationManager;
use App\Manager\RedisSubscribeManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class NotificationController extends FOSRestController
{
    /**
     * Set notification as read
     *
     * @Rest\Patch("/notifications/{notificationId}/read", requirements={"notificationId"="\d+"})
     *
     * @SWG\Parameter( name="notificationId",    in="path", type="integer", description="The id of notifications" )
     * @SWG\Response(
     *     response=204,
     *     description="Notification set as read",
     * )
     * @SWG\Tag(name="Notification")
     *
     * @param string $notificationId
     * @param NotificationManager $notificationManager
     * @return View
     * @throws AppException
     */
    public function patchSetAsRead(string $notificationId, NotificationManager $notificationManager) : View
    {
        /** @var Notification $notification */
        $notification = $notificationManager->load($notificationId);

        // set notification as already read
        $notificationManager->setAsRead($notification);

        return $this->view([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Get recent notifications
     *
     * @Rest\Get("/notifications/recent")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns array of Notifications",
     * )
     * @SWG\Tag(name="Notification")
     *
     * @param NotificationManager $notificationManager
     * @return View
     * @throws \Exception
     */
    public function getRecentNotifications(NotificationManager $notificationManager) : View
    {
        $messages = [];

        // TODO - create repo

        $notifications = $notificationManager->loadNotRead($this->getUser());
        if(count($notifications) > 0){
            /** @var Notification $notification */
            foreach($notifications as $notification){
                $messages[] = $notificationManager->buildNotificationMessage($notification);

                // set notification as already read
                $notificationManager->setAsRead($notification);
            }
        }

        return $this->view(['notifications' => $messages], JsonResponse::HTTP_OK);
    }
}
