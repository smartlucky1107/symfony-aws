<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\WalletGenerator;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserWalletGenerateAllCommand extends Command
{
    protected static $defaultName = 'user:wallet:generate-all';

    /** @var UserRepository */
    private $userRepository;

    /** @var WalletGenerator */
    private $walletGenerator;

    /**
     * UserWalletGenerateAllCommand constructor.
     * @param UserRepository $userRepository
     * @param WalletGenerator $walletGenerator
     */
    public function __construct(UserRepository $userRepository, WalletGenerator $walletGenerator)
    {
        $this->userRepository = $userRepository;
        $this->walletGenerator = $walletGenerator;

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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userRepository->findAll();
        if($users){
            /** @var User $user */
            foreach ($users as $user){
                echo 'Generate for '.$user->getEmail().PHP_EOL;
                $this->walletGenerator->generateForUser($user);

                $user = null;
                unset($user);

                echo '---- Success ---- '.PHP_EOL.PHP_EOL;
            }
        }
    }
}
