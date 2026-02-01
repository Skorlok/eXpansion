<?php

namespace ManiaLivePlugins\eXpansion\AutoLoad\Gui\Windows;

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

use ManiaLive\Gui\Controls\Frame;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad;
use ManiaLivePlugins\eXpansion\AutoLoad\Gui\Controls\Plugin;
use ManiaLivePlugins\eXpansion\Core\types\config\MetaData;
use ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use ManiaLivePlugins\eXpansion\Gui\Elements\Pager;

class PluginList extends Window
{

    protected $input_name;
    protected $input_author;

    /** @var string /*
     * protected $value_name;
     *
     * /** @var  string
     */
    protected $value_author;

    protected $select_group;

    /**
     * @var Label
     */
    protected $label_group;

    /**
     * @var String
     */
    protected $value_group;

    protected $elements = array();

    protected $button_search;

    /**
     * @var Pager
     */
    public $pagerFrame = null;

    /**
     * @var PluginHandler
     */
    protected $pluginHandler = null;

    /**
     * @var Plugin[]
     */
    protected $items = array();

    /**
     * @var MetaData[]
     */
    protected $pluginList = array();

    /**
     * @var AutoLoad
     */
    protected $autoLoad;
    /** @var Frame */
    protected $frame;

    /** @var bool */
    public $firstDisplay = true;

    /** @var Frame */
    public $categories;

    protected $btn = [];

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $layout = new \ManiaLib\Gui\Layouts\Line();
        $layout->setMarginWidth(2);
        $this->frame = new Frame(33, -8, $layout);

        $this->addComponent($this->frame);

        $this->categories = new Frame();
        $this->mainFrame->addComponent($this->categories);

        $this->input_name = new \ManiaLive\Gui\Elements\Xml();
        $this->input_name->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("name", 35, true, __("Name", $login), null, null, null) . '</frame>');
        $this->frame->addComponent($this->input_name);

        $this->input_author = new \ManiaLive\Gui\Elements\Xml();
        $this->input_author->setContent('<frame posn="37 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("author", 35, true, __("Author", $login), null, null, null) . '</frame>');
        $this->frame->addComponent($this->input_author);

        $this->select_group = new Dropdown("group", array('Select'), 0, 25);
        $this->select_group->setPosX(74);
        $this->frame->addComponent($this->select_group);

        $this->button_search = new \ManiaLive\Gui\Elements\Xml();
        $this->button_search->setContent('<frame posn="101 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Search", $login), null, null, null, null, null, $this->createAction(array($this, "doSearch")), null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->button_search);

        $this->pagerFrame = new Pager();
        $this->pagerFrame->setPosition(33, -2);

        $this->mainFrame->addComponent($this->pagerFrame);

        $this->pluginHandler = PluginHandler::getInstance();

        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pagerFrame->setSize($this->getSizeX() - 33, $this->getSizeY() - 4);
    }


    /**
     * @param AutoLoad $autoLoader
     * @param MetaData[] $availablePlugins
     */
    public function populate(AutoLoad $autoLoader, $availablePlugins, $selectedGroup = null, $pluginName = null, $authorName = null)
    {

        $this->pluginList = $availablePlugins;
        $this->autoLoad = $autoLoader;
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = null;

        $this->pagerFrame->clearItems();

        $this->items = array();

        $groups = array();

        $i = 0;

        // $groups['All'] = true;

        foreach ($availablePlugins as $metaData) {
            if ($this->firstDisplay) {
                if (count($metaData->getGroups()) == 0) {
                    $groups["Other"] = true;
                }
                foreach ($metaData->getGroups() as $name) {
                    if ($name != "Core") {
                        $groups[$name] = true;
                    }
                }
            }
        }

        if ($this->firstDisplay) {
            $groups2 = array_keys($groups);
            sort($groups2, SORT_STRING);
            $this->elements = $groups2;
            $this->select_group->addItems($groups2);
            $this->select_group->setSelected(0);
            $this->value_group = $this->elements[0];
        }

        $x = 0;
        $this->categories->clearComponents();
        $this->btn = array();
        foreach ($this->elements as $idx => $group) {
            $this->btn[$idx] = new \ManiaLive\Gui\Elements\Xml();
            $this->btn[$idx]->setContent('<frame posn="0 -' . ($x*6.5) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, $group, null, null, ($selectedGroup == $idx ? '0b0' : null), null, null, $this->createAction(array($this, "setGroup"), $idx), null, null, null, null, null, null) . '</frame>');
            $this->categories->addComponent($this->btn[$idx]);
            $x++;
        }

        /** @var MetaData[] $metaData */
        foreach ($availablePlugins as $metaData) {

            if (!empty($pluginName) && strpos(strtoupper($metaData->getName()), strtoupper($pluginName)) === false) {
                continue;
            }

            if (!empty($authorName) && strpos(strtoupper($metaData->getAuthor()), strtoupper($authorName)) === false) {
                continue;
            }

            if (!empty($this->value_group) && $this->value_group != "All" && !in_array($this->value_group, $metaData->getGroups())) {
                continue;
            }

            // hide core plugins as you can't really load/unload them
            if (in_array("Core", $metaData->getGroups())) {
                continue;
            }

            $metaData->checkAll();
            $control = new Plugin(
                $i++,
                $autoLoader,
                $metaData,
                $this->getRecipient(),
                $this->pluginHandler->isLoaded($metaData->getPlugin())
            );
            $this->items[] = $control;
            $this->pagerFrame->addItem($control);
        }

        if ($this->firstDisplay) {
            $this->firstDisplay = false;
        }
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = null;
        $this->pagerFrame->destroy();
        $this->destroyComponents();
        $this->autoLoad = null;

        parent::destroy();
    }

    public function setGroup($login, $groupIndex, $params)
    {
        $this->input_name->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("name", 35, true, __("Name", $login), $params['name'], null, null) . '</frame>');
        $this->input_author->setContent('<frame posn="37 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("author", 35, true, __("Author", $login), $params['author'], null, null) . '</frame>');
        $this->value_group = $this->elements[$groupIndex];
        $this->populate($this->autoLoad, $this->pluginList, $groupIndex, $params['name'], $params['author']);
        $this->select_group->setSelected($groupIndex);
        $this->redraw($login);
    }

    public function doSearch($login, $params)
    {
        $this->input_name->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("name", 35, true, __("Name", $login), $params['name'], null, null) . '</frame>');
        $this->input_author->setContent('<frame posn="37 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("author", 35, true, __("Author", $login), $params['author'], null, null) . '</frame>');
        $this->value_group = $params['group'] == "" ? 0 : $this->elements[$params['group']];

        $this->populate($this->autoLoad, $this->pluginList, $params['group'], $params['name'], $params['author']);
        $this->select_group->setSelected($params['group']);   
        $this->redraw($login);
    }
}
