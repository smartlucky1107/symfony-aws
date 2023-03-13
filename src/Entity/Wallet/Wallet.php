<?php

namespace App\Entity\Wallet;

use App\Entity\User;
use App\Entity\Address;
use App\Entity\Currency;
use App\Model\FiatDepositInterface;
use App\Model\PriceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WalletRepository")
 */
class Wallet
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'        => 'id',
        'name'      => 'name',
        'amount'    => 'amount'
    ];

    const TYPE_FEE          = 'fee';
    const TYPE_CHECKOUT_FEE = 'checkout_fee';
    const TYPE_USER         = 'user';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Currency
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     */
    private $currency;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     * @SWG\Property(description="Total balance")
     */
    private $amount;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     * @SWG\Property(description="Balance from deposits")
     */
    private $amountDeposits;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="decimal", precision=36, scale=18)
     */
    private $amountPending;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @var User
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="wallets")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Address", mappedBy="wallet", orphanRemoval=true)
     */
    private $addresses;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=32)
     */
    private $type;

    /**
     * Wallet constructor.
     * @param User $user
     * @param Currency $currency
     * @param $name
     */
    public function __construct(User $user, Currency $currency, $name)
    {
        $this->user = $user;
        $this->currency = $currency;
        $this->name = $name;

        $this->setAmount(0);
        $this->setAmountPending(0);
        $this->setAmountDeposits(0);
        $this->addresses = new ArrayCollection();
        $this->type = self::TYPE_USER;
    }

    /**
     * Serialize and return public data of the object
     *
     * @return array
     */
    public function serialize() : array
    {
        return [
            'id'            => $this->id,
            'currency'      => $this->currency->serialize(),
            'amount'        => $this->toPrecision($this->amount),
            'amountPending' => $this->toPrecision($this->amountPending),
            'amountDeposits' => $this->toPrecision($this->amountDeposits),
            'amountDepositsLimit' => $this->toPrecision($this->resolveMaxDepositsAmount()),
            'amountDepositsLeft' => $this->toPrecision($this->resolveLeftDepositsAmount()),
            'name'          => $this->name,
            'freeAmount'    => $this->toPrecision($this->freeAmount()),
            'user'          => $this->user->serialize(),
            'freeAmountPercent'         => $this->freeAmountPercent(),
            'amountPendingPercentage'   => $this->amountPendingPercentage(),
            'isCrypto'                  => $this->getCurrency()->isCryptoType(),
            'isFiat'                    => $this->getCurrency()->isFiatType()
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
            'id'            => $this->id,
            'currency'      => $this->currency->serialize(),
        ];
    }

    /**
     * @return array
     */
    public function serializeForPrivateApi() : array
    {
        return [
            'currency'      => $this->currency->serialize(),
            'amount'        => $this->toPrecision($this->amount),
            'amountPending' => $this->toPrecision($this->amountPending),
            'name'          => $this->name,
            'freeAmount'    => $this->toPrecision($this->freeAmount()),
        ];
    }

    /**
     * @return int
     */
    public function resolveMaxDepositsAmount() : int
    {
        if($this->isFiatWalletPLN()) {
            return FiatDepositInterface::MAX_DEPOSIT_BALANCE_PLN;
        }elseif($this->isFiatWalletEUR()) {
            return FiatDepositInterface::MAX_DEPOSIT_BALANCE_EUR;
        }

        return 0;
    }

    /**
     * @param string $amount
     * @return bool
     */
    public function isDepositAmountAllowed(string $amount) : bool
    {
        if($this->isFiatWalletPLN() || $this->isFiatWalletEUR()) {
            $depositsLeft = $this->resolveLeftDepositsAmount();
            $comp = bccomp($amount, $depositsLeft, PriceInterface::BC_SCALE);
            if($comp === 1) return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function resolveLeftDepositsAmount() : string
    {
        if($this->isFiatWalletPLN() || $this->isFiatWalletEUR()) {
            $max = $this->resolveMaxDepositsAmount();

            $comp = bccomp($this->amountDeposits, $max, PriceInterface::BC_SCALE);
            if($comp === 1 || $comp === 0){
                return (string) 0;
            }else{
                return bcsub($max, $this->amountDeposits, PriceInterface::BC_SCALE);
            }
        }

        return (string) 0;
    }

    /**
     * @param string $value
     * @return string
     */
    public function toPrecision(string $value){
        return bcadd($value, 0, $this->getCurrency()->getRoundPrecision());
    }

    /**
     * @return string
     */
    public function amountToPrecision(){
        return $this->toPrecision($this->amount);
    }

    /**
     * @return string
     */
    public function amountPendingToPrecision(){
        return $this->toPrecision($this->amountPending);
    }

    /**
     * Check if the Wallet is Fiat wallet
     *
     * @return bool
     */
    public function isFiatWallet() : bool
    {
        $walletCurrencyType = $this->getCurrency()->getType();

        if($walletCurrencyType == Currency::TYPE_FIAT){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFiatWalletPLN() : bool
    {
        if($this->isFiatWallet()){
            $walletCurrencyShortName = strtolower($this->getCurrency()->getShortName());
            if(strtolower($walletCurrencyShortName) === 'pln') return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFiatWalletEUR() : bool
    {
        if($this->isFiatWallet()){
            $walletCurrencyShortName = strtolower($this->getCurrency()->getShortName());
            if(strtolower($walletCurrencyShortName) === 'eur') return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Erc20 wallet
     *
     * @return bool
     */
    public function isErc20Wallet() : bool
    {
        $walletCurrencyType = $this->getCurrency()->getType();
        $smartContractAddress = $this->getCurrency()->getSmartContractAddress();

        if($walletCurrencyType === Currency::TYPE_ERC20 && !is_null($smartContractAddress)){
            return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Bep20 wallet
     *
     * @return bool
     */
    public function isBep20Wallet() : bool
    {
        $walletCurrencyType = $this->getCurrency()->getType();
        $smartContractAddress = $this->getCurrency()->getSmartContractAddress();

        if($walletCurrencyType === Currency::TYPE_BEP20 && !is_null($smartContractAddress)){
            return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Erc20 TEO wallet
     *
     * @return bool
     */
    public function isErc20TEOWallet() : bool
    {
        $walletCurrencyType = $this->getCurrency()->getType();
        $smartContractAddress = $this->getCurrency()->getSmartContractAddress();

        if($walletCurrencyType === Currency::TYPE_ERC20 && !is_null($smartContractAddress) && strtolower($smartContractAddress) === strtolower('0x70f414b2bcc447f8e41a57c357c20e3ad1bb864d')){
            return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Bitcoin wallet
     *
     * @return bool
     */
    public function isBtcWallet() : bool
    {
        $walletCurrencyShortName = strtolower($this->getCurrency()->getShortName());
        $walletCurrencyType = $this->getCurrency()->getType();

        if($walletCurrencyType === Currency::TYPE_BTC && $walletCurrencyShortName === Currency::TYPE_BTC){
            return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Bitcoin Cash wallet
     *
     * @return bool
     */
    public function isBchWallet() : bool
    {
        $walletCurrencyShortName = strtolower($this->getCurrency()->getShortName());
        $walletCurrencyType = $this->getCurrency()->getType();

        if($walletCurrencyType === Currency::TYPE_BCH && $walletCurrencyShortName === Currency::TYPE_BCH){
            return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Bitcoin SV wallet
     *
     * @return bool
     */
    public function isBsvWallet() : bool
    {
        $walletCurrencyShortName = strtolower($this->getCurrency()->getShortName());
        $walletCurrencyType = $this->getCurrency()->getType();

        if($walletCurrencyType === Currency::TYPE_BSV && $walletCurrencyShortName === Currency::TYPE_BSV){
            return true;
        }

        return false;
    }

    /**
     * Check if the Wallet is Ethereum wallet
     *
     * @return bool
     */
    public function isEthWallet() : bool
    {
        $walletCurrencyShortName = strtolower($this->getCurrency()->getShortName());
        $walletCurrencyType = $this->getCurrency()->getType();

        if($walletCurrencyType === Currency::TYPE_ETH && $walletCurrencyShortName === Currency::TYPE_ETH){
            return true;
        }

        return false;
    }

    /**
     * Get free amount of the currency in the wallet
     *
     * @return string
     */
    public function freeAmount(){
        //return (float) $this->amount - (float) $this->amountPending;
        return bcsub($this->amount, $this->amountPending, PriceInterface::BC_SCALE);
    }

    /**
     * Return percentage pending amount on the wallet
     *
     * @return string
     */
    public function amountPendingPercentage(){
        if($this->amount > 0){
            //return $this->amountPending / $this->amount * 100;
            $a = bcdiv($this->amountPending, $this->amount, PriceInterface::BC_SCALE);
            $b = bcmul($a, '100', PriceInterface::BC_SCALE);

            return $b;
        }

        return (string) 0;
    }

    /**
     * Return percentage free amount on the wallet
     *
     * @return string
     */
    public function freeAmountPercent(){
        //return 100 - $this->amountPendingPercentage();
        return bcsub('100', $this->amountPendingPercentage(), PriceInterface::BC_SCALE);
    }

    /**
     * Verify if transfer is allowed from the wallet
     *
     * @param $amount
     * @return bool
     */
    public function isTransferAllowed($amount){
        $comp = bccomp($this->freeAmount(), $amount, PriceInterface::BC_SCALE);
        if($comp === 0 || $comp === 1){
            return true;
        }

//        if($this->freeAmount() >= $amount){
//            return true;
//        }
        return false;
    }

    /**
     * Verify is withdrawal is allowed from the wallet based on the minimal withdrawal for the currency
     *
     * @param $amount
     * @return bool
     */
    public function isWithdrawalAllowed($amount){
        $minWithdrawal = $this->getCurrency()->getMinWithdrawal();

        $comp = bccomp($minWithdrawal, $amount, PriceInterface::BC_SCALE);
        if($comp === 0 || $comp === -1){
            return true;
        }

        return false;
    }

    /**
     * Verify if passed $user is allowed for the wallet
     *
     * @param User $user
     * @return bool
     */
    public function isAllowedForUser(User $user) : bool
    {
        if($this->getUser()->getId() === $user->getId()){
            return true;
        }

        return false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \App\Entity\Currency
     */
    public function getCurrency(): \App\Entity\Currency
    {
        return $this->currency;
    }

    /**
     * @param \App\Entity\Currency $currency
     */
    public function setCurrency(\App\Entity\Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getAmountPending()
    {
        return $this->amountPending;
    }

    /**
     * @param mixed $amountPending
     */
    public function setAmountPending($amountPending): void
    {
        $this->amountPending = $amountPending;
    }

    /**
     * @return mixed
     */
    public function getAmountDeposits()
    {
        return $this->amountDeposits;
    }

    /**
     * @param mixed $amountDeposits
     */
    public function setAmountDeposits($amountDeposits): void
    {
        $this->amountDeposits = $amountDeposits;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|Address[]
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setWallet($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        if ($this->addresses->contains($address)) {
            $this->addresses->removeElement($address);
            // set the owning side to null (unless already changed)
            if ($address->getWallet() === $this) {
                $address->setWallet(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }
}
