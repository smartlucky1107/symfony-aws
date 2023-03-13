<?php

namespace App\Command;

use App\Entity\CheckoutOrder;
use App\Entity\Liquidity\LiquidityTransaction;
use App\Entity\Wallet\Deposit;
use App\Manager\CheckoutOrderManager;
use App\Manager\Liquidity\LiquidityTransactionManager;
use App\Manager\NewDepositManager;
use App\Manager\WalletManager;
use App\Repository\CheckoutOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckoutOrderProcessorCommand extends Command
{
    protected static $defaultName = 'app:checkout-order:processor';

    /** @var CheckoutOrderRepository */
    private $checkoutOrderRepository;

    /** @var CheckoutOrderManager */
    private $checkoutOrderManager;

    /** @var NewDepositManager */
    private $newDepositManager;

    /** @var LiquidityTransactionManager */
    private $liquidityTransactionManager;

    /** @var WalletManager */
    private $walletManager;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * CheckoutOrderProcessorCommand constructor.
     * @param CheckoutOrderRepository $checkoutOrderRepository
     * @param CheckoutOrderManager $checkoutOrderManager
     * @param NewDepositManager $newDepositManager
     * @param LiquidityTransactionManager $liquidityTransactionManager
     * @param WalletManager $walletManager
     * @param EntityManagerInterface $em
     */
    public function __construct(CheckoutOrderRepository $checkoutOrderRepository, CheckoutOrderManager $checkoutOrderManager, NewDepositManager $newDepositManager, LiquidityTransactionManager $liquidityTransactionManager, WalletManager $walletManager, EntityManagerInterface $em)
    {
        $this->checkoutOrderRepository = $checkoutOrderRepository;
        $this->checkoutOrderManager = $checkoutOrderManager;
        $this->newDepositManager = $newDepositManager;
        $this->liquidityTransactionManager = $liquidityTransactionManager;
        $this->walletManager = $walletManager;
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

                $paidCheckoutOrders = $this->checkoutOrderRepository->findPaidForProcessing();
                if($paidCheckoutOrders){
                    /** @var CheckoutOrder $checkoutOrder */
                    foreach($paidCheckoutOrders as $checkoutOrder){
                        try{
                            $checkoutOrder = $this->checkoutOrderManager->setProcessing($checkoutOrder);

                            // create the deposit for checkout account
                            try{
                                /** @var Deposit $deposit */
                                $deposit = $this->newDepositManager->placeCheckoutOrderDeposit($checkoutOrder);
                                if($deposit instanceof Deposit){}
                            }catch (\Exception $exception){
                                // do nothing
                            }

                            // create external market transaction - liquidity balancer
                            try{
                                /** @var LiquidityTransaction $liquidityTransaction */
                                $liquidityTransaction = $this->liquidityTransactionManager->createExternalForCheckout($checkoutOrder);
                                if($liquidityTransaction instanceof LiquidityTransaction){}
                            }catch (\Exception $exception){
                                // do nothing
                            }

                            try{
                                $this->walletManager->transferTheCheckoutOrder($checkoutOrder);
                            }catch (\Exception $exception){
                                // do nothing
                            }

                            $this->checkoutOrderManager->setCompleted($checkoutOrder);
                        }catch (\Exception $exception){
                            // do nothing
                        }
                    }
                }

                $paidCheckoutOrders = null;
                unset($paidCheckoutOrders);

                sleep(1);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                sleep(1);
            }
        }

//        $io = new SymfonyStyle($input, $output);
//        $arg1 = $input->getArgument('arg1');
//
//        if ($arg1) {
//            $io->note(sprintf('You passed an argument: %s', $arg1));
//        }
//
//        if ($input->getOption('option1')) {
//            // ...
//        }
//
//        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
