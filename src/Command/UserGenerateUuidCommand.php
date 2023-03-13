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
use Ramsey\Uuid\Uuid;

class UserGenerateUuidCommand extends Command
{
    protected static $defaultName = 'app:user:generate-uuid';

    /** @var UserRepository */
    private $userRepository;

    /**
     * UserGenerateUuidCommand constructor.
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
                if(!is_null($user->getUuid())) continue;

                $user->setUuid(Uuid::uuid4()->toString());

                $this->userRepository->save($user);

                $user = null;
                unset($user);
            }
        }
    }
}
