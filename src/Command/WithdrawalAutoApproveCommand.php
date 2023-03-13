<?php

namespace App\Command;

use App\Entity\Wallet\Withdrawal;
use App\Manager\WithdrawalManager;
use App\Repository\Wallet\WithdrawalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WithdrawalAutoApproveCommand extends Command
{
    protected static $defaultName = 'app:withdrawal:auto-approve';

    /** @var EntityManagerInterface */
    private $em;

    /** @var WithdrawalManager */
    private $withdrawalManager;

    /** @var WithdrawalRepository */
    private $withdrawalRepository;

    /**
     * WithdrawalAutoApproveCommand constructor.
     * @param EntityManagerInterface $em
     * @param WithdrawalManager $withdrawalManager
     * @param WithdrawalRepository $withdrawalRepository
     */
    public function __construct(EntityManagerInterface $em, WithdrawalManager $withdrawalManager, WithdrawalRepository $withdrawalRepository)
    {
        $this->em = $em;
        $this->withdrawalManager = $withdrawalManager;
        $this->withdrawalRepository = $withdrawalRepository;

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

                $withdrawals = $this->withdrawalRepository->findForAutoExternalApproval();
                if($withdrawals){
                    /** @var Withdrawal $withdrawal */
                    foreach ($withdrawals as $withdrawal){
                        try{
                            if($withdrawal->getWallet()->isBtcWallet()){
                                $amount = $withdrawal->getAmount();
                                $maxAmount = 0.1;

                                $comp = bccomp($amount, $maxAmount, 18);
                                if($comp === -1){
                                    $this->withdrawalManager->sendForExternalApproval($withdrawal);
                                    dump('SET FOR EXTERNAL APPROVE' . $withdrawal->getId());
                                }
                            }
                        }catch (\Exception $exception){
                            dump($exception->getMessage());
                        }
                    }
                }

                $withdrawals = null;
                unset($withdrawals);

                sleep(1);
            } catch (\Exception $exception){
                dump($exception->getMessage());
                sleep(1);
            }
        }
    }
}
