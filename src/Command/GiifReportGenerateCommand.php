<?php

namespace App\Command;

use App\Entity\GiifReport;
use App\Entity\User;
use App\Manager\GiifManager;
use App\Repository\UserRepository;
use App\Repository\Wallet\DepositRepository;
use App\Repository\Wallet\WithdrawalRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GiifReportGenerateCommand extends Command
{
    protected static $defaultName = 'app:giif-report:generate';

    /** @var UserRepository */
    private $userRepository;

    /** @var DepositRepository */
    private $depositRepository;

    /** @var WithdrawalRepository */
    private $withdrawalRepository;

    /** @var GiifManager */
    private $giifManager;

    /**
     * GiifReportGenerateCommand constructor.
     * @param UserRepository $userRepository
     * @param DepositRepository $depositRepository
     * @param WithdrawalRepository $withdrawalRepository
     * @param GiifManager $giifManager
     */
    public function __construct(UserRepository $userRepository, DepositRepository $depositRepository, WithdrawalRepository $withdrawalRepository, GiifManager $giifManager)
    {
        $this->userRepository = $userRepository;
        $this->depositRepository = $depositRepository;
        $this->withdrawalRepository = $withdrawalRepository;
        $this->giifManager = $giifManager;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('')
            ->addArgument('userId', InputArgument::REQUIRED, '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('userId');

        /** @var User $user */
        $user = $this->userRepository->findOrException($userId);

        $deposits = $this->depositRepository->findForGiifReportByUser($user);
        $withdrawals = $this->withdrawalRepository->findForGiifReportByUser($user);

        /** @var GiifReport $giifReport */
        $giifReport = $this->giifManager->generateReport($user, $deposits, $withdrawals);
        if($giifReport instanceof GiifReport){
            dump($giifReport->getId());
        }
    }
}
