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

namespace ManiaLivePlugins\eXpansion\Core\Gui\Windows;

use ManiaLive\Gui\Controls\Pager;
use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Core\Gui\Controls\ConfElement;
use ManiaLivePlugins\eXpansion\Core\MetaData;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class ConfSwitcher extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
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
     * @var Plugin[]
     */
    protected $items = array();

    protected $input;
    protected $buttonSave;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->pagerFrame = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->pagerFrame->setPosY(-2);

        $this->mainFrame->addComponent($this->pagerFrame);

        $this->configManager = ConfigManager::getInstance();

        $this->input = new \ManiaLive\Gui\Elements\Xml();
        $this->input->setContent('<frame posn="0 -3 1" scale="0.8">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("name", 65, true, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->input);

        $this->buttonSave = new \ManiaLive\Gui\Elements\Xml();
        $this->buttonSave->setContent('<frame posn="56.25 -3 1" scale="1.066666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(25, 5, __("Save", $this->getRecipient()), null, null, null, null, null, $this->createAction(array($this, 'saveAction')), null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->buttonSave);

        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pagerFrame->setSize($this->getSizeX() - 3, $this->getSizeY() - 8);
    }


    public function populate(Variable $var)
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = null;

        $this->pagerFrame->clearItems();
        $this->items = array();

        $this->populateFromDir($var, ConfigManager::DIRNAME, true);
        $this->populateFromDir($var, 'libraries/ManiaLivePlugins/eXpansion/Core/defaultConfigs', false);
    }

    public function populateFromDir($var, $dir, $diff)
    {
        $helper = Helper::getPaths();

        if (is_dir($dir)) {
            $subFiles = scandir($dir);
            $i = 0;
            foreach ($subFiles as $file) {
                if ($helper->fileHasExtension($file, '.user.exp')) {
                    $item = new ConfElement($i, $file, $file == ($var->getRawValue() . '.user.exp'), $diff, $this->getRecipient(), $dir);
                    $i++;
                    $this->items[] = $item;
                    $this->pagerFrame->addItem($item);
                }
            }
        }
    }

    public function saveAction($login, $params)
    {
        $name = $params['name'];
        if ($name != "") {
            $name .= '.user.exp';
            /** @var ConfigManager $confManager */
            $confManager = ConfigManager::getInstance();

            $confManager->saveSettingsIn($name);
            $var = MetaData::getInstance()->getVariable('saveSettingsFile');
            $var->hideConfWindow($login);
            $var->showConfWindow($login);
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
        parent::destroy();
    }
}
