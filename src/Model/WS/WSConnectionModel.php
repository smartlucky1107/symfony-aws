<?php

namespace App\Model\WS;

use App\Server\AppWebsocketInterface;
use Ratchet\ConnectionInterface;

class WSConnectionModel
{
    /** @var ConnectionInterface */
    private $connection;

    /** @var array array */
    private $modules = [];

    /**
     * WSConnectionModel constructor.
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Add subscribed module $wsModule to the connection
     *
     * @param WSModuleInterface $wsModule
     */
    public function addModule(WSModuleInterface $wsModule){
        $exists = false;

        /** @var WSModuleInterface $module */
        foreach($this->modules as $module){
            if($wsModule instanceof WSModuleOrderbook && $module instanceof WSModuleOrderbook){
                if($module->isMatched($wsModule->getCurrencyPairShortName())){
                    $exists = true;
                    break;
                }
            }elseif($wsModule instanceof WSModuleExternalOrderbook && $module instanceof WSModuleExternalOrderbook){
                if($module->isMatched($wsModule->getCurrencyPairShortName())){
                    $exists = true;
                    break;
                }
            }elseif($wsModule instanceof WSModuleNotification && $module instanceof WSModuleNotification){
                if($module->isMatched($wsModule->getUserId())){
                    $exists = true;
                    break;
                }
            }
        }

        if(!$exists){
            $this->modules[] = $wsModule;
        }
    }

    /**
     * @param WSModuleInterface $wsModule
     */
    public function removeModule(WSModuleInterface $wsModule){
        /** @var WSModuleInterface $module */
        foreach($this->modules as $key => $module){
            if($wsModule instanceof WSModuleOrderbook && $module instanceof WSModuleOrderbook){
                if($module->isMatched($wsModule->getCurrencyPairShortName())){
                    unset($this->modules[$key]);
                    break;
                }
            }elseif($wsModule instanceof WSModuleExternalOrderbook && $module instanceof WSModuleExternalOrderbook){
                if($module->isMatched($wsModule->getCurrencyPairShortName())){
                    unset($this->modules[$key]);
                    break;
                }
            }elseif($wsModule instanceof WSModuleNotification && $module instanceof WSModuleNotification){
                if($module->isMatched($wsModule->getUserId())){
                    unset($this->modules[$key]);
                    break;
                }
            }
        }
    }

    /**
     * Remove module by name - $moduleString
     *
     * @param string $moduleString
     */
    public function removeModuleForce(string $moduleString){
        /** @var WSModuleInterface $module */
        foreach($this->modules as $key => $module){
            if($moduleString === AppWebsocketInterface::MODULE_ORDERBOOK && $module instanceof WSModuleOrderbook){
                unset($this->modules[$key]);
                break;
            }elseif($moduleString === AppWebsocketInterface::MODULE_EXTERNAL_ORDERBOOK && $module instanceof WSModuleExternalOrderbook){
                unset($this->modules[$key]);
                break;
            }elseif($moduleString === AppWebsocketInterface::MODULE_NOTIFICATIONS && $module instanceof WSModuleNotification){
                unset($this->modules[$key]);
                break;
            }
        }
    }

    /**
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }
}
