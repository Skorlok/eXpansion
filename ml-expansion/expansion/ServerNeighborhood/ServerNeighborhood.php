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

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\ServerNeighborhood\Gui\Windows\PlayerList;
use ManiaLivePlugins\eXpansion\ServerNeighborhood\Gui\Windows\ServerList;
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

    private $actionHandler;
    private $action;

    private $widget;
    private $script;

    public function eXpOnInit()
    {
        $this->setVersion("1.5");
        $this->config = Config::getInstance();
        $this->setPublicMethod("showServerList");
    }

    public function eXpOnLoad()
    {
        /** @var ActionHandler @aH */
        $this->actionHandler = ActionHandler::getInstance();
        $this->action = $this->actionHandler->createAction(array($this, "showServerList"));

        Menu::addMenuItem("ServerNeighborhood",
            array("Server Neighborhood" => array(null, $this->action))
        );

        $this->initWidget();
    }

    public function initWidget()
    {
        $this->script = new Script("ServerNeighborhood/Gui/Scripts/Time");
        
        $this->widget = new Widget("ServerNeighborhood\Gui\Widgets\ServerNeighborhood.xml");
        $this->widget->setName("Server Neighborhood Panel");
        $this->widget->setLayer("normal");
        $this->widget->setParam("action", $this->action);
        $this->widget->setParam('ownLogin', $this->storage->serverLogin);
        $this->widget->setParam('title', 'Server Neighborhood');
        $this->widget->registerScript($this->script);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        if ($this->config->snwidget_isDockable) {
            $trayScript = new Script("Gui/Scripts/TrayWidget");
            $trayScript->setParam('isMinimized', 'True');
            $trayScript->setParam('autoCloseTimeout', $this->config->refresh_interval * 1000);
            $trayScript->setParam('posXMin', -33);
            $trayScript->setParam('posX', -33);
            $trayScript->setParam('posXMax', -3);
            $trayScript->setParam('specilaCase', '');
            $this->widget->registerScript($trayScript);
            $this->widget->setDisableAxis("x");
        }
    }

    public function eXpOnReady()
    {
        $this->server = new Server();
        $this->server->create_fromConnection($this->connection, $this->storage);

        $this->registerChatCommand('servers', 'showServerList', 0, true);

        $this->enableTickerEvent();
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
                $this->servers[$cleanIndex]->mlAction = $this->actionHandler->createAction(array($this, "showServerPlayers"), $this->servers[$cleanIndex]);
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

        $this->script->setParam('refresh_interval', $this->config->refresh_interval * 1000);

        $this->widget->setPosition($this->config->serverPanel_PosX, $this->config->serverPanel_PosY, 25);
        $this->widget->setSize(33, $sizeY * $this->config->nbElement + 9);
        $this->widget->setParam('refresh_interval', $this->config->refresh_interval);
        $this->widget->setParam('style', $this->config->style);
        $this->widget->setParam('nbFields', $this->config->nbElement);
        $this->widget->setParam('items', $servers);
        $this->widget->show(null, true);
    }

    public function showServerPlayers($login, $server)
    {
        PlayerList::Erase($login);
        $w = PlayerList::Create($login);
        $w->setTitle('ServerNeighborhood - Server Players');
        $w->setSize(120, 105);
        $w->setServer($server);
        $w->centerOnScreen();
        $w->show();
    }

    public function showServerList($login)
    {
        ServerList::Erase($login);
        $w = ServerList::Create($login);
        $w->setTitle('ServerNeighborhood - Server List');
        $w->setSize(120, 105);
        $w->setServers($this->servers);
        $w->centerOnScreen();
        $w->show();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
        ServerList::EraseAll();
        PlayerList::EraseAll();

        $this->actionHandler->deleteAction($this->action);
    }
}
