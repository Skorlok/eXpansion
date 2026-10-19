<?php

/**
 *
 * @name Oliverde8 Server Switch
 * @date      23-03-2013
 * @version   1.0
 * @website   oliver-decramer.com
 * @package   oliverd8
 *
 * @author    Oliver "oliverde8" De Cramer <oliverde8@gmail.com>
 * @Idea      undef.de
 * @copyright 2013
 *
 * ---------------------------------------------------------------------
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
 * ---------------------------------------------------------------------
 * You are allowed to change things of use this in other projects, as
 * long as you leave the information at the top (name, date, version,
 * website, package, author, copyright) and publish the code under
 * the GNU General Public License version 3.
 * ---------------------------------------------------------------------
 */

namespace ManiaLivePlugins\eXpansion\ServerNeighborhood;

use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Menu\Menu;

class ServerNeighborhood extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $server;

    private $servers = array();
    private $onlineServers = array();

    private $lastOnlineCount = 0;
    private $lastOnlineStartIndex = 0;

    private $lastSent = 0;

    private $config;

    private $widget;
    private $script;

    /** @var Window */
    private $serverListWindow;

    /** @var Window */
    private $playerListWindow;

    public function eXpOnInit()
    {
        $this->setVersion("1.6");
        $this->config = Config::getInstance();
        $this->setPublicMethod("showServerList");
    }

    public function eXpOnLoad()
    {
        Menu::addMenuItem("ServerNeighborhood",
            array("Server Neighborhood" => array(null, 'exp:eXpansion.ServerNeighborhood:showServerList'))
        );

        $this->initWidget();

        $this->serverListWindow = new Window("ServerNeighborhood\Gui\Windows\ServerList.xml");
        $this->serverListWindow->setName("ServerList");
        $this->serverListWindow->setSize(120, 105);
        $this->serverListWindow->setTitle("ServerNeighborhood - Server List");

        $this->playerListWindow = new Window("ServerNeighborhood\Gui\Windows\PlayerList.xml");
        $this->playerListWindow->setName("ServerPlayerList");
        $this->playerListWindow->setSize(120, 105);
        $this->playerListWindow->setTitle("ServerNeighborhood - Server Players");
    }

    public function initWidget()
    {
        $this->script = new Script("ServerNeighborhood/Gui/Scripts/Time");
        
        $this->widget = new Widget("ServerNeighborhood\Gui\Widgets\ServerNeighborhood.xml");
        $this->widget->setName("Server Neighborhood Panel");
        $this->widget->setLayer("normal");
        $this->widget->setParam('title', 'Server Neighborhood');
        $this->widget->registerScript($this->script);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        if ($this->config->snwidget_isDockable) {
            $trayScript = new Script("Gui/Scripts/TrayWidget");
            $trayScript->setParam('isMinimized', 'True');
            $trayScript->setParam('autoCloseTimeout', $this->config->refresh_interval * 1000);
            $trayScript->setParam('posXMin', $trayScript->getNumber(-33));
            $trayScript->setParam('posX', $trayScript->getNumber(-33));
            $trayScript->setParam('posXMax', $trayScript->getNumber(-3));
            $trayScript->setParam('smaller', '>');
            $this->widget->registerScript($trayScript);
            $this->widget->setDisableAxis("x");
        }
    }

    public function eXpOnReady()
    {
        $this->registerManialinkCallback('showServerList');
        $this->registerManialinkCallback('showServerPlayers', false, true);
        
        $this->server = new Server();
        $this->server->create_fromConnection($this->connection, $this->storage);

        $this->registerChatCommand('servers', 'showServerList', 0, true);

        $this->enableTickerEvent();
        $this->enableDedicatedEvents(\ManiaLive\DedicatedApi\Callback\Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);
    }

    public function onSettingsChanged(Variable $var)
    {
        $this->config = Config::getInstance();

        if ($var->getName() == 'storing_path') {
            $status = $this->saveData($this->server->createXML($this->connection, $this->storage));
            $this->lastSent = time();

            if (!$status) {
                $admins = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
                $admins->announceToPermission(Permission::EXPANSION_PLUGIN_SETTINGS, "#admin_error#[ServerNeighborhood] Storage path is wrong. Can't write!!");
            }
        }

        if ($var->getName() == 'snwidget_isDockable') {
            $this->getData();
            if ($this->widget instanceof Widget) {
                $this->widget->erase();
            }
            $this->initWidget();
            $this->showWidget();
            $this->lastSent = time();
        }
    }

    public function onTick()
    {
        parent::onTick();
        if ((time() - $this->lastSent) > $this->config->refresh_interval) {
            $this->saveData($this->server->createXML($this->connection, $this->storage));
            $this->lastSent = time();

            if (sizeof($this->storage->players) > 0 || sizeof($this->storage->spectators) > 0) {
                $this->getData();
                $this->showWidget();
            }
        }
    }

    public function saveData($data)
    {
        $filename = $this->config->storing_path . $this->storage->serverLogin . '_serverinfo.xml';

        // Opens the file for writing and truncates it to zero length
        // Try min. 40 times to open if it fails (write block)
        $tries = 0;

        $context = stream_context_create(array('ftp' => array('overwrite' => true)));

        try {
            $fh = fopen($filename, "w", 0, $context);
        } catch (\Exception $ex) {
            $fh = false;
        }
        while ($fh === false) {
            if ($tries > 40) {
                break;
            }
            $tries++;
            try {
                $fh = fopen($filename, "w", 0, $context);
            } catch (\Exception $ex) {
                $fh = false;
            }
        }
        if ($tries >= 40) {
            $this->console('Could not open file " ' . $filename . '" to store the Server Information!');
            return false;
        } else {
            fwrite($fh, $data);
            fclose($fh);
        }

        return true;
    }

    public function getData()
    {
        $this->onlineServers = array();
        foreach ($this->config->servers as $serverPath) {
            if (!file_exists($serverPath) && !is_dir($serverPath)) {
                $this->console('Error loading : ' . $serverPath . ' file does not exist!');
                continue;
            }
            
            if (substr($serverPath, -15) == '_serverinfo.xml') {
                $this->buildSrvData($serverPath);
            } else if (is_dir($serverPath)) {
                $files = scandir($serverPath);
                foreach ($files as $file) {
                    if (substr($file, -15) == '_serverinfo.xml') {
                        $this->buildSrvData($serverPath . $file);
                    }
                }
            } else {
                $this->console('Error loading : ' . $serverPath . ' is not valid! (should end with _serverinfo.xml)');
            }
        }
    }

    public function buildSrvData($serverPath)
    {
        if (!is_readable($serverPath)) {
            $this->console('Error loading : ' . $serverPath . ' file is not readable!');
            return;
        }
        if (!is_file($serverPath)) {
            $this->console('Error loading : ' . $serverPath . ' is not a file!');
            return;
        }

        try {
            $data = file_get_contents($serverPath);
            if (!$data) {
                $this->console('Error loading : ' . $serverPath . ' empty file?');
                return;
            }
            $xml = simplexml_load_string($data);
            if (!$xml) {
                $this->console("Error loading : ". $serverPath . " invalid XML?");
                return;
            }

            $cleanIndex = preg_replace("/[^a-zA-Z0-9]+/", "", $serverPath);

            if (isset($this->servers[$cleanIndex]) && is_object($this->servers[$cleanIndex])) {
                $server = $this->servers[$cleanIndex];
            } else {
                $server = new Server();
                $this->servers[$cleanIndex] = $server;
                $this->servers[$cleanIndex]->mlAction = 'exp:eXpansion.ServerNeighborhood:showServerPlayers:' . $cleanIndex;
            }

            $server->setServer_data($xml);
            
            if ($server->isOnline()) {
                $this->onlineServers[] = $server;
            }
        } catch (\Exception $ex) {
            $this->console('Error loading : ' . $serverPath);
        }
    }

    public function showWidget()
    {
        // compute new start index
        $nbOnline = count($this->onlineServers);
        if ($nbOnline != $this->lastOnlineCount) {
            $this->lastOnlineStartIndex = 0;
        } elseif ($this->lastOnlineStartIndex >= $nbOnline) {
            $this->lastOnlineStartIndex = 0;
        }
        $this->lastOnlineCount = $nbOnline;

        $servers = array_slice($this->onlineServers, $this->lastOnlineStartIndex, $this->config->nbElement);

        $this->lastOnlineStartIndex += $this->config->nbElement;


        if ($this->config->style == 'UndefStyle') {
            $sizeY = 5.8;
        } else {
            $sizeY = 3.4;
        }

        $gamemodes = array(
            'rounds'     => 'RT_Rounds',
            'timeattack' => 'RT_TimeAttack',
            'team'       => 'RT_Team',
            'laps'       => 'RT_Laps',
            'cup'        => 'RT_Cup',
        );

        $rows = array();
        foreach ($servers as $srv) {
            $server = $srv->getServer_data()->server;

            $iconStyle = 'empty';
            $iconSubstyle = 'empty';
            if ($server->login == $this->storage->serverLogin) {
                $iconStyle = 'Icons128x128_1';
                $iconSubstyle = 'Back';
            } elseif ($server->private == 'true') {
                $iconStyle = 'Icons128x128_1';
                $iconSubstyle = 'Padlock';
            }

            $playersColor = ((int)$server->players->current >= (int)$server->players->maximum) ? '$F00' : '$FFF';
            $specsColor   = ((int)$server->spectators->current >= (int)$server->spectators->maximum) ? '$F00' : '$FFF';
            $gamemode     = strval($server->gamemode);

            $rows[] = array(
                'posY'         => -$sizeY * count($rows),
                'action'       => $this->widget->handleSpecialChars($srv->mlAction),
                'iconStyle'    => $iconStyle,
                'iconSubstyle' => $iconSubstyle,
                'modeSubstyle' => isset($gamemodes[$gamemode]) ? $gamemodes[$gamemode] : 'RT_Script',
                'players'      => $this->widget->handleSpecialChars($playersColor . $server->players->current . '/' . $server->players->maximum),
                'specs'        => $this->widget->handleSpecialChars($specsColor . $server->spectators->current . '/' . $server->spectators->maximum),
                'ladder'       => $this->widget->handleSpecialChars('$FFF' . $server->ladder->minimum . '/' . $server->ladder->maximum),
                'name'         => '$AAA' . $this->widget->handleSpecialChars((string)$server->name),
            );
        }

        $this->script->setParam('refresh_interval', $this->config->refresh_interval * 1000);

        $this->widget->setPosition($this->config->serverPanel_PosX, $this->config->serverPanel_PosY, 25);
        $this->widget->setSize(33, $sizeY * $this->config->nbElement + 9);
        $this->widget->setParam('refresh_interval', $this->config->refresh_interval);
        $this->widget->setParam('isUndefStyle', $this->config->style == 'UndefStyle');
        $this->widget->setParam('servers', $rows);
        // without any server, the background only covers the title
        $this->widget->setParam('bgSizeY', empty($rows) ? 8.75 : $sizeY * $this->config->nbElement + 8.75);
        $this->widget->show(null, true);
    }

    public function showServerPlayers($login, $serverId)
    {
        if (!isset($this->servers[$serverId])) {
            $this->console('Error: Server with id ' . $serverId . ' not found!');
            return;
        }

        /** @var Server $server */
        $server = $this->servers[$serverId];
        $sd     = $server->getServer_data();

        $playersFull = ((int)$sd->server->players->current >= (int)$sd->server->players->maximum);
        $specsFull   = ((int)$sd->server->spectators->current >= (int)$sd->server->spectators->maximum);

        $playerItems = array();
        $playerData  = array();
        $i = 0;
        foreach ($sd->current->players->player as $player) {
            $playerItems[$i] = array(
                '$000' . (string)$player->nickname,
                '$000' . (string)$player->login,
                // remove the first 2 parts  from nation
                '$000' . implode('|', array_slice(explode('|', (string)$player->nation), 2)),
                '$000' . (string)$player->ladder,
                '$fff' . ($player->spectator == 'true' ? '🎥' : '🎮'),
            );
            $playerData[$i] = array(-1, -1, -1, -1);
            $i++;
        }

        $w = $this->playerListWindow;
        $w->setParam("srvEnv", $w->handleSpecialChars((string)$sd->current->map->environment));
        $w->setParam("srvName", $w->handleSpecialChars((string)$sd->server->name));
        $w->setParam("srvPlayers", (string)$sd->server->players->current . '/' . (string)$sd->server->players->maximum);
        $w->setParam("srvPlayersColor", $playersFull ? 'F00' : '111');
        $w->setParam("srvSpecs", (string)$sd->server->spectators->current . '/' . (string)$sd->server->spectators->maximum);
        $w->setParam("srvSpecsColor", $specsFull ? 'F00' : '111');
        $w->setParam("srvLadder", (string)$sd->server->ladder->minimum . ' - ' . (string)$sd->server->ladder->maximum . 'k');
        $w->setParam("mapName", $w->handleSpecialChars((string)$sd->current->map->name));
        $w->setParam("mapAuthor", $w->handleSpecialChars((string)$sd->current->map->author));
        $w->setParam("mapAuthorTime", $w->handleSpecialChars((string)$sd->current->map->authortime));
        $w->setParam("joinLink", $w->handleSpecialChars('maniaplanet://#qjoin=' . (string)$sd->server->login . '@' . (string)$sd->server->title));
        $w->setParam("playerItems", $playerItems);
        $w->setParam("playerData", $playerData);
        $w->show($login);
    }

    public function showServerList($login)
    {
        $serverItems = array();
        $serverData  = array();
        $i = 0;

        foreach ($this->servers as $serverId => $server) {
            if (!$server->isOnline()) {
                continue;
            }
            $sd = $server->getServer_data();

            $playersFull = ((int)$sd->server->players->current >= (int)$sd->server->players->maximum);
            $specsFull   = ((int)$sd->server->spectators->current >= (int)$sd->server->spectators->maximum);

            $serverItems[$i] = array(
                '$111$i' . (string)$sd->current->map->environment,
                (string)$sd->server->name,
                ($playersFull ? '$F00' : '$111') . (string)$sd->server->players->current . '/' . (string)$sd->server->players->maximum,
                ($specsFull ? '$F00' : '$111') . (string)$sd->server->spectators->current . '/' . (string)$sd->server->spectators->maximum,
                '$111' . (string)$sd->server->ladder->minimum . ' - ' . (string)$sd->server->ladder->maximum . 'k',
                '$111' . (string)$sd->current->map->name,
                '$111' . (string)$sd->current->map->author,
                '$111' . (string)$sd->current->map->authortime,
            );
            $serverData[$i] = array('exp:eXpansion.ServerNeighborhood:showServerPlayers:' . $serverId);
            $i++;
        }

        $this->serverListWindow->setParam("serverItems", $serverItems);
        $this->serverListWindow->setParam("serverData", $serverData);
        $this->serverListWindow->show($login);
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;

        if ($this->serverListWindow instanceof Window) {
            $this->serverListWindow->erase();
        }
        $this->serverListWindow = null;

        if ($this->playerListWindow instanceof Window) {
            $this->playerListWindow->erase();
        }
        $this->playerListWindow = null;
    }
}
