<?php

namespace App\DataTransformer;

use App\Entity\Currency;
use App\Exception\AppException;
use App\Repository\CurrencyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CurrencyTransformer extends AppTransformer
{
    /** @var CurrencyRepository */
    private $currencyRepository;

    /**
     * CurrencyTransformer constructor.
     * @param CurrencyRepository $currencyRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(CurrencyRepository $currencyRepository, ValidatorInterface $validator)
    {
        $this->currencyRepository = $currencyRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param Request $request
     * @return Currency
     * @throws AppException
     */
    public function transform(Request $request) : Currency
    {
        $fullName = (string) $request->get('fullName');
        $shortName = (string) $request->get('shortName');
        $type = (string) $request->get('type');

        /** @var Currency $currency */
        $currency = $this->currencyRepository->findOneBy(['shortName' => $shortName]);
        if($currency instanceof Currency) throw new AppException('Currency already exists');

        /** @var Currency $currency */
        $currency = new Currency($fullName, $shortName, $type);
        if($type === Currency::TYPE_ERC20){
            $smartContractAddress = (string) $request->get('smartContractAddress');
            if(!$smartContractAddress) throw new AppException('Smart contract address is required for ERC20 type');

            $currency->setSmartContractAddress($smartContractAddress);
        }elseif($type === Currency::TYPE_BEP20){
            $smartContractAddress = (string) $request->get('smartContractAddress');
            if(!$smartContractAddress) throw new AppException('Smart contract address is required for BEP20 type');

            $currency->setSmartContractAddress($smartContractAddress);
        }

        return $currency;
    }
}
