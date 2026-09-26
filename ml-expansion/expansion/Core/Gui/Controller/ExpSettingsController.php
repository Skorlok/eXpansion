<?php

namespace ManiaLivePlugins\eXpansion\Core\Gui\Controller;

use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BasicList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ColorCode;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ConfigFile;
use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class ExpSettingsController
{
    /** @var \ManiaLivePlugins\eXpansion\Core\ConfigManager */
    private $configManager;

    /** @var Window */
    private $window;

    /** @var Window */
    private $listWindow;

    /** @var Window */
    private $confSwitcherWindow;

    const CONF_SWITCHER_DEFAULT_DIR = '1';
    const CONF_SWITCHER_LIBRARY_DIR = '0';
    const CONF_SWITCHER_LIBRARY_PATH = 'libraries/ManiaLivePlugins/eXpansion/Core/defaultConfigs';

    public function __construct($configManager)
    {
        $this->configManager = $configManager;

        $this->window = new Window("Core\Gui\Windows\ExpSettings.xml");
        $this->window->setName("ExpSettings");
        $this->window->setSize(170, 100);
        $this->window->setTitle("Expansion Settings");

        $this->listWindow = new Window("Core\Gui\Windows\ExpListSetting.xml");
        $this->listWindow->setName("ExpListSetting");
        $this->listWindow->setSize(140, 100);

        $this->confSwitcherWindow = new Window("Core\Gui\Windows\ConfSwitcher.xml");
        $this->confSwitcherWindow->setName("ConfSwitcher");
        $this->confSwitcherWindow->setSize(100, 100);
        $this->confSwitcherWindow->setTitle("Config selection");
    }

    public function show($login, $confName = 'main')
    {
        $sizeX      = ($confName == 'main') ? 170 : 140;
        $groupVars  = $this->configManager->getGroupedVariables($confName);
        $groupNames = array_keys($groupVars);
        $firstGroup = !empty($groupNames) ? $groupNames[0] : 'General';
        $this->buildAndShow($login, $confName, $firstGroup, $sizeX);
    }

    private function buildAndShow($login, $confName, $currentGroup, $sizeX = 140)
    {
        $groupVars  = $this->configManager->getGroupedVariables($confName);
        $groupsData = array();
        foreach ($groupVars as $gName => $vars) {
            $groupsData[] = array(
                'name'     => $gName,
                'action'   => 'exp:eXpansion.Core:expSettingsSwitchGroup:' . $confName . ':' . $gName . ':' . $sizeX,
                'selected' => ($gName == $currentGroup),
            );
        }

        $saveAction = 'exp:eXpansion.Core:expSettingsSave:' . $confName . ':' . $currentGroup . ':' . $sizeX;

        $settingsData = array();
        $i            = 0;
        $currentVars  = isset($groupVars[$currentGroup]) ? $groupVars[$currentGroup] : array();
        foreach ($currentVars as $var) {
            if (!$var->getVisible()) {
                continue;
            }
            $type        = $this->getType($var);
            $resetAction = null;
            $openAction  = null;

            if ($var->getDefaultValue() != null || $type == 'checkbox') {
                $resetAction = 'exp:eXpansion.Core:expSettingsResetVar:' . $confName . ':' . $currentGroup . ':' . $var->getName() . ':' . $sizeX;
            }
            if ($type == 'list') {
                $openAction = 'exp:eXpansion.Core:expSettingsOpenWin:' . $confName . ':' . $currentGroup . ':' . $var->getName();
            }

            $settingsData[] = array(
                'index'        => $i,
                'type'         => $type,
                'label'        => $var->getVisibleName(),
                'varName'      => $var->getName(),
                'value'        => ($type == 'list') ? '' : (string)$var->getRawValue(),
                'previewValue' => $var->getPreviewValues(),
                'desc'         => is_array($var->getDescription()) ? implode("\n", $var->getDescription()) : $var->getDescription(),
                'descLines'    => is_array($var->getDescription()) ? count($var->getDescription()) : 1,
                'isGlobal'     => $var->getIsGlobal(),
                'resetAction'  => $resetAction,
                'openAction'   => $openAction,
                'colorDigits'  => ($var instanceof ColorCode) ? $var->getUseFullHex() : 3,
                'colorPrefix'  => ($var instanceof ColorCode) ? $var->getUsePrefix()  : true,
            );
            $i++;
        }

        $this->window->setSize($sizeX, 100);
        $this->window->setParam("sizeX",      $sizeX);
        $this->window->setParam("groups",     $groupsData);
        $this->window->setParam("settings",   $settingsData);
        $this->window->setParam("saveAction", $saveAction);
        $this->window->show($login);
    }

    public function switchGroup($login, $compound)
    {
        list($confName, $groupName, $sizeX) = array_pad(explode(':', $compound, 3), 3, null);

        $this->buildAndShow($login, $confName, $groupName, (int)$sizeX);
    }

    public function save($login, $compound, $params = array())
    {
        list($confName, $groupName, $sizeX) = array_pad(explode(':', $compound, 3), 3, null);
        $sizeX = (int)$sizeX;

        $groupVars = $this->configManager->getGroupedVariables($confName);
        if (isset($groupVars[$groupName])) {
            foreach ($groupVars[$groupName] as $var) {
                if (!$var->getVisible()) {
                    continue;
                }
                $name = $var->getName();
                if ($var instanceof Boolean) {
                    $var->setValue(isset($params[$name]) && $params[$name] == '1');
                } elseif (!($var instanceof BasicList) && !($var instanceof HashList) && !($var instanceof SortedList) && !$var->hasConfWindow()) {
                    if (isset($params[$name])) {
                        $var->setValue($params[$name]);
                    }
                }
            }
        }
        $this->configManager->check();
        $this->buildAndShow($login, $confName, $groupName, $sizeX);
        $msg = eXpGetMessage("Settings are now saved!");
        \ManiaLivePlugins\eXpansion\Gui\Gui::showNotice($msg, $login);
    }

    public function resetVar($login, $compound)
    {
        list($confName, $groupName, $varName, $sizeX) = array_pad(explode(':', $compound, 4), 4, null);
        $sizeX = (int)$sizeX;

        $groupVars = $this->configManager->getGroupedVariables($confName);
        if (isset($groupVars[$groupName][$varName])) {
            $var = $groupVars[$groupName][$varName];
            $var->setRawValue($var->getDefaultValue());
            $this->configManager->check();
        }
        $this->buildAndShow($login, $confName, $groupName, $sizeX);
    }

    public function openWin($login, $compound)
    {
        list($confName, $groupName, $varName) = array_pad(explode(':', $compound, 3), 3, null);

        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var) {
            return;
        }
        if ($var instanceof ConfigFile) {
            $this->showConfSwitcher($login, $confName, $groupName, $varName, $var);
            return;
        }
        if ($var->hasConfWindow()) {
            $var->showConfWindow($login);
            return;
        }
        $this->showListWindow($login, $confName, $groupName, $varName, $var);
    }

    public function addListValue($login, $compound, $params = array())
    {
        list($confName, $groupName, $varName) = array_pad(explode(':', $compound, 3), 3, null);
        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var) {
            return;
        }

        $value = isset($params['value']) ? $params['value'] : '';
        if ($var instanceof HashList) {
            $key = isset($params['key']) ? trim($params['key']) : '';
            if ($key !== '') {
                $var->setValue($key, $value);
            }
        } else {
            $var->addValue($value);
        }
        $this->configManager->check();

        $this->showListWindow($login, $confName, $groupName, $varName, $var);
    }

    public function removeListValue($login, $compound)
    {
        list($confName, $groupName, $varName, $key) = array_pad(explode(':', $compound, 4), 4, null);
        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var) {
            return;
        }

        $var->removeValue($key);
        $this->configManager->check();

        $this->showListWindow($login, $confName, $groupName, $varName, $var);
    }

    public function confSwitcherLoad($login, $full)
    {
        list($confName, $groupName, $varName, $dirToken, $fileName) = array_pad(explode(':', $full, 5), 5, null);
        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var instanceof ConfigFile) {
            return;
        }

        $fileName = str_replace('–', '-', $fileName);
        $dir      = ($dirToken === self::CONF_SWITCHER_LIBRARY_DIR) ? self::CONF_SWITCHER_LIBRARY_PATH : ConfigManager::DIRNAME;

        $this->configManager->loadSettingsFrom(rtrim($dir, '/') . '/' . $fileName);
    }

    public function confSwitcherSave($login, $full)
    {
        list($confName, $groupName, $varName, $dirToken, $fileName) = array_pad(explode(':', $full, 5), 5, null);
        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var instanceof ConfigFile) {
            return;
        }

        $fileName = str_replace('–', '-', $fileName);
        $this->configManager->saveSettingsIn($fileName);
    }

    public function confSwitcherSelect($login, $full)
    {
        list($confName, $groupName, $varName, $dirToken, $fileName) = array_pad(explode(':', $full, 5), 5, null);
        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var instanceof ConfigFile) {
            return;
        }

        $fileName = str_replace('–', '-', $fileName);

        $this->configManager->loadSettingsFrom($fileName, false);
        $var->setValue(str_replace('.user.exp', '', $fileName));
        $this->configManager->check(true);

        $this->showConfSwitcher($login, $confName, $groupName, $varName, $var);
        $this->configManager->check();
    }

    public function confSwitcherSaveAs($login, $compound, $params = array())
    {
        list($confName, $groupName, $varName) = array_pad(explode(':', $compound, 3), 3, null);
        $var = $this->resolveVar($confName, $groupName, $varName);
        if (!$var instanceof ConfigFile) {
            return;
        }

        $name = isset($params['name']) ? trim($params['name']) : '';
        if ($name === '') {
            return;
        }

        $this->configManager->saveSettingsIn($name . '.user.exp');
        $this->showConfSwitcher($login, $confName, $groupName, $varName, $var);
    }

    private function showConfSwitcher($login, $confName, $groupName, $varName, ConfigFile $var)
    {
        $compound = $confName . ':' . $groupName . ':' . $varName;
        $current  = $var->getRawValue() . '.user.exp';
        $helper   = Helper::getPaths();

        $items = array();
        $data  = array();
        $i     = 0;

        $dirs = array(
            array(self::CONF_SWITCHER_DEFAULT_DIR, ConfigManager::DIRNAME),
            array(self::CONF_SWITCHER_LIBRARY_DIR, self::CONF_SWITCHER_LIBRARY_PATH),
        );
        foreach ($dirs as $dirEntry) {
            list($dirToken, $dir) = $dirEntry;
            $modify = ($dirToken === self::CONF_SWITCHER_DEFAULT_DIR);
            if (!is_dir($dir)) {
                continue;
            }
            foreach (scandir($dir) as $file) {
                if (!$helper->fileHasExtension($file, '.user.exp')) {
                    continue;
                }
                $isCurrent = $modify && $file == $current;
                $rowSuffix = $dirToken . ':' . $file;

                $items[$i] = array($file);
                $data[$i]  = array(
                    -1,
                    'exp:eXpansion.Core:confSwitcherLoad:' . $compound . ':' . $rowSuffix,
                    $modify ? ('exp:eXpansion.Core:confSwitcherSave:' . $compound . ':' . $rowSuffix) : -1,
                    ($modify && !$isCurrent) ? ('exp:eXpansion.Core:confSwitcherSelect:' . $compound . ':' . $rowSuffix) : -1,
                );
                $i++;
            }
        }

        $this->confSwitcherWindow->setParam("compound", $compound);
        $this->confSwitcherWindow->setParam("confItems", $items);
        $this->confSwitcherWindow->setParam("confData", $data);
        $this->confSwitcherWindow->show($login);
    }

    private function resolveVar($confName, $groupName, $varName)
    {
        $groupVars = $this->configManager->getGroupedVariables($confName);
        return isset($groupVars[$groupName][$varName]) ? $groupVars[$groupName][$varName] : null;
    }

    private function showListWindow($login, $confName, $groupName, $varName, \ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $isHash   = $var instanceof HashList;
        $compound = $confName . ':' . $groupName . ':' . $varName;

        $items = array();
        $data  = array();
        $i     = 0;
        foreach ((array)$var->getRawValue() as $key => $value) {
            $items[$i] = array($key, $value . ' ', '');
            $data[$i]  = array(-1, -1, 'exp:eXpansion.Core:removeListValue:' . $compound . ':' . $key);
            $i++;
        }

        $desc = $var->getDescription();
        $desc = is_array($desc) ? "" : $desc;

        $this->listWindow->setTitle("Expansion Settings: %s", array($var->getVisibleName()));
        $this->listWindow->setParam("help",                   $this->listWindow->handleSpecialChars($desc));
        $this->listWindow->setParam("hideKeyInput",           !$isHash);
        $this->listWindow->setParam("valueFieldPosX",         $isHash ? 58.5 : 0);
        $this->listWindow->setParam("valuePosX",              $isHash ? 67.5 : 12);
        $this->listWindow->setParam("valueWidth",             $isHash ? 56.5 : 115);
        $this->listWindow->setParam("addAction",              'exp:eXpansion.Core:addListValue:' . $compound);
        $this->listWindow->setParam("listItems",              $items);
        $this->listWindow->setParam("listData",               $data);
        $this->listWindow->show($login);
    }

    private function getType(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var instanceof Boolean) {
            return 'checkbox';
        }
        if ($var instanceof ColorCode) {
            return 'color';
        }
        if ($var instanceof BasicList || $var instanceof HashList || $var instanceof SortedList || $var->hasConfWindow()) {
            return 'list';
        }
        return 'entry';
    }

    public function destroy()
    {
        if ($this->window instanceof Window) {
            $this->window->erase();
        }
        $this->window = null;

        if ($this->listWindow instanceof Window) {
            $this->listWindow->erase();
        }
        $this->listWindow = null;

        if ($this->confSwitcherWindow instanceof Window) {
            $this->confSwitcherWindow->erase();
        }
        $this->confSwitcherWindow = null;
    }
}
