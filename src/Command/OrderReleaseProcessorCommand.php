<?php

namespace App\Command;

use App\Entity\OrderBook\Order;
use App\Manager\NewOrderManager;
use App\Repository\OrderBook\OrderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OrderReleaseProcessorCommand extends Command
{
    protected static $defaultName = 'app:order:release-processor';

    /** @var OrderRepository */
    private $orderRepository;

    /** @var NewOrderManager */
    private $newOrderManager;

    /**
     * OrderReleaseProcessorCommand constructor.
     * @param OrderRepository $orderRepository
     * @param NewOrderManager $newOrderManager
     */
    public function __construct(OrderRepository $orderRepository, NewOrderManager $newOrderManager)
    {
        $this->orderRepository = $orderRepository;
        $this->newOrderManager = $newOrderManager;

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
        $orders = $this->orderRepository->findRejectedForRelease();
        if($orders){
            /** @var Order $order */
            foreach($orders as $order){
                $this->newOrderManager->releaseBlockedAmount($order);
                echo $order->getId().PHP_EOL;
            }
        }
    }
}
