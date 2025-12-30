<?php

namespace ManiaLivePlugins\eXpansion\PersonalMessages;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Config;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Windows\PlayerSelection;
use ManiaLivePlugins\eXpansion\PersonalMessages\Config as PersonalMessagesConfig;

class PersonalMessages extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    
    private $message = array();
    private $targetPlayer = array();
    private $reply = array();

    /** @var \ManiaLivePlugins\eXpansion\Core\Config */
    private $config;
    private $pConfig;

    /** @var \ManiaLivePlugins\eXpansion\Core\I18n\Message */
    private $msg_noLogin;
    private $msg_noMessage;
    private $msg_noReply;
    private $msg_self;
    private $msg_help;

    private $cmd_chat;

    private $widget;
    private $script;
    private $trayScript;
    private $actionPlayers;
    private $actionSend;

    public function eXpOnLoad()
    {
        $this->msg_noLogin = eXpGetMessage('#personalmessage#Player with login "%1$s" is not found at server!');
        $this->msg_noMessage = eXpGetMessage("#personalmessage#No message to send to!");
        $this->msg_noReply = eXpGetMessage("#personalmessage#No one to reply back!");
        $this->msg_self = eXpGetMessage("#personalmessage#You can't send a message to yourself.");
        $this->msg_help = eXpGetMessage("#personalmessage#Usage /pm [login] your personal message here");

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $this->actionPlayers = $aH->createAction(array($this, 'players'));
        $this->actionSend = $aH->createAction(array($this, 'send'));

        $this->trayScript = new Script("Gui\Scripts\TrayWidget");
        $this->trayScript->setParam('isMinimized', "True");
        $this->trayScript->setParam('autoCloseTimeout', 0); //TODO: add config
        $this->trayScript->setParam('posXMin', -92);
        $this->trayScript->setParam('posX', -92);
        $this->trayScript->setParam('posXMax', -4);

        $this->script = new Script("PersonalMessages\Gui\Script");
        $this->script->setParam("sendAction", $this->actionSend);

        $this->widget = new Widget("PersonalMessages\Gui\Widgets\MessagesPanel.xml");
        $this->widget->setName("Personal Chat Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(100, 6);
        $this->widget->setDisableAxis("x");
        $this->widget->setParam("actionPlayers", $this->actionPlayers);
        $this->widget->registerScript($this->trayScript);
        $this->widget->registerScript($this->script);
        $this->widget->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->registerChatCommand("pm", "chatSendPersonalMessage", -1, true);
        $this->registerChatCommand("r", "sendReply", -1, true);
        $admingroup = AdminGroups::getInstance();

        $cmd = AdminGroups::addAdminCommand("channel", $this, "adminChat", "admin_chatChannel");
        $admingroup->addAlias($cmd, "a");

        $this->cmd_chat = $cmd;

        $this->config = Config::getInstance();
        $this->pConfig = PersonalMessagesConfig::getInstance();

        $this->sendWidget();
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (isset($this->reply[$login])) {
            unset($this->reply[$login]);
        }
        if (isset($this->message[$login])) {
            unset($this->message[$login]);
        }
        if (isset($this->targetPlayer[$login])) {
            unset($this->targetPlayer[$login]);
        }
    }

    public function chatSendPersonalMessage($login, $params = false)
    {
        if ($params === false) {
            $this->eXpChatSendServerMessage($this->msg_help, $login);
            return;
        }
        $message = explode(" ", $params);
        $target = array_shift($message);
        $message = implode(" ", $message);

        try {
            $color = '$z$s' . $this->config->Colors_personalmessage;
            PlayerSelection::Erase($login);

            if (!array_key_exists($target, $this->storage->players) && !array_key_exists($target, $this->storage->spectators)) {
                $this->eXpChatSendServerMessage($this->msg_noLogin, $login, array($target));
                return;
            }
            if ($login == $target) {
                $this->eXpChatSendServerMessage($this->msg_self, $login, array($target));
                return;
            }
            if (empty($message)) {
                $this->eXpChatSendServerMessage($this->msg_noMessage, $login);
                return;
            }
            $targetPlayer = $this->storage->getPlayerObject($target);
            $sourcePlayer = $this->storage->getPlayerObject($login);
            $this->reply[$target] = $login;

            $this->connection->chatSendServerMessage('$fff' . $sourcePlayer->nickName . $color . ' »» $fff' . $targetPlayer->nickName . $color . " " . $message, $login);
            $this->connection->chatSendServerMessage('$fff' . $sourcePlayer->nickName . $color . ' »» $fff' . $targetPlayer->nickName . $color . " " . $message, $target);
        } catch (\Exception $e) {
            $this->console("Error:" . $e->getMessage());
        }
    }

    public function adminChat($login, $message)
    {
        $message = implode(" ", $message);

        $sourcePlayer = $this->storage->getPlayerObject($login);
        $color = '$z$s' . $this->config->Colors_admingroup_chat;

        if (empty($message)) {
            $this->eXpChatSendServerMessage($this->msg_noMessage, $login);
            return;
        }
        try {
            foreach ($this->storage->players as $reciever => $player) {
                if (AdminGroups::hasPermission($reciever, Permission::CHAT_ADMINCHAT)) {
                    $this->connection->chatSendServerMessage($color . 'Admin »» $fff' . $sourcePlayer->nickName . $color . " " . $message, $reciever);
                }
            }
            foreach ($this->storage->spectators as $reciever => $player) {
                if (AdminGroups::hasPermission($reciever, Permission::CHAT_ADMINCHAT)) {
                    $this->connection->chatSendServerMessage($color . 'Admin »» $fff' . $sourcePlayer->nickName . $color . " " . $message, $reciever);
                }
            }
        } catch (\Exception $e) {
            $this->console("Error:" . $e->getMessage());
        }
    }

    public function sendReply($login, $message = "")
    {
        try {
            if (empty($message)) {
                $this->eXpChatSendServerMessage($this->msg_noMessage, $login);
                return;
            }
            if (array_key_exists($login, $this->reply)) {
                $this->reply[$this->reply[$login]] = $login;
                $targetPlayer = $this->storage->getPlayerObject($this->reply[$login]);
                $sourcePlayer = $this->storage->getPlayerObject($login);
                $color = '$z$s' . $this->config->Colors_personalmessage;
                $this->connection->chatSendServerMessage('$fff' . $sourcePlayer->nickName . $color . ' »» $fff' . $targetPlayer->nickName . $color . " " . $message, $login);
                $this->connection->chatSendServerMessage('$fff' . $sourcePlayer->nickName . $color . ' »» $fff' . $targetPlayer->nickName . $color . " " . $message, $this->reply[$login]);
            } else {
                $this->eXpChatSendServerMessage($this->msg_noReply, $login);
            }
        } catch (\Exception $e) {
            $this->console("Error sending a reply" . $e->getMessage());
        }
    }

    public function setTargetPlayer($login, $target)
    {
        $this->targetPlayer[$login] = $target;
        PlayerSelection::Erase($login);
        $this->sendWidget($login);
    }

    public function players($login, $args = array())
    {
        /** @var PlayerSelection $window */
        $window = PlayerSelection::Create($login);
        $window->setController($this);
        $window->setTitle('Select Player to send message');
        $window->setSize(85, 100);
        $window->populateList(array($this, 'setTargetPlayer'), 'send');
        $window->centerOnScreen();
        $window->show();
    }

    public function send($login, $args)
    {
        try {
            if (!isset($this->targetPlayer[$login])) {
                $this->connection->chatSendServerMessage('Select a player to send pm first by clicking!', $login);
                return;
            }
            if (empty($args['message'])) {
                $this->eXpChatSendServerMessage($this->msg_noMessage, $login);
                return;
            }

            $target = $this->targetPlayer[$login];

            if (!array_key_exists($target, $this->storage->players) && !array_key_exists($target, $this->storage->spectators)) {
                $this->eXpChatSendServerMessage($this->msg_noLogin, $login, array($target));
                return;
            }
            if ($login == $target) {
                $this->eXpChatSendServerMessage($this->msg_self, $login, array($target));
                return;
            }

            $message = $args['message'];
            $targetPlayer = $this->storage->getPlayerObject($target);
            $sourcePlayer = $this->storage->getPlayerObject($login);
            $this->reply[$target] = $login;
            $color = '$z$s' . $this->config->Colors_personalmessage;
            $this->connection->chatSendServerMessage('$fff' . $sourcePlayer->nickName . $color . ' »» $fff' . $targetPlayer->nickName . $color . " " . $message, $login);
            $this->connection->chatSendServerMessage('$fff' . $sourcePlayer->nickName . $color . ' »» $fff' . $targetPlayer->nickName . $color . " " . $message, $target);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage('$f00$oError $z$s$fff' . $e->getMessage(), $login);
        }
    }

    public function sendWidget($login = null)
    {
        if ($login && isset($this->targetPlayer[$login])) {
            $targetPlayer = $this->storage->getPlayerObject($this->targetPlayer[$login]);
            $this->widget->setParam("targetPlayer", $targetPlayer->nickName);
        }
        $this->widget->setPosition($this->pConfig->messagingWidget_PosX, $this->pConfig->messagingWidget_PosY, 20);
        $this->widget->show($login, is_null($login));
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $aH->deleteAction($this->actionPlayers);
        $aH->deleteAction($this->actionSend);
        $this->actionPlayers = null;
        $this->actionSend = null;
        $this->script = null;
        $this->trayScript = null;
        $this->widget = null;

        AdminGroups::removeAdminCommand($this->cmd_chat);
        AdminGroups::removeShortAllias('a');

        $this->reply = array();
        $this->message = array();
        $this->targetPlayer = array();
    }
}
