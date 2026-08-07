<?php

namespace ManiaLivePlugins\eXpansion\Core\Gui\Controller;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Core\Gui\Windows\ExpListSetting;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BasicList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ColorCode;
use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

class ExpSettingsController
{
    /** @var \ManiaLivePlugins\eXpansion\Core\ConfigManager */
    private $configManager;

    /** @var Window */
    private $window;

    /** @var array */
    private $loginActions = array();

    public function __construct($configManager)
    {
        $this->configManager = $configManager;

        $this->window = new Window("Core\Gui\Windows\ExpSettings.xml");
        $this->window->setName("ExpSettings");
        $this->window->setSize(170, 100);
        $this->window->setTitle("Expansion Settings");
        $this->window->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(10, 92));
        $this->window->registerCloseCallback(array($this, 'cleanActions'));
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
        $this->cleanActions($login);

        $ah      = ActionHandler::getInstance();
        $actions = array();

        $groupVars  = $this->configManager->getGroupedVariables($confName);
        $groupsData = array();
        foreach ($groupVars as $gName => $vars) {
            $switchAction = $ah->createAction(array($this, 'switchGroup'), $confName, $gName, $sizeX);
            $actions[]    = $switchAction;
            $groupsData[] = array(
                'name'     => $gName,
                'action'   => $switchAction,
                'selected' => ($gName == $currentGroup),
            );
        }

        $saveAction = $ah->createAction(array($this, 'save'), $confName, $currentGroup, $sizeX);
        $actions[]  = $saveAction;

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
                $resetAction = $ah->createAction(array($this, 'resetVar'), $confName, $currentGroup, $var->getName(), $sizeX);
                $actions[]   = $resetAction;
            }
            if ($type == 'list') {
                $openAction = $ah->createAction(array($this, 'openWin'), $confName, $currentGroup, $var->getName());
                $actions[]  = $openAction;
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

        $this->loginActions[$login] = $actions;

        $this->window->setSize($sizeX, 100);
        $this->window->setParam("sizeX",      $sizeX);
        $this->window->setParam("groups",     $groupsData);
        $this->window->setParam("settings",   $settingsData);
        $this->window->setParam("saveAction", $saveAction);
        $this->window->show($login);
    }

    public function switchGroup($login, $confName, $groupName, $sizeX, $params = array())
    {
        $this->buildAndShow($login, $confName, $groupName, $sizeX);
    }

    public function save($login, $confName, $groupName, $sizeX, $params = array())
    {
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

    public function resetVar($login, $confName, $groupName, $varName, $sizeX, $params = array())
    {
        $groupVars = $this->configManager->getGroupedVariables($confName);
        if (isset($groupVars[$groupName][$varName])) {
            $var = $groupVars[$groupName][$varName];
            $var->setRawValue($var->getDefaultValue());
            $this->configManager->check();
        }
        $this->buildAndShow($login, $confName, $groupName, $sizeX);
    }

    public function openWin($login, $confName, $groupName, $varName, $params = array())
    {
        $groupVars = $this->configManager->getGroupedVariables($confName);
        if (!isset($groupVars[$groupName][$varName])) {
            return;
        }
        $var = $groupVars[$groupName][$varName];
        if ($var->hasConfWindow()) {
            $var->showConfWindow($login);
        } else {
            ExpListSetting::Erase($login);
            /** @var ExpListSetting $win */
            $win = ExpListSetting::Create($login);
            $win->setTitle("Expansion Settings: " . $var->getVisibleName());
            $win->centerOnScreen();
            $win->setSize(140, 100);
            $win->populate($var);
            $win->show();
        }
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

    public function cleanActions($login)
    {
        if (!empty($this->loginActions[$login])) {
            $ah = ActionHandler::getInstance();
            foreach ($this->loginActions[$login] as $action) {
                $ah->deleteAction($action);
            }
        }
        $this->loginActions[$login] = array();
    }

    public function destroy()
    {
        if ($this->window instanceof Window) {
            $this->window->erase();
        }
        $this->window = null;

        $ah = ActionHandler::getInstance();
        foreach ($this->loginActions as $actions) {
            foreach ($actions as $action) {
                $ah->deleteAction($action);
            }
        }
        $this->loginActions = array();
    }
}
