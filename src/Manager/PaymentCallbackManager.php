<?php

namespace App\Manager;

use App\Entity\PaymentCallback;
use App\Repository\PaymentCallbackRepository;

class PaymentCallbackManager
{
    /** @var PaymentCallbackRepository */
    private $paymentCallbackRepository;

    /**
     * PaymentCallbackManager constructor.
     * @param PaymentCallbackRepository $paymentCallbackRepository
     */
    public function __construct(PaymentCallbackRepository $paymentCallbackRepository)
    {
        $this->paymentCallbackRepository = $paymentCallbackRepository;
    }

    /**
     * @param int $type
     * @param $data
     * @param string|null $signature
     * @return PaymentCallback
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function create(int $type, $data, string $signature = null) : PaymentCallback
    {
        /** @var PaymentCallback $paymentCallback */
        $paymentCallback = new PaymentCallback($type);
        $paymentCallback->setResponse($data);
        if(!is_null($signature)) $paymentCallback->setSignature($signature);

        return $this->saveCallback($paymentCallback);
    }

    /**
     * @param PaymentCallback $paymentCallback
     * @return PaymentCallback
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveCallback(PaymentCallback $paymentCallback) : PaymentCallback
    {
        return $this->paymentCallbackRepository->save($paymentCallback);
    }
}
