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

namespace ManiaLivePlugins\eXpansion\AutoLoad\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad;
use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Core\Gui\Windows\ExpSettings;
use ManiaLivePlugins\eXpansion\Core\types\config\MetaData;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;

class Plugin extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    /**
     * @var AutoLoad
     */
    protected $autoLoad;

    /**
     * @var MetaData
     */
    protected $metaData;

    protected $button_running;
    protected $button_titleComp;
    protected $button_gameComp;
    protected $button_otherComp;
    protected $button_more;
    protected $button_start;

    /**
     * @var \ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround
     */
    protected $bg;

    /**
     * @var Label
     */
    protected $label_name;
    protected $label_author;

    /**
     * @var Quad
     */
    protected $icon_name;
    protected $icon_author;

    /**
     * @var ConfigManager
     */
    protected $configManger = null;

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n", "\t", "\r"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;', '&#9;', '&#13;'), $string);
    }

    public function __construct($indexNumber, AutoLoad $autoload, MetaData $plugin, $login, $isLoaded)
    {

        $this->metaData = $plugin;
        $this->autoLoad = $autoload;
        $toggleAction = $this->createAction(array($this, "togglePlugin"));
        $this->configManger = ConfigManager::getInstance();

        $this->bg = new ListBackGround($indexNumber, 120, 4);
        $this->addComponent($this->bg);

        $titleCompatible = $plugin->checkTitleCompatibility();
        $gameCompatible = $plugin->checkGameCompatibility();
        $otherCompatible = $plugin->checkOtherCompatibility();
        $isInStart = $autoload->isInStartList($plugin->getPlugin());

        if ($isLoaded) {
            $button_running_colorize = '0f0';
        } else {
            if ($isInStart) {
                $button_running_colorize = 'ff0';
            } else {
                $button_running_colorize = 'f00';
            }
        }
        $this->button_running = new \ManiaLive\Gui\Elements\Xml();
        $this->button_running->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(8, 8, null, array(__($this->getRunningDescriptionText($isLoaded, $isInStart), $login), 100), null, $button_running_colorize, null, null, $toggleAction, null, null, array('Icons64x64_1', 'GenericButton'), null, null, null) . '</frame>');
        $this->addComponent($this->button_running);

        $this->label_name = new Label(40, 4);
        $this->label_name->setTextSize(2);
        $this->label_name->setText($plugin->getName() == "" ? $plugin->getPlugin() : $plugin->getName());
        $this->label_name->setPosition(8, 3);
        $this->addComponent($this->label_name);

        $this->label_author = new Label(40, 4);
        $this->label_author->setStyle("TextCardScores2");
        $this->label_author->setTextSize(1);

        $this->label_author->setText('$i' . $plugin->getDescription());
        $this->label_author->setPosition(8, -0.5);
        $this->addComponent($this->label_author);

        if ($titleCompatible) {
            $button_titleComp_colorize = '090';
        } else {
            $button_titleComp_colorize = 'f00';
        }
        $this->button_titleComp = new \ManiaLive\Gui\Elements\Xml();
        $this->button_titleComp->setContent('<frame posn="102 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(7, 7, null, array(__($this->getTitleDescriptionText($titleCompatible), $login), 100), null, $button_titleComp_colorize, null, null, null, null, null, array('Icons64x64_1', 'GenericButton'), null, null, null) . '</frame>');
        $this->addComponent($this->button_titleComp);

        if ($gameCompatible) {
            $button_gameComp_colorize = '090';
        } else {
            $button_gameComp_colorize = 'f00';
        }
        $this->button_gameComp = new \ManiaLive\Gui\Elements\Xml();
        $this->button_gameComp->setContent('<frame posn="108 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(7, 7, null, array(__($this->getGameDescriptionText($gameCompatible), $login), 100), null, $button_gameComp_colorize, null, null, null, null, null, array('Icons64x64_1', 'GenericButton'), null, null, null) . '</frame>');
        $this->addComponent($this->button_gameComp);

        if (empty($otherCompatible)) {
            $button_otherComp_colorize = '090';
        } else {
            $button_otherComp_colorize = 'f00';
        }
        $otherCompatible = $this->handleSpecialChars($this->getOtherDescriptionText($otherCompatible));
        $this->button_otherComp = new \ManiaLive\Gui\Elements\Xml();
        $this->button_otherComp->setContent('<frame posn="114 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(7, 7, null, array(__($otherCompatible[0], $login), 100, 5*$otherCompatible[1], sizeof($otherCompatible) + 1), null, $button_otherComp_colorize, null, null, null, null, null, array('Icons64x64_1', 'GenericButton'), null, null, null) . '</frame>');
        $this->addComponent($this->button_otherComp);

        $configs = $this->configManger->getGroupedVariables($this->metaData->getPlugin());
        if (!empty($configs)) {
            $this->button_more = new \ManiaLive\Gui\Elements\Xml();
            $this->button_more->setContent('<frame posn="84 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(22, 7, __("Settings", $login), null, null, null, null, null, $this->createAction(array($this, 'showPluginSettings')), null, null, null, null, null, null) . '</frame>');
            $this->addComponent($this->button_more);
        }

        if ($this->getStartText($isLoaded, $isInStart) == "Start") {
            $button_start_colorize = "0D0";
        } else {
            $button_start_colorize = "F00";
        }
        $this->button_start = new \ManiaLive\Gui\Elements\Xml();
        $this->button_start->setContent('<frame posn="121 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(14, 7, __($this->getStartText($isLoaded, $isInStart), $login), null, null, $button_start_colorize, null, null, $toggleAction, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->button_start);

        $this->setSize(122, 8);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->label_name->setSizeX(($this->getSizeX() - $this->label_name->getPosX() - 5 * 8 - 7) / 1);
        $this->label_author->setSizeX(($this->getSizeX() - $this->label_author->getPosX() - 5 * 8 - 7) / 1);

        $this->bg->setSize($this->getSizeX() + 3, $this->getSizeY());
    }

    private function getRunningDescriptionText($running, $inStart)
    {
        if ($running) {
            return "Plugin is running. Click to unload!";
        } else {
            if ($inStart) {
                return "Plugin not compatible with game mode, title or server settings.\n Plugin will be enabled when possible.";
            } else {
                return "Plugin not running. Click to load!";
            }
        }
    }

    private function getTitleDescriptionText($titleCompatible)
    {
        if ($titleCompatible) {
            return "This plugin is compatible with the current Title";
        } else {
            return "This plugin isn't compatible with the current Title";
        }
    }

    private function getGameDescriptionText($gameCompatible)
    {
        if ($gameCompatible) {
            return "This plugin is compatible with the current Game mode";
        } else {
            return "This plugin isn't compatible with the current Game mode";
        }
    }

    private function getOtherDescriptionText($otherCompatibility)
    {
        if (empty($otherCompatibility)) {
            return array("This plugin is is compatible with current installation", 1);
        } else {
            return array("This plugin has a few compatibility issues : \n" . implode("\n", $otherCompatibility), count($otherCompatibility) + 1);
        }
    }

    private function getStartText($started, $inStart)
    {
        if ($inStart || $started) {
            return "Stop";
        } else {
            return "Start";
        }
    }

    public function togglePlugin($login)
    {
        $this->autoLoad->togglePlugin($login, $this->metaData);
    }

    public function showPluginSettings($login)
    {
        ExpSettings::Erase($login);
        /** @var ExpSettings $win */
        $win = ExpSettings::Create($login);
        $win->setTitle("Expansion Settings");
        $win->centerOnScreen();
        $win->setSize(140, 100);
        $win->populate($this->configManger, 'General', $this->metaData->getPlugin());
        $win->show();
    }
}
