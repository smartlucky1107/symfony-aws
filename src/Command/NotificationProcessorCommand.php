<?php

namespace App\Command;

use App\Event\WSPushEvent;
use App\Event\WSPushNotificationEvent;
use App\Manager\NotificationManager;
use App\Manager\RedisSubscribeInterface;
use App\Manager\RedisProvider;
use App\Model\WS\WSPushRequest;
use App\Server\AppWebsocketInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NotificationProcessorCommand extends Command
{
    protected static $defaultName = 'app:notification:processor';

    /** @var RedisProvider */
    private $redisProvider;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var ParameterBagInterface */
    private $parameters;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * NotificationProcessorCommand constructor.
     * @param RedisProvider $redisProvider
     * @param NotificationManager $notificationManager
     * @param ParameterBagInterface $parameters
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(RedisProvider $redisProvider, NotificationManager $notificationManager, ParameterBagInterface $parameters, EventDispatcherInterface $eventDispatcher)
    {
        $this->redisProvider = $redisProvider;
        $this->notificationManager = $notificationManager;
        $this->parameters = $parameters;
        $this->eventDispatcher = $eventDispatcher;

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
        $redis = $this->redisProvider->getRedis();
        $redisClient = new \Redis();
        $redisClient->connect($this->parameters->get('redis_host'), $this->parameters->get('redis_port'));

        $subscribers = $redisClient->pubsub('numsub', [RedisSubscribeInterface::NOTIFICATIONS_SUBSCRIBE_CHANEL]);
        if(isset($subscribers[RedisSubscribeInterface::NOTIFICATIONS_SUBSCRIBE_CHANEL])){
            if($subscribers[RedisSubscribeInterface::NOTIFICATIONS_SUBSCRIBE_CHANEL] > 0){
                echo 'Processor already running'.PHP_EOL;
                return false;
            }
        }

        ini_set('default_socket_timeout', -1);
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $redis->subscribe([RedisSubscribeInterface::NOTIFICATIONS_SUBSCRIBE_CHANEL], function(\Redis $redis, $chan, $msg) use ($redisClient){
            try{
                $length = $redisClient->lLen(RedisSubscribeInterface::NOTIFICATION_LIST);
                if($length > 0){
                    for ($i = 1; $i <= $length; $i++) {
                        $notification = json_decode($redisClient->lPop(RedisSubscribeInterface::NOTIFICATION_LIST));

                        $this->eventDispatcher->dispatch(WSPushNotificationEvent::NAME, new WSPushNotificationEvent($notification));
                    }
                }
            } catch (\Exception $exception){

            }
        });
    }
}
