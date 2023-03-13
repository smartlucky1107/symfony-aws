<?php

namespace App\Entity\Configuration;

use App\Security\VoterRoleInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Configuration\VoterRoleRepository")
 */
class VoterRole
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'    => 'id',
        'module'  => 'module',
        'action'  => 'action',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedModules")
     */
    private $module;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getAllowedActions")
     */
    private $action;

    /**
     * VoterRole constructor.
     * @param $module
     * @param $action
     */
    public function __construct($module, $action)
    {
        $this->module = $module;
        $this->action = $action;
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'id'        => $this->id,
            'module'    => $this->module,
            'action'    => $this->action,
        ];
    }

    /**
     * Get allowed modules as simple array.
     *
     * @return array
     */
    public static function getAllowedModules(){
        return VoterRoleInterface::MODULES;
    }

    /**
     * Get allowed actions as simple array.
     *
     * @return array
     */
    public static function getAllowedActions(){
        return VoterRoleInterface::ACTIONS;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param mixed $module
     */
    public function setModule($module): void
    {
        $this->module = $module;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
    }
}
