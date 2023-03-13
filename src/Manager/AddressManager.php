<?php

namespace App\Manager;

use App\Entity\Address;
use App\Entity\Currency;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Repository\AddressRepository;
use App\Service\AddressApp\AddressAppManager;

class AddressManager
{
    /** @var Address */
    private $address;

    /** @var AddressRepository */
    private $addressRepository;

    /** @var AddressAppManager */
    private $addressAppManager;

    /**
     * AddressManager constructor.
     * @param AddressRepository $addressRepository
     * @param AddressAppManager $addressAppManager
     */
    public function __construct(AddressRepository $addressRepository, AddressAppManager $addressAppManager)
    {
        $this->addressRepository = $addressRepository;
        $this->addressAppManager = $addressAppManager;
    }

    /**
     * Load Address to the class by $address
     *
     * @param string $address
     * @return Address
     * @throws AppException
     */
    public function load(string $address) : Address
    {
        $this->address = $this->addressRepository->findOneBy([
            'address' => $address
        ]);
        if(!($this->address instanceof Address)) throw new AppException('error.address.not_found');

        return $this->address;
    }

    /**
     * @param Wallet $wallet
     * @return Address
     * @throws AppException
     * @throws \App\Exception\ApiConnectionException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generate(Wallet $wallet) : Address
    {
        $response = null;

        if($wallet->isErc20Wallet()){
            $response = (array) $this->addressAppManager->generateEthereumErc20Address($wallet->getCurrency()->getSmartContractAddress());
        }elseif($wallet->isEthWallet()){
            $response = (array) $this->addressAppManager->generateEthereumAddress();
        }elseif($wallet->isBtcWallet()){
            $response = (array) $this->addressAppManager->generateBitcoinAddress();
        }elseif($wallet->isBchWallet()){
            $response = (array) $this->addressAppManager->generateBitcoinCashAddress();
        }elseif($wallet->isBsvWallet()){
            $response = (array) $this->addressAppManager->generateBitcoinSvAddress();
        }else{
            throw new AppException('error.address.type_not_allowed');
        }

        if(isset($response['address']) && isset($response['address']->publicKey)){
            /** @var Address $address */
            $address = new Address($wallet, $response['address']->publicKey);
            $address = $this->addressRepository->save($address);

            return $address;
        }else{
            throw new AppException('error.address.cannot_create');
        }
    }
}
