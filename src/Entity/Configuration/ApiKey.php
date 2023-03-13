<?php

namespace App\Entity\Configuration;

use App\Entity\User;
use App\Exception\AppException;
use App\Security\ApiRoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Configuration\ApiKeyRepository")
 */
class ApiKey
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     */
    private $createdAt;

    /**
     * @var User
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled = false;

    /**
     * @var string
     * @ORM\Column(name="key_hash", type="string", length=128)
     * @Assert\NotBlank()
     */
    private $key;

    /** @var string */
    private $keyPlain;

    /**
     * @var int
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $limit1M;

    /**
     * @var int
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $limit1H;

    /**
     * @var int
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     */
    private $limit1D;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $disabledAt;

    /**
     * @ORM\Column(type="json")
     */
    private $apiRoles;

    /**
     * ApiKey constructor.
     * @param User $user
     * @param array $roles
     * @throws AppException
     */
    public function __construct(User $user, array $roles = [])
    {
        $this->user = $user;
        $this->apiRoles = [];

        $this->setCreatedAt(new \DateTime('now'));
        $this->setEnabled(true);
        $this->setLimit1M(60);
        $this->setLimit1H(3600);
        $this->setLimit1D(5000);

        $this->setKeyPlain($this->generateKeyPlain());
        $this->setKey($this->generateKey($this->keyPlain));

        if(is_array($roles)){
            foreach($roles as $role){
                $this->addApiRole($role);
            }
        }
    }

    /**
     * @param string $apiRole
     * @throws AppException
     */
    public function addApiRole(string $apiRole)
    {
        if(!in_array($apiRole, ApiRoleInterface::ROLES)) throw new AppException('Api role not allowed');
        if(!in_array($apiRole, $this->apiRoles)){
            $this->apiRoles[] = $apiRole;
        }
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'key'       => $this->key,
            'keyPlain'  => $this->keyPlain ? $this->keyPlain : null,
        ];
    }

    /**
     * Serialize and return public basic data of the object
     *
     * @return array
     */
    public function serializeBasic() : array
    {
        return [
            'key'       => $this->key,
            'createdAt' => $this->createdAt,
            'enabled'   => $this->enabled,
            'disabledAt'=> $this->disabledAt
        ];
    }

    /**
     * @return string
     */
    public function generateKeyPlain() : string
    {
        return md5(uniqid() . rand(10000, 1000000));
    }

    /**
     * @param string $keyPlain
     * @return string
     */
    public function generateKey(string $keyPlain) : string
    {
        return md5(md5($keyPlain) . md5($keyPlain));
    }

    /**
     * @return bool
     */
    public function isRequestAllowed() : bool
    {
        if($this->isEnabled()){
            return true;
        }

        return false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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
     * @return int
     */
    public function getLimit1M(): int
    {
        return $this->limit1M;
    }

    /**
     * @param int $limit1M
     */
    public function setLimit1M(int $limit1M): void
    {
        $this->limit1M = $limit1M;
    }

    /**
     * @return int
     */
    public function getLimit1H(): int
    {
        return $this->limit1H;
    }

    /**
     * @param int $limit1H
     */
    public function setLimit1H(int $limit1H): void
    {
        $this->limit1H = $limit1H;
    }

    /**
     * @return int
     */
    public function getLimit1D(): int
    {
        return $this->limit1D;
    }

    /**
     * @param int $limit1D
     */
    public function setLimit1D(int $limit1D): void
    {
        $this->limit1D = $limit1D;
    }

    /**
     * @return string
     */
    public function getKeyPlain(): string
    {
        return $this->keyPlain;
    }

    /**
     * @param string $keyPlain
     */
    public function setKeyPlain(string $keyPlain): void
    {
        $this->keyPlain = $keyPlain;
    }

    /**
     * @return \DateTime|null
     */
    public function getDisabledAt(): ?\DateTime
    {
        return $this->disabledAt;
    }

    /**
     * @param \DateTime|null $disabledAt
     */
    public function setDisabledAt(?\DateTime $disabledAt): void
    {
        $this->disabledAt = $disabledAt;
    }

    /**
     * @return mixed
     */
    public function getApiRoles()
    {
        return $this->apiRoles;
    }

    /**
     * @param mixed $apiRoles
     */
    public function setApiRoles($apiRoles): void
    {
        $this->apiRoles = $apiRoles;
    }
}
