<?php

namespace App\DataTransformer;

use App\Entity\CurrencyPair;
use App\Entity\OrderBook\Order;
use App\Entity\User;
use App\Entity\Wallet\Wallet;
use App\Exception\AppException;
use App\Repository\CurrencyPairRepository;
use App\Resolver\FeeResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderTransformer extends AppTransformer
{
    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var FeeResolver */
    private $feeResolver;

    /**
     * OrderTransformer constructor.
     * @param CurrencyPairRepository $currencyPairRepository
     * @param FeeResolver $feeResolver
     * @param ValidatorInterface $validator
     */
    public function __construct(CurrencyPairRepository $currencyPairRepository, FeeResolver $feeResolver, ValidatorInterface $validator)
    {
        $this->currencyPairRepository = $currencyPairRepository;
        $this->feeResolver = $feeResolver;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new Order object
     *
     * @param User $user
     * @param array $queryParameters
     * @return Order
     * @throws AppException
     */
    public function transform(User $user, array $queryParameters = []) : Order
    {
        if(!isset($queryParameters['amount'])) throw new AppException('Amount is required');
        if(!isset($queryParameters['type'])) throw new AppException('Type is required');
        if(!(isset($queryParameters['currencyPairId']) || isset($queryParameters['currencyPairShortName']))){
            throw new AppException('Currency pair is required');
        }

        $amount = $queryParameters['amount'];
        if(!is_numeric($amount)) throw new AppException('Amount is invalid');
        $amount = bcadd($amount, '0', 8);
        if(!($amount > 0)) throw new AppException('Amount is invalid');

        if(isset($queryParameters['limitPrice'])){
            $limitPrice = $queryParameters['limitPrice'];
        }else{
            $limitPrice = null;
        }

        if(is_numeric($limitPrice)) {
            $limitPrice = bcadd($limitPrice, '0', 8);
            if(!($limitPrice > 0)) throw new AppException('Limit price is invalid');

            $total = bcmul($amount, $limitPrice, 8);
            if(!($total > 0)) throw new AppException('Limit price is invalid');
        }

        if(isset($queryParameters['currencyPairId']) && $queryParameters['currencyPairId']){
            $currencyPairId = (int) $queryParameters['currencyPairId'];
            /** @var CurrencyPair $currencyPair */
            $currencyPair = $this->currencyPairRepository->find($currencyPairId);
        }elseif(isset($queryParameters['currencyPairShortName']) && $queryParameters['currencyPairShortName']){
            $currencyPairShortName = $queryParameters['currencyPairShortName'];
            /** @var CurrencyPair $currencyPair */
            $currencyPair = $this->currencyPairRepository->findByShortName($currencyPairShortName);
        }else{
            throw new AppException('Currency pair is required');
        }

        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency pair not found');
        if(!$currencyPair->isEnabled()) throw new AppException('Trading for the pair is not allowed');

        if((int) $queryParameters['type'] === 2){
            if($currencyPair->getId() === 43 || $currencyPair->getId() === 44 || $currencyPair->getId() === 45){
                if($user->getId() !== 10412){
                    throw new AppException('Trading for the pair is not allowed');
                }
            }
        }

        if(is_numeric($limitPrice) && $currencyPair->getQuotedCurrency()->isFiatType()) {
            $limitPrice = $currencyPair->toPrecisionQuoted($limitPrice);
        }

        if(!$currencyPair->isExternalLotSizeValid((string) $amount)) throw new AppException('Lot size is not valid');
        if(is_numeric($limitPrice)){
            if(!$currencyPair->isExternalMinNotionalValid((string) $amount, (string) $limitPrice)) throw new AppException('Lot size is not valid');
        }

        if(!$currencyPair->isMinMaxLimitPriceValid($limitPrice)) throw new AppException('Limit price is not valid');

//        if($currencyPair->isBinanceLiquidity()){
//            if(!$currencyPair->isExternalLotSizeValid((string) $amount)) throw new AppException('Lot size is not valid');
//        }

        $userWallets = $user->getWallets();
        if(!$userWallets) throw new AppException('User wallets not found');

        $baseCurrencyWallet = null;
        $quotedCurrencyWallet = null;

        /** @var Wallet $userWallet */
        foreach($userWallets as $userWallet){
            if($userWallet->getCurrency()->getId() === $currencyPair->getBaseCurrency()->getId()){
                $baseCurrencyWallet = $userWallet;
            }
            if($userWallet->getCurrency()->getId() === $currencyPair->getQuotedCurrency()->getId()){
                $quotedCurrencyWallet = $userWallet;
            }
        }

        if($baseCurrencyWallet instanceof Wallet && $quotedCurrencyWallet instanceof Wallet){
            //if($limitPrice){ $limitPrice = (float) $limitPrice; }

            $type = (int) $queryParameters['type'];

            /** @var Order $order */
            $order = new Order($user, $baseCurrencyWallet, $quotedCurrencyWallet, $currencyPair, $type, $amount, $limitPrice);
        }else{
            throw new AppException('Base currency wallet and quoted currency wallet is required');
        }

        return $order;
    }
}
