<?php

namespace App\Command;

use App\Document\Blockchain\BitcoinSvTx;
use App\Document\Blockchain\BitcoinTx;
use App\Document\Blockchain\BitcoinCashTx;
use App\Document\Blockchain\EthereumTx;
use App\Manager\Blockchain\TxManager;
use App\Manager\Processor\BitcoinCashTxProcessor;
use App\Manager\Processor\BitcoinSvTxProcessor;
use App\Manager\Processor\BitcoinTxProcessor;
use App\Manager\Processor\EthereumTxProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BlockchainTxProcessorCommand extends Command
{
    protected static $defaultName = 'app:blockchain-tx:processor';

    /** @var LoggerInterface */
    private $logger;

    /** @var BitcoinTxProcessor */
    private $bitcoinTxProcessor;

    /** @var BitcoinCashTxProcessor */
    private $bitcoinCashTxProcessor;

    /** @var BitcoinSvTxProcessor */
    private $bitcoinSvTxProcessor;

    /** @var EthereumTxProcessor */
    private $ethereumTxProcessor;

    /** @var TxManager */
    private $txManager;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * BlockchainTxProcessorCommand constructor.
     * @param LoggerInterface $logger
     * @param BitcoinTxProcessor $bitcoinTxProcessor
     * @param BitcoinCashTxProcessor $bitcoinCashTxProcessor
     * @param BitcoinSvTxProcessor $bitcoinSvTxProcessor
     * @param EthereumTxProcessor $ethereumTxProcessor
     * @param TxManager $txManager
     * @param EntityManagerInterface $em
     */
    public function __construct(LoggerInterface $logger, BitcoinTxProcessor $bitcoinTxProcessor, BitcoinCashTxProcessor $bitcoinCashTxProcessor, BitcoinSvTxProcessor $bitcoinSvTxProcessor, EthereumTxProcessor $ethereumTxProcessor, TxManager $txManager, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->bitcoinTxProcessor = $bitcoinTxProcessor;
        $this->bitcoinCashTxProcessor = $bitcoinCashTxProcessor;
        $this->bitcoinSvTxProcessor = $bitcoinSvTxProcessor;
        $this->ethereumTxProcessor = $ethereumTxProcessor;
        $this->txManager = $txManager;
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
            try{
                $this->em->clear();
                $this->txManager->dmClear();

                ##########
                ######
                ## TODO zrobić automatyczne sprawdzanie w Blockchain, które BitcoinTxs, BitcoinCashTxs, BitcoinSvTxs i EthereumTxs są confirmed
                ######
                ##########

                $bitcoinTxs = $this->txManager->findConfirmedNotProcessedBitcoinTxs();
                if($bitcoinTxs){
                    /** @var BitcoinTx $bitcoinTx */
                    foreach($bitcoinTxs as $bitcoinTx){
                        $bitcoinTx = $this->txManager->setBitcoinTxProcessed($bitcoinTx);
                        $this->bitcoinTxProcessor->processDeposits($bitcoinTx);
                    }
                }

//                $bitcoinCashTxs = $this->txManager->findConfirmedNotProcessedBitcoinCashTxs();
//                if($bitcoinCashTxs){
//                    /** @var BitcoinCashTx $bitcoinCashTx */
//                    foreach($bitcoinCashTxs as $bitcoinCashTx){
//                        $bitcoinCashTx = $this->txManager->setBitcoinCashTxProcessed($bitcoinCashTx);
//                        $this->bitcoinCashTxProcessor->processDeposits($bitcoinCashTx);
//                    }
//                }
//
//                $bitcoinSvTxs = $this->txManager->findConfirmedNotProcessedBitcoinSvTxs();
//                if($bitcoinSvTxs){
//                    /** @var BitcoinSvTx $bitcoinSvTx */
//                    foreach($bitcoinSvTxs as $bitcoinSvTx){
//                        $bitcoinSvTx = $this->txManager->setBitcoinSvTxProcessed($bitcoinSvTx);
//                        $this->bitcoinSvTxProcessor->processDeposits($bitcoinSvTx);
//                    }
//                }

                $ethereumTxs = $this->txManager->findConfirmedNotProcessedEthereumTxs();
                if($ethereumTxs){
                    /** @var EthereumTx $ethereumTx */
                    foreach($ethereumTxs as $ethereumTx){
                        $ethereumTx = $this->txManager->setEthereumTxProcessed($ethereumTx);
                        $this->ethereumTxProcessor->processDeposits($ethereumTx);
                    }
                }

                sleep(1);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                $this->logger->error($exception->getMessage());
                sleep(1);
            }
        }
    }
}
