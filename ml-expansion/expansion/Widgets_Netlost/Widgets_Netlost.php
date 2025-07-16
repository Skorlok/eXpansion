<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Netlost;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Events\Event as AdminGroupEvent;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use Maniaplanet\DedicatedServer\Structures\PlayerNetInfo;

class Widgets_Netlost extends ExpPlugin implements \ManiaLivePlugins\eXpansion\AdminGroups\Events\Listener
{

    private $config;

    private $widget;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        Dispatcher::register(AdminGroupEvent::getClass(), $this);

        $this->widget = new Widget("Widgets_Netlost\Gui\Widgets\Netlost.xml");
        $this->widget->setName("Netlost Widget");
        $this->widget->setLayer("normal");
        $this->widget->registerScript(new Script('Widgets_Netlost\Gui\Scripts_Netlost'));

        $this->displayWidget();
    }

    /**
     * @param PlayerNetInfo[] $players
     */
    public function onPlayerNetLost($players)
    {
        $out = "";
        $comma = "";

        foreach ($players as $player) {
            $pla = $this->storage->getPlayerObject($player->login);
            $out .= $comma . '"' . Gui::fixString($pla->nickName) . ' $z$s(' . Gui::fixString($pla->login) . ') "';
            $comma = ", ";
        }

        if (empty($out)) {
            $out = "Text[]";
        } else {
            $out = "[" . $out . "]";
        }

        $recepient = null;
        if (Config::getInstance()->showOnlyAdmins) {
            $recepient = $this->getConnectedAdmins();
            if ($recepient === false) {
                return;
            }
        }

        $script = "main () {
            declare eXp_lastUpdate = 0;
            declare Text[] netLost for UI = Text[];
            declare Integer[Text] netLostTimeouts for UI;

            declare Boolean isNetlostUpdated for UI;

            netLost = " . $out . ";
            isNetlostUpdated = True;
        }";

        $xml = '<manialink id="netlost_messaging" version="2" name="netlost_messaging">';
        $xml .= '<script><!--';
        $xml .= $script;
        $xml .= '--></script>';
        $xml .= '</manialink>';
        
        try {
            $this->connection->sendDisplayManialinkPage($recepient, $xml);
        } catch (\Exception $e) {
            if ($recepient !== null) {
                $this->console('Could not send netlost update to admins, retrying every logins: ' . $e->getMessage());
                foreach ($recepient as $login) {
                    try {
                        $this->connection->sendDisplayManialinkPage($login, $xml);
                    } catch (\Exception $e) {
                        $this->console('Could not send netlost update to ' . $login . ': ' . $e->getMessage());
                    }
                }
            } else {
                $this->console('Could not send netlost update: ' . $e->getMessage());
            }
        }
    }

    public function getConnectedAdmins()
    {
        $admins = AdminGroups::getInstance()->get();

        $playersConnected = array();
        foreach ($this->storage->players as $player) {
            if (in_array($player->login, $admins)) {
                $playersConnected[] = $player->login;
            }
        }
        foreach ($this->storage->spectators as $player) {
            if (in_array($player->login, $admins)) {
                $playersConnected[] = $player->login;
            }
        }
        
        if (empty($playersConnected)) {
            return false;
        } else {
            return $playersConnected;
        }
    }

    /**
     * displayWidget(string $login)
     *
     * @param string $login
     */
    public function displayWidget()
    {
        $recepient = null;
        if (Config::getInstance()->showOnlyAdmins) {
            $recepient = $this->getConnectedAdmins();
            if ($recepient === false) {
                return;
            }
        }

        $this->widget->setSize(200, 12);
        $this->widget->setPosition($this->config->netlostWidget_PosX, $this->config->netlostWidget_PosY, 0);
        $this->widget->show($recepient);
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($this->widget instanceof Widget) {
            if (Config::getInstance()->showOnlyAdmins && !in_array($login, AdminGroups::getInstance()->get())) {
                return;
            }
            $this->widget->show($login);
        }
    }

    public function eXpAdminAdded($login)
    {
        $this->displayWidget();
    }

    public function eXpAdminRemoved($login)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase($login);
        }
        $this->displayWidget();
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(AdminGroupEvent::getClass(), $this);

        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
    }
}
