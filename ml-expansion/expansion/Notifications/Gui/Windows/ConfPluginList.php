<?php

/**
 * @author       Petri
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

namespace ManiaLivePlugins\eXpansion\Notifications\Gui\Windows;

use ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad;
use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted;
use ManiaLivePlugins\eXpansion\Gui\Elements\Pager;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use ManiaLivePlugins\eXpansion\Notifications\Gui\Controls\ItemPlugin;
use ManiaLivePlugins\eXpansion\Notifications\MetaData;

class ConfPluginList extends Window
{

    /**
     * @var Pager
     */
    public $pagerFrame = null;

    /**
     * @var ConfigManager
     */
    private $configManager = null;

    /**
     * @var ItemPlugin[]
     */
    private $items = array();

    private $buttonSave;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->pagerFrame = new Pager();
        $this->pagerFrame->setPosY(-2);

        $this->mainFrame->addComponent($this->pagerFrame);

        $this->configManager = ConfigManager::getInstance();

        $this->buttonSave = new \ManiaLive\Gui\Elements\Xml();
        $this->buttonSave->setContent('<frame posn="50 -3 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(30, 5, __('Save', $this->getRecipient()), null, null, null, null, null, $this->createAction(array($this, 'saveAction')), null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->buttonSave);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pagerFrame->setSize($this->getSizeX() - 3, $this->getSizeY() - 8);
    }

    public function populate(Variable $var)
    {
        $this->pagerFrame->clearItems();
        $this->items = array();

        $list = $var->getRawValue();

        foreach (AutoLoad::getAvailablePlugins() as $pluginId => $meta) {
            $item = new ItemPlugin($pluginId, $meta);
            if (in_array($pluginId, $list)) {
                $item->setStatus(true);
            }

            $this->items[] = $item;
            $this->pagerFrame->addItem($item);
        }
    }

    public function saveAction($login, $args)
    {
        $outArray = array();
        // sync checkboxes
        foreach ($this->items as $item) {
            foreach ($item->getComponents() as $component) {
                if ($component instanceof CheckboxScripted) {
                    $component->setArgs($args);
                }
            }
            if ($item->checkbox->getStatus()) {
                $outArray[] = (string)$item->pluginId;
            }
        }

        $var = MetaData::getInstance()->getVariable('redirectedPlugins');
        $var->setRawValue($outArray);
        $var->hideConfWindow($login);

    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = null;
        $this->pagerFrame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
