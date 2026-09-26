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

namespace ManiaLivePlugins\eXpansion\Notifications\types;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ActionManager;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Notifications\MetaData;

/**
 * Description of HashListToggable
 *
 * @author Reaby
 */
class NotificationPluginlist extends \ManiaLivePlugins\eXpansion\Core\types\config\types\BasicList
{

    /** @var Window */
    private $confPluginListWindow;

    public function __construct($name, $visibleName = "", $configInstance = null, $scope = false, $showMain = false)
    {
        parent::__construct($name, $visibleName, $configInstance, $scope, $showMain);
        $this->setType(new \ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString(""));

        /** @var ActionManager */
        $aM = ActionManager::getInstance();
        $closeAction = $aM->createAction(array($this, 'confPluginListApply'));

        $this->confPluginListWindow = new Window("Notifications\Gui\Windows\ConfPluginList.xml");
        $this->confPluginListWindow->setName("ConfPluginList");
        $this->confPluginListWindow->setSize(100, 100);
        $this->confPluginListWindow->setTitle("Config selection");
        $this->confPluginListWindow->setParam("closeAction", $closeAction);
    }

    public function showConfWindow($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::EXPANSION_PLUGIN_SETTINGS)) {
            return;
        }
        
        $var  = MetaData::getInstance()->getVariable('redirectedPlugins');
        $list = ($var === null) ? array() : (array)$var->getRawValue();

        $plugins = array();
        foreach (AutoLoad::getAvailablePlugins() as $pluginId => $meta) {
            $plugins[] = array(
                'name'   => 'cb_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $pluginId),
                'text'   => $meta->getName(),
                'active' => in_array($pluginId, $list),
            );
        }

        $this->confPluginListWindow->setParam("plugins", $plugins);
        $this->confPluginListWindow->show($login);
    }

    public function hideConfWindow($login)
    {
        if ($this->confPluginListWindow instanceof Window) {
            $this->confPluginListWindow->erase($login);
        }
    }

    public function hasConfWindow()
    {
        return true;
    }

    public function confPluginListApply($login, $entries = array())
    {
        if (!AdminGroups::hasPermission($login, Permission::EXPANSION_PLUGIN_SETTINGS)) {
            return;
        }

        $outArray = array();
        foreach (AutoLoad::getAvailablePlugins() as $pluginId => $meta) {
            $cbName = 'cb_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $pluginId);
            if (isset($entries[$cbName]) && $entries[$cbName] == '1') {
                $outArray[] = (string)$pluginId;
            }
        }

        $var = MetaData::getInstance()->getVariable('redirectedPlugins');
        if ($var !== null) {
            $var->setRawValue($outArray);
        }

        $this->hideConfWindow($login);
    }
}
