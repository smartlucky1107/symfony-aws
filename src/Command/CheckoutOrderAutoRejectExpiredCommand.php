<?php

namespace App\Command;

use App\Entity\CheckoutOrder;
use App\Manager\CheckoutOrderManager;
use App\Repository\CheckoutOrderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckoutOrderAutoRejectExpiredCommand extends Command
{
    protected static $defaultName = 'app:checkout-order:auto-reject-expired';

    /** @var CheckoutOrderRepository */
    private $checkoutOrderRepository;

    /** @var CheckoutOrderManager */
    private $checkoutOrderManager;

    /**
     * CheckoutOrderAutoRejectExpiredCommand constructor.
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param CheckoutOrderManager $checkoutOrderManager
     */
    public function __construct(CheckoutOrderRepository $checkoutOrderRepository, CheckoutOrderManager $checkoutOrderManager)
    {
        $this->checkoutOrderRepository = $checkoutOrderRepository;
        $this->checkoutOrderManager = $checkoutOrderManager;

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
        $checkoutOrdersExpired = $this->checkoutOrderRepository->findNewExpired();
        if($checkoutOrdersExpired){
            /** @var CheckoutOrder $checkoutOrder */
            foreach ($checkoutOrdersExpired as $checkoutOrder){
                try{
                    if($checkoutOrder->isExpired()) $this->checkoutOrderManager->reject($checkoutOrder);
                }catch (\Exception $exception){
                    dump($exception);
                }
            }
        }
    }
}
