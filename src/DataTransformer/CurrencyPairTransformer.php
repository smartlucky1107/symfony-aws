<?php

namespace App\DataTransformer;

use App\Entity\Currency;
use App\Entity\CurrencyPair;
use App\Exception\AppException;
use App\Repository\CurrencyPairRepository;
use App\Repository\CurrencyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CurrencyPairTransformer extends AppTransformer
{
    /** @var CurrencyRepository */
    private $currencyRepository;

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /**
     * CurrencyPairTransformer constructor.
     * @param CurrencyRepository $currencyRepository
     * @param CurrencyPairRepository $currencyPairRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(CurrencyRepository $currencyRepository, CurrencyPairRepository $currencyPairRepository, ValidatorInterface $validator)
    {
        $this->currencyRepository = $currencyRepository;
        $this->currencyPairRepository = $currencyPairRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param Request $request
     * @return CurrencyPair
     * @throws AppException
     */
    public function transform(Request $request) : CurrencyPair
    {
        $baseCurrencyId = (int) $request->get('baseCurrencyId');
        $quotedCurrencyId = (int) $request->get('quotedCurrencyId');

        /** @var Currency $baseCurrency */
        $baseCurrency = $this->currencyRepository->find($baseCurrencyId);
        if(!($baseCurrency instanceof Currency)) throw new AppException('Base currency not found');

        $quotedCurrency = $this->currencyRepository->find($quotedCurrencyId);
        if(!($quotedCurrency instanceof Currency)) throw new AppException('Quoted currency not found');

        if($baseCurrency->getId() === $quotedCurrency->getId()) throw new AppException('The same currency is not allowed');

        /** @var CurrencyPair $currencyPair */
        $currencyPair = $this->currencyPairRepository->findOneBy(['baseCurrency' => $baseCurrency->getId(), 'quotedCurrency' => $quotedCurrency->getId()]);
        if($currencyPair instanceof CurrencyPair) throw new AppException('Currency pair already exists');

        /** @var CurrencyPair $currencyPair */
        $currencyPair = new CurrencyPair($baseCurrency, $quotedCurrency);

        return $currencyPair;
    }
}
