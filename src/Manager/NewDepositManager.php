<?php

namespace App\Manager;

use App\Entity\Address;
use App\Entity\CheckoutOrder;
use App\Entity\Currency;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Model\SystemUserInterface;
use App\Model\PriceInterface;
use App\Repository\AddressRepository;
use App\Repository\Wallet\DepositRepository;
use App\Repository\WalletRepository;

class NewDepositManager
{
    /** @var DepositRepository */
    private $depositRepository;

    /** @var AddressRepository */
    private $addressRepository;

    /** @var WalletRepository */
    private $walletRepository;

    /** @var DepositManager */
    private $depositManager;

    /**
     * NewDepositManager constructor.
     * @param DepositRepository $depositRepository
     * @param AddressRepository $addressRepository
     * @param WalletRepository $walletRepository
     * @param DepositManager $depositManager
     */
    public function __construct(DepositRepository $depositRepository, AddressRepository $addressRepository, WalletRepository $walletRepository, DepositManager $depositManager)
    {
        $this->depositRepository = $depositRepository;
        $this->addressRepository = $addressRepository;
        $this->walletRepository = $walletRepository;
        $this->depositManager = $depositManager;
    }

//    /**
//     * Convert $amount in decimal to wei
//     *
//     * @param string $amount
//     * @return string
//     */
//    public function toWei(string $amount)
//    {
//        $pow = bcpow('10', '18', 0);
//        $newAmount = bcmul($amount, $pow, 0);
//
//        return $newAmount;
//    }
//
//    /**
//     * Convert $amount in wei to decimal
//     *
//     * @param string $amount
//     * @return string|null
//     */
//    public function fromWei(string $amount)
//    {
//        $pow = bcpow('10', '18', PriceInterface::BC_SCALE);
//        $newAmount = bcdiv($amount, $pow, PriceInterface::BC_SCALE);
//
//        return $newAmount;
//    }

    /**
     * @param string $txHash
     * @param string $addressString
     * @param $value
     * @return Deposit
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function placeBlockchainDeposit(string $txHash, string $addressString, $value) : Deposit
    {
        $this->addressRepository->checkConnection();
        $this->depositRepository->checkConnection();

        /** @var Address $address */
        $address = $this->addressRepository->findByAddressOrException($addressString);

        /** @var Deposit $deposit */
        $deposit = $this->depositRepository->findOneBy([
            'blockchainTransactionHash' => $txHash,
            'blockchainAddress' => $address->getAddress()
        ]);
        if($deposit instanceof Deposit) throw new AppException('Deposit for ' . $txHash . ' and ' . $address->getAddress() . ' already exists');

        /** @var Deposit $deposit */
        $deposit = new Deposit($address->getWallet(), $value, $address->getWallet()->getUser(), 'blockchain', 'blockchainHash');
        $deposit->setBlockchainTransactionHash($txHash);
        $deposit->setBlockchainAddress($address->getAddress());
        $deposit = $this->depositRepository->save($deposit);

        $deposit = $this->depositManager->approveForce($deposit);

        return $deposit;
    }

    /**
     * @param CheckoutOrder $checkoutOrder
     * @return Deposit
     * @throws AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function placeCheckoutOrderDeposit(CheckoutOrder $checkoutOrder) : Deposit
    {
        // deposit to Checkout User
        $depositWallet = null;

        /** @var Currency $currency */
        $currency = $checkoutOrder->getCurrencyPair()->getQuotedCurrency();

        /** @var Wallet $depositWallet */
        $depositWallet = $this->walletRepository->getOneByCurrencyUserId($currency, SystemUserInterface::CHECKOUT_LIQ_USER);
        if(is_null($depositWallet)) throw new AppException('Wallet for deposit cannot be found');

        /** @var Deposit $deposit */
        $deposit = new Deposit($depositWallet, $checkoutOrder->getTotalPaymentValue(), $depositWallet->getUser(), $checkoutOrder->getPaymentProcessor()->getName(), $checkoutOrder->getId());
        $deposit = $this->depositRepository->save($deposit);

        $deposit = $this->depositManager->approveForce($deposit);

        return $deposit;
    }
}
