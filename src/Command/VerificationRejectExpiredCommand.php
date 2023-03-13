<?php

namespace App\Command;

use App\Entity\Verification;
use App\Manager\VerificationManager;
use App\Repository\VerificationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VerificationRejectExpiredCommand extends Command
{
    protected static $defaultName = 'app:verification:reject-expired';

    /** @var VerificationRepository */
    private $verificationRepository;

    /** @var VerificationManager */
    private $verificationManager;

    /**
     * @param VerificationRepository $verificationRepository
     * @param VerificationManager $verificationManager
     */
    public function __construct(VerificationRepository $verificationRepository, VerificationManager $verificationManager)
    {
        $this->verificationRepository = $verificationRepository;
        $this->verificationManager = $verificationManager;

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
        $verifications = $this->verificationRepository->findBy(['status' => Verification::STATUS_NEW]);
        if($verifications){
            foreach($verifications as $verification){
                try{
                    $this->verificationManager->updateExpiredStatus($verification);
                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }
            }
        }
    }
}
