<?php

namespace App\Manager\ListManager;

use App\Entity\User;

class Paginator
{
    /** @var int */
    public $page;

    /** @var int */
    public $pageSize;

    /** @var mixed */
    public $result;

    /** @var int */
    public $totalItems;

    /**
     * Paginator constructor.
     * @param int $page
     * @param int $pageSize
     * @param mixed $result
     * @param int $totalItems
     */
    public function __construct(int $page, int $pageSize, $result, int $totalItems)
    {
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->result = $result;
        $this->totalItems = $totalItems;
    }

    /**
     * Serialize $result object to array
     *
     * @param $result
     * @return array
     */
    public function serialize($result) : array
    {
        $array = [];

        if($result){
            foreach($result as $item){
                if(method_exists($item, 'serialize')){
                    $array[] = $item->{'serialize'}();
                }
            }
        }

        return $array;
    }

    /**
     * @param $result
     * @param User|null $user
     * @return array
     */
    public function serializeForPrivateApi($result, User $user = null) : array
    {
        $array = [];

        if($result){
            foreach($result as $item){
                if(method_exists($item, 'serializeForPrivateApi')){
                    $array[] = $item->{'serializeForPrivateApi'}($user);
                }
            }
        }

        return $array;
    }

    /**
     * @param bool $isForPrivateApi
     * @param User|null $user
     */
    public function serializeResult($isForPrivateApi = false, User $user = null)
    {
        if($isForPrivateApi){
            $this->result = $this->serializeForPrivateApi($this->result, $user);
        }else{
            $this->result = $this->serialize($this->result);
        }
    }
}