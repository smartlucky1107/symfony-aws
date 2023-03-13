<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class ApiPrivateRequest
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $createdAtTime;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $key;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $requestUri;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $content;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $response;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $method;

    /**
     * ApiPrivateRequest constructor.
     * @param string $key
     * @param string $requestUri
     * @param string $content
     * @param string $method
     * @throws \Exception
     */
    public function __construct(string $key, string $requestUri, string $content, string $method)
    {
        $this->key = $key;
        $this->requestUri = $requestUri;
        $this->content = $content;
        $this->method = $method;

        $this->setCreatedAtTime(strtotime((new \DateTime('now'))->format('Y-m-d H:i:s')));
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * @param string $requestUri
     */
    public function setRequestUri(string $requestUri): void
    {
        $this->requestUri = $requestUri;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}