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

namespace ManiaLivePlugins\eXpansion\Widgets_ChatEnhancement\Gui\Widgets;

use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\eXpansion\Chat\MetaData as ChatMetaData;
use ManiaLivePlugins\eXpansion\Gui\Widgets\PlainWidget;

class Chat extends PlainWidget
{

    protected $chatLogIcon;
    protected $chatState;

    public function onConstruct()
    {
        parent::onConstruct();

        /** @var PluginHandler $phandler */
        $phandler = PluginHandler::getInstance();

        $params = func_get_args();

        if ($phandler->isLoaded('\ManiaLivePlugins\eXpansion\Chatlog\Chatlog')) {
            $this->chatLogIcon = new \ManiaLive\Gui\Elements\Xml();
            $this->chatLogIcon->setContent(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(8, 8, null, array('Display Chat History'), null, null, null, null, $params[0], null, null, array('UIConstruction_Buttons', 'Text'), null, null, null));
            $this->addComponent($this->chatLogIcon);
        }

        $chatEnabled = true;
        if ($phandler->isLoaded('\ManiaLivePlugins\eXpansion\Chat\Chat')) {
            /** @var ChatMetaData $chatMeta */
            $chatMeta = ChatMetaData::getInstance();
            if (!$chatMeta->getVariable('publicChatActive')->getRawValue()) {
                $chatEnabled = false;
            }
        }

        $this->chatState = new \ManiaLive\Gui\Elements\Xml();
        $this->chatState->setContent('<frame posn="2 -4 30">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(4, 4, null, array('Is public chat active'), null, null, null, null, $params[1], null, null, array('Icons64x64_1', $chatEnabled ? 'LvlGreen' : 'LvlRed'), null, null, null) . '</frame>');
        $this->addComponent($this->chatState);

        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
    }
}
