<?php

namespace ManiaLivePlugins\eXpansion\Menu;

use Exception;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Utilities\Logger;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Events\Event;
use ManiaLivePlugins\eXpansion\AdminGroups\Events\Listener;
use ManiaLivePlugins\eXpansion\AdminGroups\Group as AdmGroup;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ManiaLink;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Description of Menu2
 * Second attempt to create (optimized) menu
 *
 * @author Petri Järvisalo <petri.jarvisalo@gmail.com>
 * @reAuthor Skorlok
 */
class Menu extends ExpPlugin implements Listener
{
    /**
     * @var array
     */
    protected $menuWidgets = array();
    protected $menuItems = array();

    private static $additionalMenuItems = array();

    // This array will be merged with the default menu items
    public static function addMenuItem($widgetName, $data)
    {
        self::$additionalMenuItems[$widgetName] = $data;
    }

    public function eXpOnInit()
    {
        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();

        $this->menuItems = array(
            "Players" => array(null, $aH->createAction(array($this, "actionHandler"), "!players")),
            "Server Info" => array(null, $aH->createAction(array($this, "actionHandler"), "!serverinfo")),
            "Hud" => array(null, array(
                "Move" => array(null, $aH->createAction(array($this, "actionHandler"), "!hudMove")),
                "Lock" => array(null, $aH->createAction(array($this, "actionHandler"), "!hudLock")),
                "Reset" => array(null, $aH->createAction(array($this, "actionHandler"), "!hudReset")),
                "Config..." => array(null, $aH->createAction(array($this, "actionHandler"), "!hudConfig"))
            )),
            '$f00Admin' => array(null, array(
                "Instant Res" => array(Permission::MAP_RES, $aH->createAction(array($this, "actionHandler"), "!admres")),
                "Replay" => array(Permission::MAP_RES, $aH->createAction(array($this, "actionHandler"), "!admreplay")),
                "Skip" => array(Permission::MAP_SKIP, $aH->createAction(array($this, "actionHandler"), "!admskip")),
                "Extend" => array(Permission::GAME_SETTINGS, $aH->createAction(array($this, "actionHandler"), "!admext")),
                "End Round" => array(Permission::MAP_END_ROUND, $aH->createAction(array($this, "actionHandler"), "!admer")),
                "End WarmUp" => array(Permission::MAP_END_ROUND, $aH->createAction(array($this, "actionHandler"), "!admewu")),
                "End Round WarmUp" => array(Permission::MAP_END_ROUND, $aH->createAction(array($this, "actionHandler"), "!admewur")),
                "Start pause" => array(Permission::GAME_SETTINGS, $aH->createAction(array($this, "actionHandler"), "!admpause")),
                "End pause" => array(Permission::GAME_SETTINGS, $aH->createAction(array($this, "actionHandler"), "!admresume")),
                "Balance Teams" => array(Permission::TEAM_BALANCE, $aH->createAction(array($this, "actionHandler"), "!teambalance"))
            )),
            "Server Control" => array(Permission::SERVER_CONTROL_PANEL, array(
                "Control Panel" => array(Permission::SERVER_CONTROL_PANEL, $aH->createAction(array($this, "actionHandler"), "!admcontrol")),
                '$fffe$3afX$fffpansion Config' => array(Permission::EXPANSION_PLUGIN_SETTINGS, $aH->createAction(array($this, "actionHandler"), "!adm_settings")),
                "Plugin Manager" => array(Permission::EXPANSION_PLUGIN_START_STOP, $aH->createAction(array($this, "actionHandler"), "!adm_plugins"))
            )),
            "Maps" => array(null, array()),
            "Records" => array(null, array()),
            "Vote" => array(null, array(
                "Skip" => array(null, $aH->createAction(array($this, "actionHandler"), "!voteskip")),
                "Res" => array(null, $aH->createAction(array($this, "actionHandler"), "!voteres"))
            ))
        );
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enablePluginEvents();
        Dispatcher::register(Event::getClass(), $this);
        $this->prepareMenu();
    }

    public function eXpAdminAdded($login)
    {
        $group = AdminGroups::getAdmin($login)->getGroup();

        if (!isset($this->menuWidgets[$group->getGroupName()])) {
            $this->createMenu($group, $login);
        } else {
            $this->menuWidgets[$group->getGroupName()]->show($login);
        }
    }

