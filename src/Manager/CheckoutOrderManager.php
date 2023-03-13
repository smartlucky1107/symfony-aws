<?php

namespace App\Manager;

use App\Entity\PaymentProcessor;
use App\Entity\CheckoutOrder;
use App\Entity\Wallet\Deposit;
use App\Exception\AppException;
use App\Manager\Liquidity\LiquidityManager;
use App\Manager\Payment\PaymentProcessorInterface;
use App\Manager\Payment\PaywallManager;
use App\Manager\Payment\Przelewy24Manager;
use App\Repository\CheckoutOrderRepository;
use App\Security\SystemTagAccessResolver;
use App\Security\TagAccessResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CheckoutOrderManager
{
    /** @var CheckoutOrderRepository */
    private $checkoutOrderRepository;

    /** @var Przelewy24Manager */
    private $przelewy24Manager;

    /** @var PaywallManager */
    private $paywallManager;

    /** @var IndacoinManager */
    private $indacoinManager;

    /** @var SystemTagAccessResolver */
    private $systemTagAccessResolver;

    /**
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param Przelewy24Manager $przelewy24Manager
     * @param PaywallManager $paywallManager
     * @param IndacoinManager $indacoinManager
     * @param SystemTagAccessResolver $systemTagAccessResolver
     */
    public function __construct(CheckoutOrderRepository $checkoutOrderRepository, Przelewy24Manager $przelewy24Manager, PaywallManager $paywallManager, IndacoinManager $indacoinManager, SystemTagAccessResolver $systemTagAccessResolver)
    {
        $this->checkoutOrderRepository = $checkoutOrderRepository;
        $this->przelewy24Manager = $przelewy24Manager;
        $this->paywallManager = $paywallManager;
        $this->indacoinManager = $indacoinManager;
        $this->systemTagAccessResolver = $systemTagAccessResolver;
    }

    /**
     * Load CheckoutOrder to the class by $checkoutOrderId
     *
     * @param string $checkoutOrderId
     * @return CheckoutOrder
     * @throws AppException
     */
    public function load(string $checkoutOrderId) : CheckoutOrder
    {
        /** @var CheckoutOrder $checkoutOrder */
        $checkoutOrder = $this->checkoutOrderRepository->find($checkoutOrderId);
        if(!($checkoutOrder instanceof CheckoutOrder)) throw new AppException('error.checkout_order.not_found');

        return $checkoutOrder;
    }

//    /** @var RedisSubscribeManager  */
//    private $redisSubscribeManager;
//
//    /** @var NotificationManager */
//    private $notificationManager;
//
//    /** @var EventDispatcherInterface */
//    private $eventDispatcher;
//
//    /** @var NewOrderManager */
//    private $newOrderManager;
//
//    /** @var TagAccessResolver */
//    private $tagAccessResolver;
//
//    /** @var LiquidityManager */
//    private $liquidityManager;


    /**
     * @param CheckoutOrder $checkoutOrder
     * @return PaymentProcessorInterface
     * @throws AppException
     */
    private function resolvePaymentGateway(CheckoutOrder $checkoutOrder) : PaymentProcessorInterface
    {
        /** @var PaymentProcessor $paymentProcessor */
        $paymentProcessor = $checkoutOrder->getPaymentProcessor();
        if(!($paymentProcessor instanceof PaymentProcessor)) throw new AppException('Payment processor not defined');
        if(!$paymentProcessor->isEnabled()) throw new AppException('Payment processor is not enabled');

        switch ($paymentProcessor->getId()){
            case 1:
                return $this->paywallManager;
            case 2:
                return $this->indacoinManager;
//                return $this->przelewy24Manager;
            default:
                throw new AppException('Payment processor not supported');
        }
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function placeOrder(CheckoutOrder $checkoutOrder) : CheckoutOrder
    {
        $this->systemTagAccessResolver->authTrading();
        // TODO weryfikacja wszystkiego

        $checkoutOrder = $this->checkoutOrderRepository->save($checkoutOrder);

        return $checkoutOrder;
    }

    /**
     * Reject order
     *
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function reject(CheckoutOrder $checkoutOrder) : CheckoutOrder
    {
        $checkoutOrder->setStatus(CheckoutOrder::STATUS_REJECTED);
        $checkoutOrder = $this->checkoutOrderRepository->save($checkoutOrder);

        return $checkoutOrder;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @param string $paymentUrl
     * @return CheckoutOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updatePaymentUrl(CheckoutOrder $checkoutOrder, string $paymentUrl) : CheckoutOrder
    {
        $checkoutOrder->setPaymentUrl($paymentUrl);

        return $this->checkoutOrderRepository->save($checkoutOrder);
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return string
     * @throws AppException
     */
    public function generatePaymentUrl(CheckoutOrder $checkoutOrder) : string
    {
        return $this->resolvePaymentGateway($checkoutOrder)->obtainPaymentUrl($checkoutOrder);
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function preparePayment(CheckoutOrder $checkoutOrder) : CheckoutOrder
    {
        if($checkoutOrder->getStatus() !== CheckoutOrder::STATUS_PENDING) throw new AppException('Status does not allowed to update payment url');

        $checkoutOrder->setStatus(CheckoutOrder::STATUS_PAYMENT_INIT);
        $checkoutOrder = $this->checkoutOrderRepository->save($checkoutOrder);

        $paymentUrl = $this->generatePaymentUrl($checkoutOrder);
        $checkoutOrder = $this->updatePaymentUrl($checkoutOrder, $paymentUrl);

        return $checkoutOrder;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setPaid(CheckoutOrder $checkoutOrder) : CheckoutOrder
    {
        if($checkoutOrder->getStatus() !== CheckoutOrder::STATUS_PAYMENT_INIT) throw new AppException('Status does not allowed to set order as paid');

        $checkoutOrder->setStatus(CheckoutOrder::STATUS_PAYMENT_SUCCESS);
        $checkoutOrder = $this->checkoutOrderRepository->save($checkoutOrder);

        return $checkoutOrder;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setProcessing(CheckoutOrder $checkoutOrder) : CheckoutOrder
    {
        if($checkoutOrder->getStatus() !== CheckoutOrder::STATUS_PAYMENT_SUCCESS) throw new AppException('Status does not allowed to set order as processing');

        $checkoutOrder->setStatus(CheckoutOrder::STATUS_PROCESSING);
        $checkoutOrder = $this->checkoutOrderRepository->save($checkoutOrder);

        return $checkoutOrder;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return CheckoutOrder
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setCompleted(CheckoutOrder $checkoutOrder) : CheckoutOrder
    {
        if($checkoutOrder->getStatus() !== CheckoutOrder::STATUS_PROCESSING) throw new AppException('Status does not allowed to set order as completed');

        $checkoutOrder->setStatus(CheckoutOrder::STATUS_COMPLETED);
        $checkoutOrder = $this->checkoutOrderRepository->save($checkoutOrder);

        return $checkoutOrder;
    }
}
