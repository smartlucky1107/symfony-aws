<?php

namespace App\Command;

use App\Document\WithdrawalApproveRequest;
use App\Entity\Wallet\Withdrawal;
use App\Exception\ProcessorLoopException;
use App\Manager\Processor\WithdrawalApproveRequestProcessor;
use App\Manager\Queue\QueueItemManager;
use App\Manager\RedisSubscribeInterface;
use App\Model\WithdrawalApproveRequestModel;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WithdrawalApproveRequestProcessorCommand extends Command
{
    protected static $defaultName = 'app:withdrawal-approve-request:processor';

    /** @var WithdrawalApproveRequestProcessor */
    private $withdrawalApproveRequestProcessor;

    /** @var LoggerInterface */
    private $logger;

    /** @var QueueItemManager */
    private $queueItemManager;

    /** @var ParameterBagInterface */
    private $parameters;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * WithdrawalApproveRequestProcessorCommand constructor.
     * @param WithdrawalApproveRequestProcessor $withdrawalApproveRequestProcessor
     * @param LoggerInterface $logger
     * @param QueueItemManager $queueItemManager
     * @param ParameterBagInterface $parameters
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $em
     */
    public function __construct(WithdrawalApproveRequestProcessor $withdrawalApproveRequestProcessor, LoggerInterface $logger, QueueItemManager $queueItemManager, ParameterBagInterface $parameters, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em)
    {
        $this->withdrawalApproveRequestProcessor = $withdrawalApproveRequestProcessor;
        $this->logger = $logger;
        $this->queueItemManager = $queueItemManager;
        $this->parameters = $parameters;
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;

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
        $redisClient = new \Redis();
        $redisClient->connect($this->parameters->get('redis_host'), $this->parameters->get('redis_port'));

        // reset Redis list and load WithdrawalApproveRequests from Mongo to Redis list
        $redisClient->del(RedisSubscribeInterface::WITHDRAWAL_APPROVE_REQUEST_LIST);
        $withdrawalApproveRequests = $this->queueItemManager->findNotProcessedWithdrawalApproveRequests();
        if($withdrawalApproveRequests){
            /** @var WithdrawalApproveRequest $withdrawalApproveRequest */
            foreach($withdrawalApproveRequests as $withdrawalApproveRequest){
                $withdrawalApproveRequestModel = new WithdrawalApproveRequestModel();
                $withdrawalApproveRequestModel->setWithdrawalApproveRequestId($withdrawalApproveRequest->getId());
                $withdrawalApproveRequestModel->setWithdrawalId($withdrawalApproveRequest->getWithdrawalId());

                $redisClient->lPush(RedisSubscribeInterface::WITHDRAWAL_APPROVE_REQUEST_LIST, json_encode($withdrawalApproveRequestModel));
            }
        }

        while(1 == 1) {
            try{
                $this->em->clear();

                var_dump(memory_get_usage(true)/1024/1024);
                echo '---'.PHP_EOL;

                $len = $redisClient->lLen(RedisSubscribeInterface::WALLET_TRANSFER_BATCH_LIST);
                if($len > 0){
                    throw new ProcessorLoopException('Processor is waiting..');
                }

                ############################
                #### process withdrawal requests
                ##

                $length = $redisClient->lLen(RedisSubscribeInterface::WITHDRAWAL_APPROVE_REQUEST_LIST);
                if($length > 0) {
                    for ($i = 1; $i <= $length; $i++) {
                        /** @var WithdrawalApproveRequestModel $withdrawalApproveRequestModel */
                        $withdrawalApproveRequestModel = new WithdrawalApproveRequestModel((array) json_decode($redisClient->rPop(RedisSubscribeInterface::WITHDRAWAL_APPROVE_REQUEST_LIST)));
                        if(!$withdrawalApproveRequestModel->isValid()) throw new ProcessorLoopException('Loaded empty WithdrawalApproveRequestModel');

                        // set backup item as processed
                        $this->queueItemManager->setWithdrawalApproveRequestProcessed($withdrawalApproveRequestModel->getWithdrawalApproveRequestId());

                        $this->withdrawalApproveRequestProcessor->getWithdrawalRepository()->checkConnection();

                        // process
                        /** @var Withdrawal $withdrawal */
                        $withdrawal = $this->withdrawalApproveRequestProcessor->loadWithdrawal($withdrawalApproveRequestModel->getWithdrawalId());

                        $this->withdrawalApproveRequestProcessor->process();
                    }
                }

                sleep(1);
            } catch (\Exception $exception){
                $this->logger->error($exception->getMessage());
                dump($exception->getMessage());
                sleep(1);
            }
        }
    }
}
