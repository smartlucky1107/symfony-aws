<?php

namespace App\Command;

use App\Entity\Wallet\Deposit;
use App\Model\PriceInterface;
use App\Repository\Wallet\DepositRepository;
use App\Service\AddressApp\AddressAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AnalyzeDepositsCommand extends Command
{
    protected static $defaultName = 'app:analyze:deposits';

    /** @var DepositRepository */
    private $depositRepository;

    /** @var AddressAppManager */
    private $addressAppManager;

    /**
     * AnalyzeDepositsCommand constructor.
     * @param DepositRepository $depositRepository
     * @param AddressAppManager $addressAppManager
     */
    public function __construct(DepositRepository $depositRepository, AddressAppManager $addressAppManager)
    {
        $this->depositRepository = $depositRepository;
        $this->addressAppManager = $addressAppManager;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }


    /**
     * Convert $amount in wei to decimal
     *
     * @param string $amount
     * @return string|null
     */
    public function fromWei(string $amount)
    {
        $pow = bcpow('10', '18', PriceInterface::BC_SCALE);
        $newAmount = bcdiv($amount, $pow, PriceInterface::BC_SCALE);

        return $newAmount;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $deposits = $this->depositRepository->findAll();
        if($deposits){
            /** @var Deposit $deposit */
            foreach($deposits as $deposit){
                $io->title($deposit->getId());

                $output->writeln('<fg=green> - Deposit found</>');
                $io->text('[id] ' . $deposit->getId());
                $io->text('[status] ' . $deposit->getStatus());
                $io->text('[statusName] ' . $deposit->getStatusName());
                $io->text('[amount] ' . $deposit->toPrecision($deposit->getAmount()));
                $io->text('[blockchainTransactionHash] ' . $deposit->getBlockchainTransactionHash());
                $io->newLine();

                if($deposit->getWallet()->isEthWallet() || $deposit->getWallet()->isErc20Wallet()){
                    $output->writeln('<fg=yellow>Ethereum analyze..</>');

                    $blockchainTx = null;
                    if($deposit->getBlockchainTransactionHash()){
                        $output->writeln('<fg=green> - detected blockchain transaction hash</>');

                        try{
                            $response = (array) $this->addressAppManager->getEthereumTxBlockchainTx($deposit->getBlockchainTransactionHash());
                            if(isset($response['blockchainTx'])){
                                $blockchainTx = (array) $response['blockchainTx'];
                                if(isset($blockchainTx['blockHash']) && isset($blockchainTx['blockNumber']) && isset($blockchainTx['from']) && isset($blockchainTx['hash']) && isset($blockchainTx['to']) && isset($blockchainTx['value'])){
                                    $output->writeln('<fg=green> - BlockchainTx found</>');

                                    $io->text('[blockHash] ' . $blockchainTx['blockHash']);
                                    $io->text('[blockNumber] ' . $blockchainTx['blockNumber']);
                                    $io->text('[from] ' . $blockchainTx['from']);
                                    $io->text('[hash] ' . $blockchainTx['hash']);
                                    $io->text('[to] ' . $blockchainTx['to']);
                                    $io->text('[valueWei] ' . $blockchainTx['value']);
                                    $io->text('[value] ' . $this->fromWei($blockchainTx['value']));

                                    $io->newLine();
                                    $output->writeln('<fg=yellow>Compare values..</>');
                                    $io->text('[Blockchain value] ' . $this->fromWei($blockchainTx['value']));
                                    $io->text('[Deposit value] ' . $deposit->toPrecision($deposit->getAmount()));

                                    $comp = bccomp($this->fromWei($blockchainTx['value']), $deposit->getAmount(), PriceInterface::BC_SCALE);
                                    if($comp === 0){
                                        $io->success('Deposit confirmed with blockchain');
                                    }else{
                                        $io->error('Blockchain value is different than deposit ID ' . $deposit->getId());
                                    }
                                }else{
                                    $output->writeln('<fg=red> - Invalid BlockchainTx</>');
                                }
                            }else{
                                $output->writeln('<fg=red> - BlockchainTx not found</>');
                            }
                        }catch (\Exception $exception){
                            dump($exception->getMessage());
                        }
                    }else{
                        $io->error('blockchain transaction hash not found for deposit ID ' . $deposit->getId());
                    }
                }elseif($deposit->getWallet()->isBtcWallet()){
                    $output->writeln('<fg=yellow>Bitcoin analyze..</>');
                }elseif($deposit->getWallet()->isBchWallet()){
                    $output->writeln('<fg=yellow>Bitcoin Cash analyze..</>');
                }elseif($deposit->getWallet()->isBsvWallet()){
                    $output->writeln('<fg=yellow>Bitcoin SV analyze..</>');
                }else{
                    $output->writeln('<fg=yellow>Fiat deposit</>');
                    $output->writeln('<fg=green>Analysis not required</>');
                }

                $io->newLine();
                $io->writeln('========================================================================================================================');
            }
        }
    }
}
