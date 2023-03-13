<?php

namespace App\Manager\Liquidity;

use App\Entity\CurrencyPair;
use App\Entity\Liquidity\ExternalOrder;
use App\Repository\Liquidity\ExternalOrderRepository;

class ExternalOrderManager
{
    /** @var ExternalOrderRepository */
    private $externalOrderRepository;

    /**
     * ExternalOrderManager constructor.
     * @param ExternalOrderRepository $externalOrderRepository
     */
    public function __construct(ExternalOrderRepository $externalOrderRepository)
    {
        $this->externalOrderRepository = $externalOrderRepository;
    }

    /**
     * @param CurrencyPair $currencyPair
     */
    public function prepareOrderBookRemoval(CurrencyPair $currencyPair) : void
    {
        $this->externalOrderRepository->prepareForRemovalByCurrencyPair($currencyPair);
    }

    /**
     * @param CurrencyPair $currencyPair
     */
    public function clearRemovedOrderBook(CurrencyPair $currencyPair) : void
    {
        $this->externalOrderRepository->removeByCurrencyPair($currencyPair);
    }
}
