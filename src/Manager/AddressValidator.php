<?php

namespace App\Manager;

use App\Entity\Wallet\Wallet;
use App\Service\AddressApp\AddressAppManager;

class AddressValidator
{
    /** @var AddressAppManager */
    private $addressAppManager;

    /**
     * AddressValidator constructor.
     * @param AddressAppManager $addressAppManager
     */
    public function __construct(AddressAppManager $addressAppManager)
    {
        $this->addressAppManager = $addressAppManager;
    }

    /**
     * @param string $address
     * @param Wallet $wallet
     * @return bool
     */
    public function isValid(string $address, Wallet $wallet) : bool
    {
        try{
            if($wallet->isBtcWallet()){
                $response = (array) $this->addressAppManager->validateBitcoinAddress($address);
                if(isset($response['isValid']) && $response['isValid'] === true){
                    return true;
                }
            }elseif($wallet->isBchWallet()){
                $response = (array) $this->addressAppManager->validateBitcoinCashAddress($address);
                if(isset($response['isValid']) && $response['isValid'] === true){
                    return true;
                }
            }elseif($wallet->isBsvWallet()){
                $response = (array) $this->addressAppManager->validateBitcoinSvAddress($address);
                if(isset($response['isValid']) && $response['isValid'] === true){
                    return true;
                }
            }elseif($wallet->isEthWallet() || $wallet->isErc20Wallet()){
                $response = (array) $this->addressAppManager->validateEthereumAddress($address);
                if(isset($response['isValid']) && $response['isValid'] === true){
                    return true;
                }
            }
        }catch (\Exception $exception){
        }

        return false;
    }
}
