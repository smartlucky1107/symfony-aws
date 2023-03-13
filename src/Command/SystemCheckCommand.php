<?php

namespace App\Command;

use App\DataTransformer\UserTransformer;
use App\Entity\User;
use App\Manager\UserManager;
use App\Model\SystemUserInterface;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SystemCheckCommand extends Command
{
    protected static $defaultName = 'app:system:check';

    /** @var UserRepository */
    private $userRepository;

    /** @var WalletRepository */
    private $walletRepository;

    /** @var UserTransformer */
    private $userTransformer;

    /** @var UserManager */
    private $userManager;

    /**
     * SystemCheckCommand constructor.
     * @param UserRepository $userRepository
     * @param WalletRepository $walletRepository
     * @param UserTransformer $userTransformer
     * @param UserManager $userManager
     */
    public function __construct(UserRepository $userRepository, WalletRepository $walletRepository, UserTransformer $userTransformer, UserManager $userManager)
    {
        $this->userRepository = $userRepository;
        $this->walletRepository = $walletRepository;
        $this->userTransformer = $userTransformer;
        $this->userManager = $userManager;

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
        $io = new SymfonyStyle($input, $output);
        $io->note('Verify system users');

        /** @var User $feeUser */
        $feeUser = $this->userRepository->find(SystemUserInterface::FEE_USER);
        if($feeUser instanceof User){
            $io->success(sprintf('[%s] Fee user exists', SystemUserInterface::FEE_USER));
        }else{
            $io->warning(sprintf('[%s] Fee user does not exist', SystemUserInterface::FEE_USER));

            // create

        }

        /** @var User $checkoutFeeUser */
        $checkoutFeeUser = $this->userRepository->find(SystemUserInterface::CHECKOUT_FEE_USER);
        if($checkoutFeeUser instanceof User){
            $io->success(sprintf('[%s] Checkout fee user exists', SystemUserInterface::CHECKOUT_FEE_USER));
        }else{
            $io->warning(sprintf('[%s] Checkout fee user does not exist', SystemUserInterface::CHECKOUT_FEE_USER));

            // create

        }

        /** @var User $checkoutLiqUser */
        $checkoutLiqUser = $this->userRepository->find(SystemUserInterface::CHECKOUT_LIQ_USER);
        if($checkoutLiqUser instanceof User){
            $io->success(sprintf('[%s] Checkout liq user exists', SystemUserInterface::CHECKOUT_LIQ_USER));
        }else{
            $io->warning(sprintf('[%s] Checkout liq user does not exist', SystemUserInterface::CHECKOUT_LIQ_USER));

            // create

        }

        /** @var User $bitbayLiqUser */
        $bitbayLiqUser = $this->userRepository->find(SystemUserInterface::BITBAY_LIQ_USER);
        if($bitbayLiqUser instanceof User){
            $io->success(sprintf('[%s] Bitbay liq user exists', SystemUserInterface::BITBAY_LIQ_USER));
        }else{
            $io->warning(sprintf('[%s] Bitbay liq user does not exist', SystemUserInterface::BITBAY_LIQ_USER));

            // create

        }

        /** @var User $binanceLiqUser */
        $binanceLiqUser = $this->userRepository->find(SystemUserInterface::BINANCE_LIQ_USER);
        if($binanceLiqUser instanceof User){
            $io->success(sprintf('[%s] Binance liq user exists', SystemUserInterface::BINANCE_LIQ_USER));
        }else{
            $io->warning(sprintf('[%s] Binance liq user does not exist', SystemUserInterface::BINANCE_LIQ_USER));

            // create

        }

        /** @var User $krakenLiqUser */
        $krakenLiqUser = $this->userRepository->find(SystemUserInterface::KRAKEN_LIQ_USER);
        if($krakenLiqUser instanceof User){
            $io->success(sprintf('[%s] Kraken liq user exists', SystemUserInterface::KRAKEN_LIQ_USER));
        }else{
            $io->warning(sprintf('[%s] Kraken liq user does not exist', SystemUserInterface::KRAKEN_LIQ_USER));

            // create

        }

        /** @var User $walutomatLiqUser */
        $walutomatLiqUser = $this->userRepository->find(SystemUserInterface::WALUTOMAT_LIQ_USER);
        if($walutomatLiqUser instanceof User){
            $io->success(sprintf('[%s] Walutomat liq user exists', SystemUserInterface::WALUTOMAT_LIQ_USER));
        }else{
            $io->warning(sprintf('[%s] Walutomat liq user does not exist', SystemUserInterface::WALUTOMAT_LIQ_USER));

            // create

        }



//        $io = new SymfonyStyle($input, $output);
//        $arg1 = $input->getArgument('arg1');
//
//        if ($arg1) {
//            $io->note(sprintf('You passed an argument: %s', $arg1));
//        }
//
//        if ($input->getOption('option1')) {
//            // ...
//        }
//
//        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
