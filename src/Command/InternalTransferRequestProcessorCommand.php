<?php

namespace App\Command;

use App\Document\InternalTransferRequest;
use App\Entity\Wallet\InternalTransfer;
use App\Exception\ProcessorLoopException;
use App\Manager\Processor\InternalTransferRequestProcessor;
use App\Manager\Queue\QueueItemManager;
use App\Manager\RedisSubscribeInterface;
use App\Model\InternalTransferRequestModel;
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

class InternalTransferRequestProcessorCommand extends Command
{
    protected static $defaultName = 'app:internal-transfer-request:processor';

    /** @var InternalTransferRequestProcessor */
    private $internalTransferRequestProcessor;

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
     * InternalTransferRequestProcessorCommand constructor.
     * @param InternalTransferRequestProcessor $internalTransferRequestProcessor
     * @param LoggerInterface $logger
     * @param QueueItemManager $queueItemManager
     * @param ParameterBagInterface $parameters
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $em
     */
    public function __construct(InternalTransferRequestProcessor $internalTransferRequestProcessor, LoggerInterface $logger, QueueItemManager $queueItemManager, ParameterBagInterface $parameters, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em)
    {
        $this->internalTransferRequestProcessor = $internalTransferRequestProcessor;
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

        // reset Redis list and load InternalTransferRequests from Mongo to Redis list
        $redisClient->del(RedisSubscribeInterface::INTERNAL_TRANSFER_REQUEST_LIST);
        $internalTransferRequests = $this->queueItemManager->findNotProcessedInternalTransferRequests();
        if($internalTransferRequests){
            /** @var InternalTransferRequest $internalTransferRequest */
            foreach($internalTransferRequests as $internalTransferRequest){
                $internalTransferRequestModel = new InternalTransferRequestModel();
                $internalTransferRequestModel->setInternalTransferRequestId($internalTransferRequest->getId());
                $internalTransferRequestModel->setInternalTransferId($internalTransferRequest->getInternalTransferId());

                $redisClient->lPush(RedisSubscribeInterface::INTERNAL_TRANSFER_REQUEST_LIST, json_encode($internalTransferRequestModel));
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
                #### process internal transfer requests
                ##

                $length = $redisClient->lLen(RedisSubscribeInterface::INTERNAL_TRANSFER_REQUEST_LIST);
                if($length > 0) {
                    for ($i = 1; $i <= $length; $i++) {
                        /** @var InternalTransferRequestModel $internalTransferRequestModel */
                        $internalTransferRequestModel = new InternalTransferRequestModel((array) json_decode($redisClient->rPop(RedisSubscribeInterface::INTERNAL_TRANSFER_REQUEST_LIST)));
                        if(!$internalTransferRequestModel->isValid()) throw new ProcessorLoopException('Loaded empty InternalTransferRequestModel');

                        // set backup item as processed
                        $this->queueItemManager->setInternalTransferRequestProcessed($internalTransferRequestModel->getInternalTransferRequestId());

                        $this->internalTransferRequestProcessor->getInternalTransferRepository()->checkConnection();

                        // process
                        /** @var InternalTransfer $internalTransfer */
                        $internalTransfer = $this->internalTransferRequestProcessor->loadInternalTransfer($internalTransferRequestModel->getInternalTransferId());

                        $this->internalTransferRequestProcessor->process();
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
