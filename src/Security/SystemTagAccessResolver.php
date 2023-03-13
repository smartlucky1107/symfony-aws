<?php

namespace App\Security;

use App\Entity\Configuration\SystemTag;
use App\Exception\AppException;
use App\Repository\Configuration\SystemTagRepository;

class SystemTagAccessResolver
{
    /** @var SystemTagRepository */
    private $systemTagRepository;

    /** @var array */
    private $systemTags;

    /**
     * SystemTagAccessResolver constructor.
     * @param SystemTagRepository $systemTagRepository
     */
    public function __construct(SystemTagRepository $systemTagRepository)
    {
        $this->systemTagRepository = $systemTagRepository;

        $this->systemTags = $this->systemTagRepository->findActivated();
    }

    /**
     * @throws AppException
     */
    public function authTrading(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_TRADING_DISABLED) throw new AppException('Trading is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authDeposit(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_DEPOSIT_DISABLED) throw new AppException('Deposit request is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authWithdrawal(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_WITHDRAWAL_DISABLED) throw new AppException('Withdrawal request is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authInternalTransfer(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_WITHDRAWAL_DISABLED) throw new AppException('Internal transfer request is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authRegister(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_REGISTER_DISABLED) throw new AppException('Registration is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authMarket(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_MARKET_DISABLED) throw new AppException('Market is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authLogin(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_LOGIN_DISABLED) throw new AppException('Login is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authPasswordResetting(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_PASSWORD_RESETTING_DISABLED) throw new AppException('Password resetting is not allowed');
            }
        }
    }

    /**
     * @throws AppException
     */
    public function authPos(){
        if($this->systemTags){
            /** @var SystemTag $systemTag */
            foreach($this->systemTags as $systemTag){
                if($systemTag->getType() === SystemTag::TYPE_POS_DISABLED) throw new AppException('POS is not allowed');
            }
        }
    }
}
