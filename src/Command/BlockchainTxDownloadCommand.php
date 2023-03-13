<?php

namespace App\Command;

use App\DataTransformer\BitcoinTxTransformer;
use App\DataTransformer\BitcoinCashTxTransformer;
use App\DataTransformer\BitcoinSvTxTransformer;
use App\DataTransformer\EthereumTxTransformer;
use App\Document\Blockchain\BitcoinTx;
use App\Document\Blockchain\BitcoinCashTx;
use App\Document\Blockchain\BitcoinSvTx;
use App\Document\Blockchain\EthereumTx;
use App\Exception\AppException;
use App\Manager\Blockchain\TxManager;
use App\Service\AddressApp\AddressAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BlockchainTxDownloadCommand extends Command
{
    protected static $defaultName = 'app:blockchain-tx:download';

    /** @var AddressAppManager */
    private $addressAppManager;

    /** @var BitcoinTxTransformer */
    private $bitcoinTxTransformer;

    /** @var BitcoinCashTxTransformer */
    private $bitcoinCashTxTransformer;

    /** @var BitcoinSvTxTransformer */
    private $bitcoinSvTxTransformer;

    /** @var EthereumTxTransformer */
    private $ethereumTxTransformer;

    /** @var TxManager */
    private $txManager;

    /**
     * BlockchainTxDownloadCommand constructor.
     * @param AddressAppManager $addressAppManager
     * @param BitcoinTxTransformer $bitcoinTxTransformer
     * @param BitcoinCashTxTransformer $bitcoinCashTxTransformer
     * @param BitcoinSvTxTransformer $bitcoinSvTxTransformer
     * @param EthereumTxTransformer $ethereumTxTransformer
     * @param TxManager $txManager
     */
    public function __construct(AddressAppManager $addressAppManager, BitcoinTxTransformer $bitcoinTxTransformer, BitcoinCashTxTransformer $bitcoinCashTxTransformer, BitcoinSvTxTransformer $bitcoinSvTxTransformer, EthereumTxTransformer $ethereumTxTransformer, TxManager $txManager)
    {
        $this->addressAppManager = $addressAppManager;
        $this->bitcoinTxTransformer = $bitcoinTxTransformer;
        $this->bitcoinCashTxTransformer = $bitcoinCashTxTransformer;
        $this->bitcoinSvTxTransformer = $bitcoinSvTxTransformer;
        $this->ethereumTxTransformer = $ethereumTxTransformer;
        $this->txManager = $txManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \App\Exception\ApiConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $response = (array) $this->addressAppManager->downloadBitcoinTxs();
        if(isset($response['txs'])){
            foreach($response['txs'] as $tx){
                try{
                    /** @var BitcoinTx $bitcoinTx */
                    $bitcoinTx = $this->bitcoinTxTransformer->transformFromArray((array) $tx);
                    $this->txManager->save($bitcoinTx);
                }catch (\Exception $exception){
                    //$output->writeln('<error>'.'Cannot save transaction '.json_encode($tx).'</error>');
                    $output->writeln('<error>'.$exception->getMessage().'</error>');
                    $output->writeln('');
                }
            }
        }

//        $response = (array) $this->addressAppManager->downloadBitcoinCashTxs();
//        if(isset($response['txs'])){
//            foreach($response['txs'] as $tx){
//                try{
//                    /** @var BitcoinCashTx $bitcoinCashTx */
//                    $bitcoinCashTx = $this->bitcoinCashTxTransformer->transformFromArray((array) $tx);
//                    $this->txManager->save($bitcoinCashTx);
//                }catch (\Exception $exception){
//                    //$output->writeln('<error>'.'Cannot save transaction '.json_encode($tx).'</error>');
//                    $output->writeln('<error>'.$exception->getMessage().'</error>');
//                    $output->writeln('');
//                }
//            }
//        }
//
//        $response = (array) $this->addressAppManager->downloadBitcoinSvTxs();
//        if(isset($response['txs'])){
//            foreach($response['txs'] as $tx){
//                try{
//                    /** @var BitcoinSvTx $bitcoinSvTx */
//                    $bitcoinSvTx = $this->bitcoinSvTxTransformer->transformFromArray((array) $tx);
//                    $this->txManager->save($bitcoinSvTx);
//                }catch (\Exception $exception){
//                    //$output->writeln('<error>'.'Cannot save transaction '.json_encode($tx).'</error>');
//                    $output->writeln('<error>'.$exception->getMessage().'</error>');
//                    $output->writeln('');
//                }
//            }
//        }

        $response = (array) $this->addressAppManager->downloadEthereumTxs();
        if(isset($response['txs'])) {
            foreach ($response['txs'] as $tx) {
                try{
                    /** @var EthereumTx $ethereumTx */
                    $ethereumTx = $this->ethereumTxTransformer->transformFromArray((array) $tx);
                    $this->txManager->save($ethereumTx);
                }catch (\Exception $exception){
                    //$output->writeln('<error>'.'Cannot save transaction '.json_encode($tx).'</error>');
                    $output->writeln('<error>'.$exception->getMessage().'</error>');
                    $output->writeln('');
                }
            }
        }
    }
}
