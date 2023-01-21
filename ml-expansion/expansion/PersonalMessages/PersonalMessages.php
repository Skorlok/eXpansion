<?php

namespace ManiaLivePlugins\eXpansion\PersonalMessages;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Config;
use ManiaLivePlugins\eXpansion\Gui\Windows\PlayerSelection;
use ManiaLivePlugins\eXpansion\PersonalMessages\Gui\Widgets\MessagesPanel;

class PersonalMessages extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    public static $reply = array();
    private $message = array();

    /** @var \ManiaLivePlugins\eXpansion\Core\Config */
    private $config;

    /** @var \ManiaLivePlugins\eXpansion\Core\I18n\Message */
    private $msg_noLogin;
    private $msg_noMessage;
    private $msg_noReply;
    private $msg_self;
    private $msg_help;

    private $cmd_chat;

    public function eXpOnLoad()
    {
        $this->msg_noLogin = eXpGetMessage('#personalmessage#Player with login "%1$s" is not found at server!');
        $this->msg_noMessage = eXpGetMessage("#personalmessage#No message to send to!");
        $this->msg_noReply = eXpGetMessage("#personalmessage#No one to reply back!");
        $this->msg_self = eXpGetMessage("#personalmessage#You can't send a message to yourself.");
        $this->msg_help = eXpGetMessage("#personalmessage#Usage /pm [login] your personal message here");
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->registerChatCommand("pm", "chatSendPersonalMessage", -1, true);
        $this->registerChatCommand("r", "sendReply", -1, true);
        $admingroup = AdminGroups::getInstance();

        $cmd = AdminGroups::addAdminCommand("channel", $this, "adminChat", "admin_chatChannel");
        $admingroup->addShortAlias($cmd, "a");

        $this->cmd_chat = $cmd;

        $this->config = Config::getInstance();

        foreach ($this->storage->players as $login => $player) {
            $this->onPlayerConnect($player->login, false);
        }
        foreach ($this->storage->spectators as $login => $player) {
            $this->onPlayerConnect($player->login, true);
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $info = Gui\Widgets\MessagesPanel::Create($login);
        $info->setSize(100, 6);
        $info->setDisableAxis("x");
        $info->show();
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (isset(self::$reply[$login])) {
            unset(self::$reply[$login]);
        }
        if (isset($this->message[$login])) {
            unset($this->message[$login]);
        }
    }

    public function sendPersonalMessage($login, $message = "")
    {
        $window = PlayerSelection::Create($login);
        $window->setController($this);
        $window->setTitle('Select Player to send message');
        $window->setSize(85, 100);
        $window->populateList(array($this, 'sendPm'), 'send');
        $window->centerOnScreen();
        $window->show();
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

            if (!array_key_exists($target, $this->storage->players)
                && !array_key_exists($target, $this->storage->spectators)
            ) {
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
            self::$reply[$login] = $target;


            $this->connection->chatSendServerMessage(
                '$fff' . $sourcePlayer->nickName . $color . ' »» $fff'
                . $targetPlayer->nickName . $color . " " . $message,
                $login
            );
            $this->connection->chatSendServerMessage(
                '$fff' . $sourcePlayer->nickName . $color . ' »» $fff'
                . $targetPlayer->nickName . $color . " " . $message,
                $target
            );
        } catch (\Exception $e) {
            $this->console("Error:" . $e->getMessage());
        }
    }

    public function sendPm($login, $target)
    {
        try {

            if (!array_key_exists($target, $this->storage->players)
                && !array_key_exists($target, $this->storage->spectators)
            ) {
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

            PlayerSelection::Erase($login);
            $targetPlayer = $this->storage->getPlayerObject($target);
            $sourcePlayer = $this->storage->getPlayerObject($login);
            self::$reply[$login] = $target;
            $color = '$z$s' . $this->config->Colors_personalmessage;

            $this->connection->chatSendServerMessage(
                '$fff' . $sourcePlayer->nickName . $color . ' »» $fff'
                . $targetPlayer->nickName . $color . " " . $this->message[$login],
                $login
            );
            $this->connection->chatSendServerMessage(
                '$fff' . $sourcePlayer->nickName . $color . ' »» $fff'
                . $targetPlayer->nickName . $color . " " . $this->message[$login],
                $target
            );
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
                    $this->connection->chatSendServerMessage(
                        $color . 'Admin »» $fff' . $sourcePlayer->nickName . $color . " " . $message,
                        $reciever
                    );
                }
            }
            foreach ($this->storage->spectators as $reciever => $player) {
                if (AdminGroups::hasPermission($reciever, Permission::CHAT_ADMINCHAT)) {
                    $this->connection->chatSendServerMessage(
                        $color . 'Admin »» $fff' . $sourcePlayer->nickName . $color . " " . $message,
                        $reciever
                    );
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
            if (array_key_exists($login, self::$reply)) {
                $targetPlayer = $this->storage->getPlayerObject(self::$reply[$login]);
                $sourcePlayer = $this->storage->getPlayerObject($login);
                $color = '$z$s' . $this->config->Colors_personalmessage;
                $this->connection->chatSendServerMessage(
                    '$fff' . $sourcePlayer->nickName . $color . ' »» $fff'
                    . $targetPlayer->nickName . $color . " " . $message,
                    $login
                );
                $this->connection->chatSendServerMessage(
                    '$fff' . $sourcePlayer->nickName . $color . ' »» $fff'
                    . $targetPlayer->nickName . $color . " " . $message,
                    self::$reply[$login]
                );
            } else {
                $this->eXpChatSendServerMessage($this->msg_noReply, $login);
            }
        } catch (\Exception $e) {
            $this->console("Error sending a reply" . $e->getMessage());
        }
    }

    public function eXpOnUnload()
    {
        MessagesPanel::EraseAll();
        AdminGroups::removeAdminCommand($this->cmd_chat);
        AdminGroups::removeShortAllias('a');
    }
}
