<?php

namespace App\Command;

use App\Entity\Wallet\Withdrawal;
use App\Manager\WithdrawalManager;
use App\Repository\Wallet\WithdrawalRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WithdrawalRequestAutoRejectCommand extends Command
{
    protected static $defaultName = 'app:withdrawal-request:auto-reject';

    /** @var WithdrawalRepository */
    private $withdrawalRepository;

    /** @var WithdrawalManager */
    private $withdrawalManager;

    /**
     * WithdrawalRequestAutoRejectCommand constructor.
     * @param WithdrawalRepository $withdrawalRepository
     * @param WithdrawalManager $withdrawalManager
     */
    public function __construct(WithdrawalRepository $withdrawalRepository, WithdrawalManager $withdrawalManager)
    {
        $this->withdrawalRepository = $withdrawalRepository;
        $this->withdrawalManager = $withdrawalManager;

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
        $withdrawalsExpired = $this->withdrawalRepository->findNewExpired();
        if($withdrawalsExpired){
            /** @var Withdrawal $withdrawal */
            foreach($withdrawalsExpired as $withdrawal){
                try{
                    $this->withdrawalManager->reject($withdrawal);
                }catch (\Exception $exception){
                    dump($exception);
                }
            }
        }
    }
}
