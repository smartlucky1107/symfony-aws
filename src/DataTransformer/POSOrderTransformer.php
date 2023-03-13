<?php

namespace App\DataTransformer;

use App\Entity\CurrencyPair;
use App\Entity\POS\Employee;
use App\Entity\POS\POSOrder;
use App\Exception\AppException;
use App\Model\PriceInterface;
use App\Repository\CurrencyPairRepository;
use App\Resolver\InstantAmountResolver;
use App\Resolver\InstantPriceResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class POSOrderTransformer extends AppTransformer
{
    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var InstantPriceResolver */
    private $instantPriceResolver;

    /** @var InstantAmountResolver */
    private $instantAmountResolver;

    /**
     * POSOrderTransformer constructor.
     * @param CurrencyPairRepository $currencyPairRepository
     * @param InstantPriceResolver $instantPriceResolver
     * @param InstantAmountResolver $instantAmountResolver
     * @param ValidatorInterface $validator
     */
    public function __construct(CurrencyPairRepository $currencyPairRepository, InstantPriceResolver $instantPriceResolver, InstantAmountResolver $instantAmountResolver, ValidatorInterface $validator)
    {
        $this->currencyPairRepository = $currencyPairRepository;
        $this->instantPriceResolver = $instantPriceResolver;
        $this->instantAmountResolver = $instantAmountResolver;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param Employee $employee
     * @param Request $request
     * @return POSOrder
     * @throws AppException
     * @throws \Exception
     */
    public function transform(Employee $employee, Request $request) : POSOrder
    {
        if(!$request->request->has('currency')) throw new AppException('Currency is required');

        $baseCurrencyShortName = (string) $request->request->get('currency', '');
        if(empty($baseCurrencyShortName)) throw new AppException('Currency is required');

        $currencyPairShortName = strtoupper($baseCurrencyShortName) . '-' . strtoupper($employee->getWorkspace()->getDefaultQuotedCurrency()->getShortName());

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findByShortName($currencyPairShortName);
        if(!($currencyPair instanceof CurrencyPair)) throw new AppException('Currency not found');

        if($request->request->has('amount')){
            $amount = (string) $request->request->get('amount', '');
            if(empty($amount)) throw new AppException('Amount is required');
        }elseif($request->request->has('totalPrice')){
            $totalPrice = (string) $request->request->get('totalPrice', '');
            if(empty($totalPrice)) throw new AppException('Total price is required');

            $amount = $this->instantAmountResolver->resolveSell($currencyPair, $totalPrice);
        }else{
            throw new AppException('Amount or total price is required');
        }

        $instantPrice = $this->instantPriceResolver->resolveSell($currencyPair, $amount);
        if(is_null($instantPrice)) throw new AppException('Price cannot be calculated');

        $totalPrice = bcmul($instantPrice, $amount, PriceInterface::BC_SCALE);

        /** @var POSOrder $posOrder */
        $posOrder = new POSOrder($employee, $currencyPair, $amount, $totalPrice, $instantPrice);

        if($request->request->has('phone')){
            $phone = (string) $request->request->get('phone', '');
            $posOrder->setPhone($phone);
        }

        return $posOrder;
    }
}
