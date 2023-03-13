<?php

namespace App\Command;

use App\Manager\RedisProvider;
use App\Manager\RedisSubscribeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TradingRunCommand extends Command
{
    protected static $defaultName = 'app:trading:run';

    /** @var RedisProvider */
    private $redisProvider;

    /** @var ParameterBagInterface */
    private $parameters;

    /**
     * @param RedisProvider $redisProvider
     * @param ParameterBagInterface $parameters
     */
    public function __construct(RedisProvider $redisProvider, ParameterBagInterface $parameters)
    {
        $this->redisProvider = $redisProvider;
        $this->parameters = $parameters;

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

        $redisClient->publish(RedisSubscribeInterface::TRADING_SUBSCRIBE_CHANEL, '[]');
    }
}
