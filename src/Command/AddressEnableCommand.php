<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Currency;
use App\Repository\AddressRepository;
use App\Repository\CurrencyRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddressEnableCommand extends Command
{
    protected static $defaultName = 'app:address:enable';

    /** @var AddressRepository */
    private $addressRepository;

    /** @var CurrencyRepository */
    private $currencyRepository;

    /**
     * AddressEnableCommand constructor.
     * @param AddressRepository $addressRepository
     * @param CurrencyRepository $currencyRepository
     */
    public function __construct(AddressRepository $addressRepository, CurrencyRepository $currencyRepository)
    {
        $this->addressRepository = $addressRepository;
        $this->currencyRepository = $currencyRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('currencyId', InputArgument::OPTIONAL, '')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \App\Exception\AppException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currencyId = $input->getArgument('currencyId');
        if(is_null($currencyId)){
            $this->addressRepository->enableAll();
        }else{
            /** @var Currency $currency */
            $currency = $this->currencyRepository->findOrException($currencyId);
            $addresses = $this->addressRepository->findByCurrency($currency);
            if($addresses) {
                /** @var Address $address */
                foreach ($addresses as $address) {
                    $address->setEnabled(true);

                    $this->addressRepository->save($address);
                }
            }
        }
    }
}