    public function eXpAdminRemoved($login)
    {
        $group = AdminGroups::getInstance()->getGuestGroup();

        if (!isset($this->menuWidgets[$group->getGroupName()])) {
            $this->createMenu($group, $login);
        } else {
            $this->menuWidgets[$group->getGroupName()]->show($login);
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $admin = AdminGroups::getAdmin($login);
        if ($admin) {
            $group = $admin->getGroup();
        } else {
            $group = AdminGroups::getInstance()->getGuestGroup();
        }

        if (!isset($this->menuWidgets[$group->getGroupName()])) {
            $this->createMenu($group, $login);
        } else {
            $this->menuWidgets[$group->getGroupName()]->show($login);
        }
    }

    public function onPluginLoaded($pluginId)
    {
        $this->prepareMenu();
    }
    
    public function onPluginUnloaded($pluginId)
    {
        // remove everything before last slash
        for ($i = strlen($pluginId) - 1; $i >= 0; $i--) {
            if ($pluginId[$i] == "\\") {
                $pluginId = substr($pluginId, $i + 1);
                break;
            }
        }

        if (isset(self::$additionalMenuItems[$pluginId])) {
            unset(self::$additionalMenuItems[$pluginId]);
            $this->prepareMenu();
        }
    }

    public function prepareMenu()
    {
        $this->menuWidgets = array();
        foreach (AdminGroups::getGroupList() as $group) {
            $userGroup = array();
            foreach ($group->getGroupUsers() as $user) {
                if ($this->storage->getPlayerObject($user->getLogin())) {
                    $userGroup[] = $user->getLogin();
                }
            }
            if (!empty($userGroup)) {
                $this->createMenu($group, $userGroup);
            }
        }
    }

    /**
     * Creates the menu for the given group and shows it to the target logins.
     *
     * @param AdmGroup $group
     * @param array $targetLogins
     */
    public function createMenu(AdmGroup $group, $targetLogins)
    {
        $widget = new ManiaLink("Menu\Gui\Widgets\MenuWidget.xml");
        
        $ml = $this->buildMenuItems($this->getMenuItems(), $group, $widget, true);
        $script = new Script("Menu\Gui\Script");
        $script->setParam("itemCount", $ml[1]);

        $widget->setName("Menu");
        $widget->setLayer("normal");
        $widget->setPosition(0, 0, -60);
        $widget->setScripts($script);
        //$widget->setParam("menuItems", $this->getMenuItems());
        //$widget->setParam("group", $group);
        $widget->setParam("menuItems", $ml[0]);
        $widget->show($targetLogins);

        $this->menuWidgets[$group->getGroupName()] = $widget;
    }

    private function getMenuItems()
    {
        $items = $this->menuItems;

        // Add additional items from plugins
        if (count(self::$additionalMenuItems) > 0) {
            ksort(self::$additionalMenuItems);
            foreach (self::$additionalMenuItems as $data) {
                foreach ($data as $itemName => $itemData) {
                    if (is_array($itemData[1])) {
                        if (isset($items[$itemName])) {
                            $items[$itemName][1] = array_merge($items[$itemName][1], $itemData[1]);
                        } else {
                            $items[$itemName] = $itemData;
                        }
                    } else {
                        $items[$itemName] = $itemData;
                    }
                }
            }
        }

        return $items;
    }

    private function buildMenuItems(Array $items, admGroup $group, ManiaLink $ML, bool $isMain = false)
    {
        /** @var \ManiaLivePlugin\eXpansion\Gui\Config $config */
        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $counter = 0;
        $buff = '';
        foreach ($items as $itemName => $item) {
            if (is_array($item)) {
                if ($group->hasPermission($item[0])) {
                    if (is_array($item[1])) {
                        $subMenu = $this->buildMenuItems($item[1], $group, $ML);
                        if ($subMenu[1] > 0) {
                            $buff .= '<frame posn="0 -' . ($counter*5) . ' 5">';
                            $buff .= '<quad id="mQuad_' . ($counter+1) . '" sizen="30 5" halign="left" valign="center" bgcolor="' . $config->style_widget_bgColorize . '" bgcolorfocus="' . $config->style_widget_title_bgColorize . '" scriptevents="1" class="group menu item" data-label="' . $itemName . '"/>';
                            $buff .= '<label id="item_' . ($counter+1) . '" posn="2 0 1" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" textid="' . $ML->addLang($itemName) . '"/>';
                            $buff .= '<quad id="quad_' . ($counter+1) . '" posn="30 0 2" sizen="5 5" halign="right" valign="center" style="Icons64x64_1" substyle="ShowRight2"/>';
                            $buff .= '<frame posn="30 0 5" id="' . $itemName . '">';
                            $buff .= '<frame>';

                            $buff .= $subMenu[0];

                            $buff .= '</frame>';
                            $buff .= '</frame>';
                            $buff .= '</frame>';

                            $counter++;
                        }
                    } else {
                        if ($isMain) {
                            $buff .= '<frame posn="0 -' . ($counter*5) . ' 5">';
                            $buff .= '<quad id="mQuad_' . ($counter+1) . '" sizen="30 5" halign="left" valign="center" bgcolor="' . $config->style_widget_bgColorize . '" bgcolorfocus="' . $config->style_widget_title_bgColorize . '" action="' . $item[1] . '" scriptevents="1"/>';
                            $buff .= '<label id="item_' . ($counter+1) . '" posn="2 0 1" sizen="30 5" halign="left" valign="center" style="TextRaceChat" class="menu item" textsize="1" textcolor="fff" textid="' . $ML->addLang($itemName) . '"/>';
                            $buff .= '</frame>';
                        } else {
                            $buff .= '<frame posn="0 -' . ($counter*5) . ' 5">';
                            $buff .= '<quad sizen="30 5" halign="left" valign="center" bgcolor="' . $config->style_widget_bgColorize . '" bgcolorfocus="' . $config->style_widget_title_bgColorize . '" opacity="0.75" action="' . $item[1] . '" scriptevents="1" class="sub item"/>';
                            $buff .= '<label posn="2 0 1" sizen="30 5" halign="left" valign="center" style="TextRaceChat" class="sub item" textsize="1" textcolor="fff" textid="' . $ML->addLang($itemName) . '"/>';
                            $buff .= '</frame>';
                        }
                        $counter++;
                    }
                }
            }
        }
        return array($buff, $counter);
    }

    public function pluginLoaded($plugin)
    {
        return $this->isPluginLoaded($this->getPluginClass($plugin));
    }

    public function actionHandler($login, $action)
    {
        $adminGrp = AdminGroups::getInstance();
        try {
            switch ($action) {
                case "!players":
                    $this->callPublicMethod($this->getPluginClass("Players"), "showPlayerList", $login);
                    break;
                case "!admres":
                    $adminGrp->adminCmd($login, "restart");
                    break;
                case "!admreplay":
                    $adminGrp->adminCmd($login, "replay");
                    break;
                case "!admskip":
                    $adminGrp->adminCmd($login, "skip");
                    break;
                case "!admext":
                    $adminGrp->adminCmd($login, "extend");
                    break;
                case "!admpause":
                    $adminGrp->adminCmd($login, "pause");
                    break;
                case "!admresume":
                    $adminGrp->adminCmd($login, "resume");
                    break;
                case "!admer":
                    $adminGrp->adminCmd($login, "er");
                    break;
				case "!admewu":
                    $adminGrp->adminCmd($login, "ewu");
                    break;
				case "!admewur":
                    $adminGrp->adminCmd($login, "ewur");
                    break;
                case "!teambalance":
                    $adminGrp->adminCmd($login, "team balance");
                    break;
                case "!admcontrol":
                    $this->callPublicMethod($this->getPluginClass("Adm"), "serverControlMain", $login);
                    break;
                case "!hudMove":
                    $this->callPublicMethod($this->getPluginClass("Gui"), "hudCommands", $login, "move");
                    break;
                case "!hudLock":
                    $this->callPublicMethod($this->getPluginClass("Gui"), "hudCommands", $login, "lock");
                    break;
                case "!hudConfig":
                    $this->callPublicMethod($this->getPluginClass("Gui"), "hudCommands", $login, "config");
                    break;
                case "!hudReset":
                    $this->callPublicMethod($this->getPluginClass("Gui"), "hudCommands", $login, "reset");
                    break;
                case "!serverinfo":
                    $this->callPublicMethod($this->getPluginClass("Core"), "showInfo", $login);
                    break;
                case "!adm_plugins":
                    $adminGrp->adminCmd($login, "plugins");
                    break;
                case "!adm_settings":
                    $adminGrp->adminCmd($login, "setexp");
                    break;
                case "!adm_groups":
                    $adminGrp->adminCmd($login, "groups");
                    break;
                case "!adm_update":
                    $adminGrp->adminCmd($login, "update");
                    break;
                case "!voteres":
                    $this->connection->callVoteRestartMap();
                    break;
                case "!voteskip":
                    $this->connection->callVoteNextMap();
                    break;
                default:
                    $this->eXpChatSendServerMessage("not found: " . $action, $login);
                    Logger::info("menu command not found: " . $action);
                    break;
            }
        } catch (Exception $ex) {
            Logger::error("Error in Menu while running action : " . $action);
        }
    }

    /**
     *
     * @param string $plugin
     *
     * @return string
     */
    private function getPluginClass($plugin)
    {
        return "\\ManiaLivePlugins\\eXpansion\\" . $plugin . "\\" . $plugin;
    }
}
