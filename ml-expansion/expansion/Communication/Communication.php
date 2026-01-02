<?php

/*
 * Copyright (C) 2014 Reaby
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ManiaLivePlugins\eXpansion\Communication;

use ManiaLib\Utils\Formatting;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Windows\PlayerSelection;

/**
 * Description of Communication
 *
 * @author Reaby
 */
class Communication extends ExpPlugin
{

    private $lastCheck = 0;
    private $config;

    private $widget;
    private $script;
    private $trayScript;
    private $actionPlayers;
    private $actionSend;

    /** @var \ManiaLivePlugins\eXpansion\Core\I18n\Message */
    private $msg_noLogin;
    private $msg_noMessage;
    private $msg_self;
    private $msg_help;

    /** @var \Maniaplanet\DedicatedServer\Structures\Player */
    private $cachedIgnoreList = array();

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->config = Config::getInstance();

        $this->msg_noLogin = eXpGetMessage('#personalmessage#Player with login "%1$s" is not found at server!');
        $this->msg_noMessage = eXpGetMessage("#personalmessage#No message to send to!");
        $this->msg_self = eXpGetMessage("#personalmessage#You can't send a message to yourself.");
        $this->msg_help = eXpGetMessage("#personalmessage#Usage /send [login] your personal message here");

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $this->actionPlayers = $aH->createAction(array($this, 'selectPlayer'));
        $this->actionSend = $aH->createAction(array($this, 'guiSendMessage'));

        $this->trayScript = new Script("Gui\Scripts\TrayWidget");
        $this->trayScript->setParam('isMinimized', "True");
        $this->trayScript->setParam('autoCloseTimeout', 0); //TODO: add config
        $this->trayScript->setParam('posXMin', -116);
        $this->trayScript->setParam('posX', -116);
        $this->trayScript->setParam('posXMax', -4);

        $this->script = new Script("Communication\Gui\Script");
        $this->script->setParam("sendAction", $this->actionSend);

        $this->widget = new Widget("Communication\Gui\Widgets\CommunicationWidget.xml");
        $this->widget->setName("Messaging Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(120, 39);
        $this->widget->setDisableAxis("x");
        $this->widget->setParam("actionPlayers", $this->actionPlayers);
        $this->widget->setParam("actionSend", $this->actionSend);
        $this->widget->registerScript($this->trayScript);
        $this->widget->registerScript($this->script);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }

        $this->sendWidget();

        $this->registerChatCommand("send", "sendPmChat", -1, true);

        $this->updateMessager(null, "clearMessages");

        $this->lastCheck = time();
        $this->cachedIgnoreList = $this->connection->getIgnoreList(-1, 0);
    }

    public function sendWidget()
    {
        $this->widget->setPosition($this->config->messaging_PosX, $this->config->messaging_PosY, 30);
        $this->widget->show(null, true);
    }

    public function updateMessager($login, $action, $tab = "", $text = "")
    {
        if ($action != "sendMessage" && $action != "clearMessages" && $action != "closeTab" && $action != "openTab") {
            return;
        }

        $tab = Gui::fixString($tab);
        $text = Gui::fixString($text);

        $script = 'main () {
            declare Text[][Text] chatLiness for UI = Text[][Text];
            declare Boolean isChatUpdated for UI;
            declare Boolean forceUpdate for UI;
            declare Text tab = "' . $tab . '";
            declare Text chat = "' . $text . '";
            declare Text action = "' . $action . '";

            switch (action) {
                case "sendMessage": {
                    if (!chatLiness.existskey(tab)) {
                        chatLiness[tab] = Text[];
                    }
                    chatLiness[tab].add(chat);
                    isChatUpdated = True;
                }
                case "clearMessages": {
                    chatLiness.clear(); 
                    forceUpdate = True; 
                }
                case "closeTab": {
                    chatLiness.removekey(tab);
                    forceUpdate = True; 
                }
                case "openTab": {
                    if (!chatLiness.existskey(tab)) {
                        chatLiness[tab] = Text[];
                    }
                    forceUpdate = True; 
                }
            }
        }';

        $xml = '<manialink id="messager_update" version="2" name="messager_update">';
        $xml .= '<script><!--';
        $xml .= $script;
        $xml .= '--></script>';
        $xml .= '</manialink>';

        try {
            $this->connection->sendDisplayManialinkPage($login, $xml);
        } catch (\Exception $e) {
            $this->console('Could not send messager update to ' . $login . ': ' . $e->getMessage());
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->updateMessager($login, "clearMessages");
    }

    public function send($login, $tab, $text)
    {
        // undo replacing maniascript en hyphen to normal one, so message reaches the right person...
        $login = str_replace('–', '-', $login);
        $this->updateMessager($login, "sendMessage", $tab, $text);
    }

    public function sendPm($login, $target, $text)
    {
        if (!$this->checkPlayer($login)) {
            $this->send($login, $target, '$d00' . __("You are being ignored. Message not sent.", $login));
            return;
        }

        $fromPlayer = $this->storage->getPlayerObject($login);
        $this->send($login, $target, '$z$fffMe: ' . $text);
        $this->send($target, $login, '$z$222' . Formatting::stripWideFonts($fromPlayer->nickName) . '$z$222: ' . $text);
    }

    /**
     * checks if player is found at server
     *
     * @param string $login
     *
     * @return boolean
     */
    private function checkPlayer($login)
    {
        // sync ignorelist every 10 seconds...
        if (time() > $this->lastCheck + 10) {
            $this->lastCheck = time();
            $this->cachedIgnoreList = $this->connection->getIgnoreList(-1, 0);
        }

        foreach ($this->cachedIgnoreList as $player) {
            if ($player->login == $login) {
                return false;
            }
        }

        $test = $this->storage->getPlayerObject($login);
        if (empty($test)) {
            return false;
        }

        return true;
    }

    public function guiSendMessage($login, $entries)
    {
        $target = $entries['replyTo'];

        $this->sendPm($login, $target, $entries['chatEntry']);
    }

    public function sendPmChat($login, $params = false)
    {
        if ($params === false) {
            $this->eXpChatSendServerMessage($this->msg_help, $login);
            return;
        }
        $text = explode(" ", $params);
        $target = array_shift($text);
        $text = implode(" ", $text);

        if (!array_key_exists($target, $this->storage->players) && !array_key_exists($target, $this->storage->spectators)) {
            $this->eXpChatSendServerMessage($this->msg_noLogin, $login, array($target));
            return;
        }
        if ($login == $target) {
            $this->eXpChatSendServerMessage($this->msg_self, $login, array($target));
            return;
        }
        if (empty($text)) {
            $this->eXpChatSendServerMessage($this->msg_noMessage, $login);
            return;
        }

        $this->sendPm($login, $target, $text);
    }

    public function selectPlayer($login)
    {
        /** @var PlayerSelection @window */
        $window = PlayerSelection::Create($login);
        $window->setController($this);
        $window->setTitle('Select Player');
        $window->setSize(85, 100);
        $window->populateList(array($this, 'openNewTab'), 'Select');
        $window->centerOnScreen();
        $window->show();
    }

    public function openNewTab($login, $target)
    {
        PlayerSelection::Erase($login);
        $this->updateMessager($login, "openTab", $target);
    }

    public function eXpOnUnload()
    {
        $this->connection->sendDisplayManialinkPage(null, '<manialink id="messager_update"></manialink>', 0, false, true);
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

        parent::eXpOnUnload();
    }
}
