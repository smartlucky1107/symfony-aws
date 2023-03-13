<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\User;
use App\Manager\AddressManager;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserWalletBtcGenerateAllCommand extends Command
{
    protected static $defaultName = 'user:wallet:btc-generate-all';

    /** @var UserRepository */
    private $userRepository;

    /** @var AddressManager */
    private $addressManager;

    /**
     * UserWalletGenerateAllCommand constructor.
     * @param UserRepository $userRepository
     * @param AddressManager $addressManager
     */
    public function __construct(UserRepository $userRepository, AddressManager $addressManager)
    {
        $this->userRepository = $userRepository;
        $this->addressManager = $addressManager;

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
                foreach ($user->getWallets() as $wallet){
                    if($wallet->isBtcWallet()){
                        echo 'Generate for '.$user->getEmail();

                        $address = $this->addressManager->generate($wallet);
                        if($address instanceof Address){
                            echo $address->getAddress().PHP_EOL;;
                        }

                        echo PHP_EOL;

                        break;
                    }
                }

                $user = null;
                unset($user);

                echo '---- Success ---- '.PHP_EOL.PHP_EOL;
            }
        }
    }
}
