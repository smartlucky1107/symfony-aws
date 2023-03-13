<?php

namespace App\Manager\ApiPrivate;

use App\Entity\Configuration\ApiKey;
use App\Exception\AppException;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use App\Document\ApiPrivateRequest;

class ApiPrivateManager
{
    /** @var DocumentManager */
    private $dm;

    /**
     * ApiPrivateManager constructor.
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param string $key
     * @param string $period
     * @return int
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function getRequests(string $key, string $period) : int
    {
        $minDate = new \DateTime('now');

        if($period === 'M'){
            $minDate->modify('-1 minute');
        }elseif($period == 'H'){
            $minDate->modify('-1 hour');
        }elseif($period == 'D'){
            $minDate->modify('-1 day');
        }else{
            throw new AppException('request period not allowed');
        }

        $minTime = strtotime($minDate->format('Y-m-d H:i:s'));

        $qb = $this->dm->createQueryBuilder(ApiPrivateRequest::class);
        $qb->field('key')->equals($key);
        $qb->field('createdAtTime')->gt($minTime);

        return $qb->getQuery()->execute()->count();
    }

    /**
     * @param string $key
     * @return int
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getMinuteRequests(string $key): int
    {
        return $this->getRequests($key, 'M');
    }

    /**
     * @param string $key
     * @return int
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getHourRequests(string $key): int
    {
        return $this->getRequests($key, 'H');
    }

    /**
     * @param string $key
     * @return int
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getDayRequests(string $key): int
    {
        return $this->getRequests($key, 'D');
    }

    /**
     * @param string $key
     * @param string $requestUri
     * @param string $content
     * @param string $method
     * @return ApiPrivateRequest
     * @throws \Exception
     */
    public function saveRequest(string $key, string $requestUri, string $content, string $method = '') : ApiPrivateRequest
    {
        $apiPrivateRequest = new ApiPrivateRequest($key, $requestUri, $content, $method);

        $this->dm->persist($apiPrivateRequest);
        $this->dm->flush();

        return $apiPrivateRequest;
    }

    /**
     * @param ApiPrivateRequest $apiPrivateRequest
     * @param string $response
     * @return ApiPrivateRequest
     */
    public function assignResponse(ApiPrivateRequest $apiPrivateRequest, string $response) : ApiPrivateRequest
    {
        $apiPrivateRequest->setResponse($response);

        $this->dm->persist($apiPrivateRequest);
        $this->dm->flush();

        return $apiPrivateRequest;
    }

    /**
     * @param ApiKey $apiKey
     * @return bool
     * @throws AppException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function verifyRequestLimits(ApiKey $apiKey) : bool
    {
        $minuteRequests = $this->getMinuteRequests($apiKey->getKey());
        $hourRequests = $this->getHourRequests($apiKey->getKey());
        $dayRequests = $this->getDayRequests($apiKey->getKey());

        if($minuteRequests > $apiKey->getLimit1M()) throw new AppException('Requests limit for a minute exceeded');
        if($hourRequests > $apiKey->getLimit1H()) throw new AppException('Requests limit for a hour exceeded');
        if($dayRequests > $apiKey->getLimit1D()) throw new AppException('Requests limit for a day exceeded');

        return true;
    }
}
