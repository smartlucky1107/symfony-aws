<?php

namespace App\Command;

use App\Server\AppWebsocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WebsocketAppCommand extends Command
{
    protected static $defaultName = 'app:websocket:app';

    /** @var ParameterBagInterface */
    private $parameters;

    /**
     * WebsocketAppCommand constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
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
        $server = IoServer::factory(new HttpServer(
            new WsServer(
                new AppWebsocket()
            )
        ), $this->parameters->get('websocket_port'));

        $server->run();
    }
}
