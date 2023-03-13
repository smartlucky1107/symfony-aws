<?php

namespace App\Model;

use App\Security\VoterRoleInterface;

class VRoleModel implements VoterRoleInterface
{
    /** @var string */
    public $module;

    /** @var string */
    public $action;

    /**
     * VRoleModel constructor.
     * @param string $module
     * @param string $action
     */
    public function __construct(string $module, string $action)
    {
        $this->module = $module;
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string $module
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }
}