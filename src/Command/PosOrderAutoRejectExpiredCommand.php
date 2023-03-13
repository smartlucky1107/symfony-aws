<?php

namespace App\Command;

use App\Entity\POS\POSOrder;
use App\Manager\POS\POSOrderManager;
use App\Repository\POS\POSOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PosOrderAutoRejectExpiredCommand extends Command
{
    protected static $defaultName = 'app:pos-order:auto-reject-expired';

    /** @var POSOrderRepository */
    private $POSOrderRepository;

    /** @var POSOrderManager */
    private $POSOrderManager;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * PosOrderAutoRejectExpiredCommand constructor.
     * @param POSOrderRepository $POSOrderRepository
     * @param POSOrderManager $POSOrderManager
     * @param EntityManagerInterface $em
     */
    public function __construct(POSOrderRepository $POSOrderRepository, POSOrderManager $POSOrderManager, EntityManagerInterface $em)
    {
        $this->POSOrderRepository = $POSOrderRepository;
        $this->POSOrderManager = $POSOrderManager;
        $this->em = $em;

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
        while(1 == 1) {
            try {
                $this->em->clear();

                $ordersExpired = $this->POSOrderRepository->findInitiatedExpired();
                if($ordersExpired){
                    /** @var POSOrder $POSOrder */
                    foreach ($ordersExpired as $POSOrder){
                        try{
                            if($POSOrder->isExpired()) $this->POSOrderManager->reject($POSOrder);
                        }catch (\Exception $exception){
                            dump($exception);
                        }
                    }
                }

                $ordersExpired = null;
                unset($ordersExpired);

                sleep(1);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                sleep(1);
            }
        }
    }
}
