<?php

namespace App\Command;

use App\Entity\CurrencyPair;
use App\Repository\CurrencyPairRepository;
use App\Resolver\GrowthResolver;
use App\Resolver\PriceResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CurrencyPairUpdateDataCommand extends Command
{
    protected static $defaultName = 'app:currency-pair:update-data';

    /** @var CurrencyPairRepository */
    private $currencyPairRepository;

    /** @var GrowthResolver */
    private $growthResolver;

    /** @var PriceResolver */
    private $priceResolver;

    /**
     * CurrencyPairUpdateDataCommand constructor.
     * @param CurrencyPairRepository $currencyPairRepository
     * @param GrowthResolver $growthResolver
     * @param PriceResolver $priceResolver
     */
    public function __construct(CurrencyPairRepository $currencyPairRepository, GrowthResolver $growthResolver, PriceResolver $priceResolver)
    {
        $this->currencyPairRepository = $currencyPairRepository;
        $this->growthResolver = $growthResolver;
        $this->priceResolver = $priceResolver;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currencyPairs = $this->currencyPairRepository->findBy(['enabled' => true]);
        /** @var CurrencyPair $currencyPair */
        foreach($currencyPairs as $currencyPair){
            if(is_null($currencyPair->getExternalOrderbook())) continue;

            $this->growthResolver->init();

            $growth = $this->growthResolver->resolveGrowth($currencyPair);
            $price = $this->priceResolver->resolve($currencyPair);
            $price1hPoints = $this->growthResolver->resolve1hPoints($currencyPair);

            $currencyPair->setGrowth24h($growth);
            $currencyPair->setPrice($price);
            $currencyPair->setPrice12Points($price1hPoints);

            $this->currencyPairRepository->save($currencyPair);
        }
    }
}
