<?php

namespace App\Server;

use App\Entity\User;
use App\Model\WS\WSModuleExternalOrderbook;
use App\Model\WS\WSModuleInterface;
use App\Model\WS\WSModuleNotification;
use App\Model\WS\WSModuleOrderbook;
use App\Model\WS\WSConnectionModel;
use App\Model\WS\WSMessage;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class AppWebsocket implements AppWebsocketInterface, MessageComponentInterface
{
    protected $connections = array();

    /**
     * Verify if action is allowed
     *
     * @param string $action
     * @return bool
     */
    private function isActionAllowed(string $action){
        if(array_key_exists($action, self::ACTIONS)){
            return true;
        }

        return false;
    }

    /**
     * Verify if module is allowed
     *
     * @param string $module
     * @return bool
     */
    private function isModuleAllowed(string $module){
        if(array_key_exists($module, self::MODULES)){
            return true;
        }

        return false;
    }

    /**
     * @param WSMessage $wsMessage
     * @return bool
     */
    private function isWSMessageAuthorized(WSMessage $wsMessage) : bool
    {
        return true;

//        if($wsMessage->getUserWsHash() && $wsMessage->getUserWsHash() === User::generateWsHash($wsMessage->getUserId())){
//            return true;
//        }
//
//        return false;
    }

    /**
     * Subscribe connection $conn for Websocket by specified $wsMessage
     *
     * @param ConnectionInterface $conn
     * @param WSMessage $wsMessage
     */
    private function subscribe(ConnectionInterface $conn, WSMessage $wsMessage){
        $exists = false;

        /** @var WSConnectionModel $connection */
        foreach($this->connections as $key => $connection){
            if($connection->getConnection() === $conn){
                $exists = true;

                if($wsMessage->getModule() === self::MODULE_ORDERBOOK && $wsMessage->getCurrencyPairShortName()){
                    $this->connections[$key]->addModule(new WSModuleOrderbook($wsMessage->getCurrencyPairShortName()));
                }elseif($wsMessage->getModule() === self::MODULE_EXTERNAL_ORDERBOOK && $wsMessage->getCurrencyPairShortName()){
                    $this->connections[$key]->addModule(new WSModuleExternalOrderbook($wsMessage->getCurrencyPairShortName()));
                }elseif($wsMessage->getModule() === self::MODULE_NOTIFICATIONS && $wsMessage->getUserId()){
                    if($this->isWSMessageAuthorized($wsMessage)){
                        $this->connections[$key]->addModule(new WSModuleNotification($wsMessage->getUserId()));
                    }
                }

                break;
            }
        }

        if(!$exists){
            /** @var WSConnectionModel $connection */
            $connection = new WSConnectionModel($conn);

            if($wsMessage->getModule() === self::MODULE_ORDERBOOK && $wsMessage->getCurrencyPairShortName()){
                $connection->addModule(new WSModuleOrderbook($wsMessage->getCurrencyPairShortName()));

                $this->connections[] = $connection;
            }elseif($wsMessage->getModule() === self::MODULE_EXTERNAL_ORDERBOOK && $wsMessage->getCurrencyPairShortName()){
                $connection->addModule(new WSModuleExternalOrderbook($wsMessage->getCurrencyPairShortName()));

                $this->connections[] = $connection;
            }elseif($wsMessage->getModule() === self::MODULE_NOTIFICATIONS && $wsMessage->getUserId()){
                if($this->isWSMessageAuthorized($wsMessage)){
                    $connection->addModule(new WSModuleNotification($wsMessage->getUserId()));

                    $this->connections[] = $connection;
                }
            }
        }
    }

    /**
     * Unsubscribe connection $conn for Websocket by specified $wsMessage
     *
     * @param ConnectionInterface $conn
     * @param WSMessage $wsMessage
     */
    private function unsubscribeModule(ConnectionInterface $conn, WSMessage $wsMessage){
        /** @var WSConnectionModel $connection */
        foreach($this->connections as $key => $connection){
            if($connection->getConnection() === $conn){
                if($wsMessage->getModule() === self::MODULE_ORDERBOOK){
                    if($wsMessage->getCurrencyPairShortName()){
                        $connection->removeModule(new WSModuleOrderbook($wsMessage->getCurrencyPairShortName()));
                    }else{
                        $connection->removeModuleForce(self::MODULE_ORDERBOOK);
                    }
                }elseif($wsMessage->getModule() === self::MODULE_EXTERNAL_ORDERBOOK){
                    if($wsMessage->getCurrencyPairShortName()){
                        $connection->removeModule(new WSModuleExternalOrderbook($wsMessage->getCurrencyPairShortName()));
                    }else{
                        $connection->removeModuleForce(self::MODULE_EXTERNAL_ORDERBOOK);
                    }
                }elseif($wsMessage->getModule() === self::MODULE_NOTIFICATIONS){
                    if($wsMessage->getUserId()){
                        $connection->removeModule(new WSModuleNotification($wsMessage->getUserId()));
                    }else{
                        $connection->removeModuleForce(self::MODULE_NOTIFICATIONS);
                    }
                }

                break;
            }
        }
    }

    /**
     * Resolve message receivers and send push message to specified clients
     *
     * @param WSMessage $wsMessage
     */
    private function sendInternalMessage(WSMessage $wsMessage){
        /** @var WSConnectionModel $connection */
        foreach($this->connections as $connection){
            if($wsMessage->getModule() === self::MODULE_ORDERBOOK && $wsMessage->getCurrencyPairShortName()){
                /** @var WSModuleInterface $module */
                foreach($connection->getModules() as $module){
                    if($module instanceof WSModuleOrderbook && $module->isMatched($wsMessage->getCurrencyPairShortName())){
                        $connection->getConnection()->send(json_encode($wsMessage->getData()));
                    }
                }
            }elseif($wsMessage->getModule() === self::MODULE_EXTERNAL_ORDERBOOK && $wsMessage->getCurrencyPairShortName()){
                /** @var WSModuleInterface $module */
                foreach($connection->getModules() as $module){
                    if($module instanceof WSModuleExternalOrderbook && $module->isMatched($wsMessage->getCurrencyPairShortName())){
                        $connection->getConnection()->send(json_encode($wsMessage->getData()));
                    }
                }
            }elseif($wsMessage->getModule() === self::MODULE_NOTIFICATIONS && $wsMessage->getUserId()){
                if($this->isWSMessageAuthorized($wsMessage)){
                    /** @var WSModuleInterface $module */
                    foreach($connection->getModules() as $module){
                        if($module instanceof WSModuleNotification && $module->isMatched($wsMessage->getUserId())){
                            $connection->getConnection()->send(json_encode($wsMessage->getData()));
                        }
                    }
                }
            }
        }
    }

    /**
     * Unsubscribe $conn from opened connections
     *
     * @param ConnectionInterface $conn
     */
    private function unsubscribe(ConnectionInterface $conn){
        /**
         * @var int $key
         * @var WSConnectionModel $connItem
         */
        foreach($this->connections as $key => $connItem){
            if($connItem->getConnection() === $conn){
                unset($this->connections[$key]);
                break;
            }
        }
    }

    /**
     * A new websocket connection
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $conn->send('ok');
    }

    /**
     * Handle message sending
     *
     * @param ConnectionInterface $from
     * @param string $msg
     * @return bool
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        try{
            /** @var WSMessage $wsMessage */
            $wsMessage = new WSMessage((array) json_decode($msg));
            if(!$wsMessage->isValid()) return false;

            if(!$this->isActionAllowed($wsMessage->getAction())) return false;
            if(!$this->isModuleAllowed($wsMessage->getModule())) return false;

            if($wsMessage->getAction() === self::ACTION_SUBSCRIBE){
                // subscribe connection
                $this->subscribe($from, $wsMessage);
            }elseif($wsMessage->getAction() === self::ACTION_UNSUBSCRIBE){
                // unsubscribe connection from the module
                $this->unsubscribeModule($from, $wsMessage);
            }elseif($wsMessage->getAction() === self::ACTION_PUSH){
                // push message to specified connections
                $this->sendInternalMessage($wsMessage);
            }
        }catch (\Exception $exception){
            // to nothing
        }
    }

    /**
     * A connection is closed
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->unsubscribe($conn);
    }

    /**
     * Error handling
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send(json_encode([
            'error' => $e->getMessage()
        ]));
        $conn->close();
    }
}
