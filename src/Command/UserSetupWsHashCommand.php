<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserSetupWsHashCommand extends Command
{
    protected static $defaultName = 'app:user:setup-ws-hash';

    /** @var UserRepository */
    private $userRepository;

    /**
     * UserSetupWsHashCommand constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

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
        $users = $this->userRepository->findAll();
        if($users){
            /** @var User $user */
            foreach ($users as $user){
                $user->setWsHash(User::generateWsHash($user->getId()));
                $this->userRepository->save($user);
            }
        }
    }
}
