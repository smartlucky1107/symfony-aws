<?php

namespace App\Document;

use App\Exception\AppException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
/**
 * @MongoDB\Document
 */
class Notification implements NotificationInterface
{
    const TYPES = [
        self::TYPE_ORDER_CREATED        => 'notification.type.order.created',
        self::TYPE_ORDER_UPDATED        => 'notification.type.order.updated',
        self::TYPE_ORDER_FILLED         => 'notification.type.order.filled',
        self::TYPE_ORDER_PARTLY_FILLED  => 'notification.type.order_partly.filled',
        self::TYPE_ORDER_REJECTED       => 'notification.type.order.rejected',

        self::TYPE_USER_REGISTERED              => 'notification.type.user.registered',
        self::TYPE_USER_EMAIL_CONFIRMED         => 'notification.type.user.email_confirmed',
        self::TYPE_USER_BANK_ACCOUNT_APPROVED   => 'notification.type.user.bank_account_approved',
        self::TYPE_USER_PASSWORD_REQUEST        => 'notification.type.user.password_request',

        self::TYPE_USER_TIER2_APPROVED          => 'notification.type.user.tier2.approved',
        self::TYPE_USER_TIER3_APPROVED          => 'notification.type.user.tier3.approved',

        self::TYPE_USER_TIER2_DECLINED          => 'notification.type.user.tier2.declined',
        self::TYPE_USER_TIER3_DECLINED          => 'notification.type.user.tier3.declined',

        self::TYPE_WITHDRAWAL_CREATED           => 'notification.type.withdrawal.created',
        self::TYPE_WITHDRAWAL_APPROVED          => 'notification.type.withdrawal.approved',
        self::TYPE_WITHDRAWAL_DECLINED          => 'notification.type.withdrawal.declined',
        self::TYPE_WITHDRAWAL_REJECTED          => 'notification.type.withdrawal.rejected',

        self::TYPE_DEPOSIT_ACCEPTED             => 'notification.type.deposit.accepted'
    ];

    const STYLE_SUCCESS     = 1;
    const STYLE_ERROR       = 2;
    const STYLE_WARNING     = 3;
    const STYLES = [
        self::STYLE_SUCCESS     => 'Success',
        self::STYLE_ERROR       => 'Error',
        self::STYLE_WARNING     => 'Warning'
    ];
    const TYPES_STYLES = [
        self::TYPE_ORDER_CREATED        => self::STYLE_SUCCESS,
        self::TYPE_ORDER_UPDATED        => self::STYLE_SUCCESS,
        self::TYPE_ORDER_FILLED         => self::STYLE_SUCCESS,
        self::TYPE_ORDER_PARTLY_FILLED  => self::STYLE_SUCCESS,
        self::TYPE_ORDER_REJECTED       => self::STYLE_ERROR,

        self::TYPE_USER_REGISTERED              => self::STYLE_SUCCESS,
        self::TYPE_USER_EMAIL_CONFIRMED         => self::STYLE_SUCCESS,
        self::TYPE_USER_BANK_ACCOUNT_APPROVED   => self::STYLE_SUCCESS,
        self::TYPE_USER_PASSWORD_REQUEST        => self::STYLE_SUCCESS,

        self::TYPE_USER_TIER2_APPROVED          => self::STYLE_SUCCESS,
        self::TYPE_USER_TIER3_APPROVED          => self::STYLE_SUCCESS,

        self::TYPE_USER_TIER2_DECLINED          => self::STYLE_ERROR,
        self::TYPE_USER_TIER3_DECLINED          => self::STYLE_ERROR,

        self::TYPE_WITHDRAWAL_CREATED           => self::STYLE_SUCCESS,
        self::TYPE_WITHDRAWAL_APPROVED          => self::STYLE_SUCCESS,
        self::TYPE_WITHDRAWAL_DECLINED          => self::STYLE_ERROR,
        self::TYPE_WITHDRAWAL_REJECTED          => self::STYLE_ERROR,

        self::TYPE_DEPOSIT_ACCEPTED             => self::STYLE_SUCCESS
    ];

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $createdAtTime;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $userId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $isRead;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $relatedObjectId;

    /**
     * Notification constructor.
     * @param $userId
     * @param $type
     * @throws AppException
     */
    public function __construct($userId, $type)
    {
        if(!$this->isTypeAllowed($type)) throw new AppException('Notification type not allowed');

        $this->userId = $userId;
        $this->type = $type;

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
        $this->setIsRead(false);
    }

    /**
     * @return int|null
     */
    public function getTypeStyle() : ?int
    {
        if(array_key_exists($this->type, self::TYPES_STYLES)){
            return self::TYPES_STYLES[$this->type];
        }

        return null;
    }

    /**
     * Check if passed $type is allowed for notification
     *
     * @param int $type
     * @return bool
     */
    public function isTypeAllowed(int $type) : bool
    {
        if(array_key_exists($type, self::TYPES)){
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTypeName() : string
    {
        return self::TYPES[$this->type];
    }

    /**
     * Convert createdAtTime to \DateTime object and return
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getCreatedAtDateTime() : \DateTime
    {
        return new \DateTime(date('Y-m-d H:i', $this->getCreatedAtTime()));
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCreatedAtTime()
    {
        return $this->createdAtTime;
    }

    /**
     * @param mixed $createdAtTime
     */
    public function setCreatedAtTime($createdAtTime): void
    {
        $this->createdAtTime = $createdAtTime;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getisRead()
    {
        return $this->isRead;
    }

    /**
     * @param mixed $isRead
     */
    public function setIsRead($isRead): void
    {
        $this->isRead = $isRead;
    }

    /**
     * @return mixed
     */
    public function getRelatedObjectId()
    {
        return $this->relatedObjectId;
    }

    /**
     * @param mixed $relatedObjectId
     */
    public function setRelatedObjectId($relatedObjectId): void
    {
        $this->relatedObjectId = $relatedObjectId;
    }
}
