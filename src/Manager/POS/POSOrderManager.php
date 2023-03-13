<?php

namespace App\Manager\POS;

use App\Entity\POS\POSOrder;
use App\Exception\AppException;
use App\Manager\SMS\SerwerSMSManager;
use App\Repository\POS\POSOrderRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class POSOrderManager
{
    /** @var POSOrderRepository */
    private $POSOrderRepository;

    /** @var POSReceiptManager */
    private $POSReceiptManager;

    /** @var SerwerSMSManager */
    private $serwerSMSManager;

    /** @var ParameterBagInterface */
    private $parameters;

    /** @var string */
    private $frontendBaseUrl;

    /**
     * POSOrderManager constructor.
     * @param POSOrderRepository $POSOrderRepository
     * @param POSReceiptManager $POSReceiptManager
     * @param SerwerSMSManager $serwerSMSManager
     * @param ParameterBagInterface $parameters
     */
    public function __construct(POSOrderRepository $POSOrderRepository, POSReceiptManager $POSReceiptManager, SerwerSMSManager $serwerSMSManager, ParameterBagInterface $parameters)
    {
        $this->POSOrderRepository = $POSOrderRepository;
        $this->POSReceiptManager = $POSReceiptManager;
        $this->serwerSMSManager = $serwerSMSManager;
        $this->parameters = $parameters;

        $this->frontendBaseUrl = $parameters->get('frontend_base_url');
    }

    /**
     * @return POSOrderRepository
     */
    public function getPOSOrderRepository(): POSOrderRepository
    {
        return $this->POSOrderRepository;
    }

    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function placeOrder(POSOrder $POSOrder) : POSOrder
    {
        // TODO weryfikacja wszystkiego - głównie external balances oraz balance sprzedawcy

        $POSOrder = $this->POSOrderRepository->save($POSOrder);

        $this->sendConfirmationCode($POSOrder);

        return $POSOrder;
    }

    /**
     * @param POSOrder $POSOrder
     * @throws AppException
     */
    private function validateExpired(POSOrder $POSOrder) : void
    {
        if($POSOrder->isExpired()) throw new AppException('POS Order is expired');
    }

    /**
     * @param POSOrder $POSOrder
     * @param string $confirmationCode
     * @return POSOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function confirm(POSOrder $POSOrder, string $confirmationCode) : POSOrder
    {
        $this->validateExpired($POSOrder);

        // TODO weryfikacja wszystkiego - głównie external balances oraz balance sprzedawcy

        if(!$POSOrder->isInit()) throw new AppException('error.order.status_does_not_allow');
        if(!$POSOrder->isConfirmationCodeValid($confirmationCode)) throw new AppException('Wrong confirmation code');

        $POSOrder->setStatus(POSOrder::STATUS_NEW);

        $POSOrder = $this->POSOrderRepository->save($POSOrder);

        return $POSOrder;
    }

    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function reject(POSOrder $POSOrder) : POSOrder
    {
        if($POSOrder->isRejected()) return $POSOrder;

        if(!$POSOrder->isInit()) throw new AppException('error.order.status_does_not_allow');

        $POSOrder->setStatus(POSOrder::STATUS_REJECTED);

        return $this->POSOrderRepository->save($POSOrder);
    }

    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setProcessing(POSOrder $POSOrder) : POSOrder
    {
        if(!$POSOrder->isNew()) throw new AppException('error.order.status_does_not_allow');

        $POSOrder->setStatus(POSOrder::STATUS_PROCESSING);
        $POSOrder = $this->POSOrderRepository->save($POSOrder);

        return $POSOrder;
    }

    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setCompleted(POSOrder $POSOrder) : POSOrder
    {
        $POSOrder->setStatus(POSOrder::STATUS_COMPLETED);
        $POSOrder = $this->POSOrderRepository->save($POSOrder);

        $this->sendConfirmationSMS($POSOrder);

        return $POSOrder;
    }

    /**
     * @param POSOrder $POSOrder
     * @throws AppException
     */
    public function sendConfirmationCode(POSOrder $POSOrder) : void
    {
        $this->validateExpired($POSOrder);

        if(!$POSOrder->isInit()) throw new AppException('error.order.status_does_not_allow');

        $this->serwerSMSManager->sendSMS($POSOrder->getPhone(),
            'Confirm purchase of ' . $POSOrder->toPrecision($POSOrder->getAmount()) . ' ' . $POSOrder->getCurrencyPair()->getBaseCurrency()->getShortName() . ' on swapcoin.today'.
            '. Confirmation code: ' . $POSOrder->getConfirmationCode()
        );
    }

    /**
     * @param POSOrder $POSOrder
     * @throws AppException
     */
    public function sendConfirmationSMS(POSOrder $POSOrder) : void
    {
        if(!$POSOrder->isCompleted()) throw new AppException('error.order.status_does_not_allow');

        $this->serwerSMSManager->sendSMS($POSOrder->getPhone(),
            'Thank you for purchasing ' . $POSOrder->toPrecision($POSOrder->getAmount()) . ' ' . $POSOrder->getCurrencyPair()->getBaseCurrency()->getShortName() . '. '.
            'You can receive your cryptocurrencies here: '.$this->frontendBaseUrl.'odbierz/' . $POSOrder->getSignature().'/'.$POSOrder->getRedeemHash() . '. Confirmation code: ' . $POSOrder->getRedeemCode()
        );
    }

    /**
     * @param POSOrder $POSOrder
     * @throws AppException
     */
    public function sendRedeemCode(POSOrder $POSOrder) : void
    {
        if(!$POSOrder->isCompleted()) throw new AppException('error.order.status_does_not_allow');

        $this->serwerSMSManager->sendSMS($POSOrder->getPhone(),
            'Kod potwierdzający odbiór: ' . $POSOrder->getRedeemCode()
        );
    }

    /**
     * @param POSOrder $POSOrder
     * @throws AppException
     */
    public function sendRedeemTransferCode(POSOrder $POSOrder) : void
    {
        if(!$POSOrder->isCompleted()) throw new AppException('error.order.status_does_not_allow');

        $this->serwerSMSManager->sendSMS($POSOrder->getPhone(),
            'Confirmation code: ' . $POSOrder->getRedeemTransferCode()
        );
    }



    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function redeemExternal(POSOrder $POSOrder) : POSOrder
    {
        $POSOrder->setStatus(POSOrder::STATUS_REDEEM_EXTERNAL_INIT);

        return $this->POSOrderRepository->save($POSOrder);
    }

    /**
     * @param POSOrder $POSOrder
     * @return POSOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function redeemInternal(POSOrder $POSOrder) : POSOrder
    {
        $POSOrder->setStatus(POSOrder::STATUS_REDEEM_INTERNAL_INIT);

        return $this->POSOrderRepository->save($POSOrder);
    }
}
