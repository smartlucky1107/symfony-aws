<?php

namespace App\Command;

use App\Entity\POS\POSOrder;
use App\Manager\Liquidity\LiquidityTransactionManager;
use App\Manager\POS\POSOrderManager;
use App\Manager\WalletManager;
use App\Repository\POS\POSOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PosOrderProcessorCommand extends Command
{
    protected static $defaultName = 'app:pos-order:processor';

    /** @var POSOrderRepository */
    private $POSOrderRepository;

    /** @var POSOrderManager */
    private $POSOrderManager;

    /** @var LiquidityTransactionManager */
    private $liquidityTransactionManager;

    /** @var WalletManager */
    private $walletManager;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * PosOrderProcessorCommand constructor.
     * @param POSOrderRepository $POSOrderRepository
     * @param POSOrderManager $POSOrderManager
     * @param LiquidityTransactionManager $liquidityTransactionManager
     * @param WalletManager $walletManager
     * @param EntityManagerInterface $em
     */
    public function __construct(POSOrderRepository $POSOrderRepository, POSOrderManager $POSOrderManager, LiquidityTransactionManager $liquidityTransactionManager, WalletManager $walletManager, EntityManagerInterface $em)
    {
        $this->POSOrderRepository = $POSOrderRepository;
        $this->POSOrderManager = $POSOrderManager;
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

                $posOrders = $this->POSOrderRepository->findNewForProcessing();
                if($posOrders){
                    /** @var POSOrder $posOrder */
                    foreach($posOrders as $posOrder){
                        try{
                            $posOrder = $this->POSOrderManager->setProcessing($posOrder);

//                            // create the deposit for POS account
//                            try{
//                                /** @var Deposit $deposit */
//                                $deposit = $this->newDepositManager->placePOSOrderDeposit($posOrder);
//                                if($deposit instanceof Deposit){}
//                            }catch (\Exception $exception){
//                                // do nothing
//                            }
//
//                            // create external market transaction - liquidity balancer
//                            try{
//                                /** @var LiquidityTransaction $liquidityTransaction */
//                                $liquidityTransaction = $this->liquidityTransactionManager->createExternalForCheckout($posOrder);
//                                if($liquidityTransaction instanceof LiquidityTransaction){}
//                            }catch (\Exception $exception){
//                                // do nothing
//                            }
//
//                            try{
//                                $this->walletManager->transferThePOSOrder($posOrder);
//                            }catch (\Exception $exception){
//                                // do nothing
//                            }

                            $this->POSOrderManager->setCompleted($posOrder);
                        }catch (\Exception $exception){
                            // do nothing
                        }
                    }
                }

                $posOrders = null;
                unset($posOrders);

                sleep(1);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                sleep(1);
            }
        }

    }
}
