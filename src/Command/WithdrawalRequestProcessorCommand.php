<?php

namespace App\Command;

use App\Document\WithdrawalRequest;
use App\Entity\Wallet\Withdrawal;
use App\Exception\ProcessorLoopException;
use App\Manager\Processor\WithdrawalRequestProcessor;
use App\Manager\Queue\QueueItemManager;
use App\Manager\RedisSubscribeInterface;
use App\Model\WithdrawalRequestModel;
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

class WithdrawalRequestProcessorCommand extends Command
{
    protected static $defaultName = 'app:withdrawal-request:processor';

    /** @var WithdrawalRequestProcessor */
    private $withdrawalRequestProcessor;

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
     * WithdrawalRequestProcessorCommand constructor.
     * @param WithdrawalRequestProcessor $withdrawalRequestProcessor
     * @param LoggerInterface $logger
     * @param QueueItemManager $queueItemManager
     * @param ParameterBagInterface $parameters
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $em
     */
    public function __construct(WithdrawalRequestProcessor $withdrawalRequestProcessor, LoggerInterface $logger, QueueItemManager $queueItemManager, ParameterBagInterface $parameters, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em)
    {
        $this->withdrawalRequestProcessor = $withdrawalRequestProcessor;
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

        // reset Redis list and load WithdrawalRequests from Mongo to Redis list
        $redisClient->del(RedisSubscribeInterface::WITHDRAWAL_REQUEST_LIST);
        $withdrawalRequests = $this->queueItemManager->findNotProcessedWithdrawalRequests();
        if($withdrawalRequests){
            /** @var WithdrawalRequest $withdrawalRequest */
            foreach($withdrawalRequests as $withdrawalRequest){
                $withdrawalRequestModel = new WithdrawalRequestModel();
                $withdrawalRequestModel->setWithdrawalRequestId($withdrawalRequest->getId());
                $withdrawalRequestModel->setWithdrawalId($withdrawalRequest->getWithdrawalId());

                $redisClient->lPush(RedisSubscribeInterface::WITHDRAWAL_REQUEST_LIST, json_encode($withdrawalRequestModel));
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

                $length = $redisClient->lLen(RedisSubscribeInterface::WITHDRAWAL_REQUEST_LIST);
                if($length > 0) {
                    for ($i = 1; $i <= $length; $i++) {
                        /** @var WithdrawalRequestModel $withdrawalRequestModel */
                        $withdrawalRequestModel = new WithdrawalRequestModel((array) json_decode($redisClient->rPop(RedisSubscribeInterface::WITHDRAWAL_REQUEST_LIST)));
                        if(!$withdrawalRequestModel->isValid()) throw new ProcessorLoopException('Loaded empty WithdrawalRequestModel');

                        // set backup item as processed
                        $this->queueItemManager->setWithdrawalRequestProcessed($withdrawalRequestModel->getWithdrawalRequestId());

                        $this->withdrawalRequestProcessor->getWithdrawalRepository()->checkConnection();

                        // process
                        /** @var Withdrawal $withdrawal */
                        $withdrawal = $this->withdrawalRequestProcessor->loadWithdrawal($withdrawalRequestModel->getWithdrawalId());

                        $this->withdrawalRequestProcessor->process();
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
