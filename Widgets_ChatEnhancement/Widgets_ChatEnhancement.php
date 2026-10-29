<?php
/**
 * @author       Oliver de Cramer (oliverde8 at gmail.com)
 * @copyright    GNU GENERAL PUBLIC LICENSE
 *                     Version 3, 29 June 2007
 *
 * PHP version 5.3 and above
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see {http://www.gnu.org/licenses/}.
 */

namespace ManiaLivePlugins\eXpansion\Widgets_ChatEnhancement;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Chat\MetaData as ChatMetaData;
use ManiaLivePlugins\eXpansion\Core\Events\PluginSettingChange;
use ManiaLivePlugins\eXpansion\Core\Events\PluginSettingChangeListener;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_ChatEnhancement extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin implements PluginSettingChangeListener
{
    /** @var StaticWidget */
    private $widget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->registerManialinkCallback('showChatLog');
        $this->registerManialinkCallback('toogleChatStatus');

        $this->widget = new StaticWidget('Widgets_ChatEnhancement\Gui\Widgets\Chat.xml');
        $this->widget->setName("Chat Enhancement");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(-161, -66, -60);
        if ($this->storage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }

        $this->updateWidget();

        Dispatcher::register(PluginSettingChange::getClass(), $this);
    }

    public function updateWidget($login = null)
    {
        $this->widget->setParam("hideChatLog", !$this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Chatlog\Chatlog'));
        $this->widget->setParam("chatStateIcon", $this->isPublicChatActive() ? "LvlGreen" : "LvlRed");
        $this->widget->show($login, $login === null);
    }

    private function isPublicChatActive()
    {
        if (!$this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Chat\Chat')) {
            return true;
        }

        /** @var ChatMetaData $chatMeta */
        $chatMeta = ChatMetaData::getInstance();

        return (bool)$chatMeta->getVariable('publicChatActive')->getRawValue();
    }

    public function hideWidget()
    {
        $this->widget->erase();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->updateWidget();
    }

    public function showChatLog($login)
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Chatlog\Chatlog')) {
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Chatlog\Chatlog', 'showLog', $login);
        }
    }

    public function toogleChatStatus($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Chat\Chat')) {
                $var = ChatMetaData::getInstance()->getVariable('publicChatActive');
                $var->setRawValue(!$var->getRawValue());

                $this->eXpChatSendServerMessage("#admin_action#Public chat is now #variable#" . ($var->getRawValue() ? "Enable" : "Disable"));
            } else {
                $this->eXpChatSendServerMessage("#admin_error#Custom Chat plugin needs to be enabled", $login);
            }
        }
    }

    /**
     * @param string $pluginId
     * @param Variable $var
     *
     * @return mixed
     */
    public function onPluginSettingsChange($pluginId, Variable $var)
    {
        if ($pluginId == '\ManiaLivePlugins\eXpansion\Chat\Chat' && $var->getName() == "publicChatActive") {
            $this->updateWidget();
        }
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();

        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;

        Dispatcher::unregister(PluginSettingChange::getClass(), $this);
    }
}
