<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Wallet\Deposit;
use App\Entity\Wallet\Wallet;
use App\Manager\DepositManager;
use App\Repository\UserRepository;
use App\Repository\Wallet\DepositRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DepositSatoshiCommand extends Command
{
    protected static $defaultName = 'app:deposit:satoshi';

    /** @var UserRepository */
    private $userRepository;

    /** @var DepositRepository */
    private $depositRepository;

    /** @var DepositManager */
    private $depositManager;

    /**
     * DepositSatoshiCommand constructor.
     * @param UserRepository $userRepository
     * @param DepositRepository $depositRepository
     * @param DepositManager $depositManager
     */
    public function __construct(UserRepository $userRepository, DepositRepository $depositRepository, DepositManager $depositManager)
    {
        $this->userRepository = $userRepository;
        $this->depositRepository = $depositRepository;
        $this->depositManager = $depositManager;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    /**
     * @param User $user
     */
    private function distribute(User $user){
        $wallets = $user->getWallets();
        if($wallets){
            /** @var Wallet $wallet */
            foreach ($wallets as $wallet){
                try{
                    if($wallet->isBtcWallet()){
                        echo 'Wallet ' . $wallet->getId() . PHP_EOL;

                        $amount = '0.00001000';
                        $transactionDate = '2021-03-15';
                        $transactionId = 'Satoshi Bonus 2021-03-15';

                        /** @var Deposit $deposit */
                        $deposit = $this->depositRepository->findOneBy([
                            'wallet' => $wallet->getId(),
                            'bankTransactionId' => $transactionId
                        ]);
                        if($deposit instanceof Deposit) throw new \Exception('Deposit already exists');

                        /** @var Deposit $deposit */
                        $deposit = new Deposit($wallet, $amount, $user, $transactionDate, $transactionId);
                        $deposit = $this->depositRepository->save($deposit);

                        $this->depositManager->approveForce($deposit);
                        break;
                    }
                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }

                echo '-------'.PHP_EOL;
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userRepository->findVerified();
        if($users){
            /** @var User $user */
            foreach ($users as $user){
                $this->distribute($user);
            }
        }
    }
}
