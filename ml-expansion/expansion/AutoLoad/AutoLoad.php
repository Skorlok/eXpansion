<?php

namespace ManiaLivePlugins\eXpansion\AutoLoad;

use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AutoLoad\Structures\PluginNotFoundException;
use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Core\Events\ConfigLoadEvent;
use ManiaLivePlugins\eXpansion\Core\types\config\MetaData as MetaDataType;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

class AutoLoad extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /**
     * @var string[] Plugins to be loaded.
     */
    private $plugins;

    /**
     * @var MetaDataType[] List of all plugins that can be autoloaded.
     */
    private $availablePlugins;

    /**
     * @var MetaDataType[] List of all plugins that can be autoloaded.
     */
    private static $allAvailablePlugins;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string[] List of plugins that needs to be unloaded
     */
    private $toBeRemoved = array();

    /**
     * @var string[] List o plugins to be loaded according the configuration;
     */
    private $configPlugins = array();

    /** @var Window */
    protected $pluginListWindow;
    protected $pluginListGroups        = array();
    protected $pluginListPlayerState    = array();

    /**
     * @inheritdoc
     */
    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents(\ManiaLive\DedicatedApi\Callback\Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);
        $this->setPublicMethod('showPluginsWindow');
        Dispatcher::register(ConfigLoadEvent::getClass(), $this, ConfigLoadEvent::ON_CONFIG_FILE_LOADED);
        $this->console("AutoLoading eXpansion pack ... ");

        try {
            /**
             * @var Config $config
             */
            $config = Config::getInstance();
            $this->config = $config;

            //List of plugins that must be loaded always !!
            $this->plugins = array(
                '\ManiaLivePlugins\eXpansion\Core\Core',
                '\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups',
                '\ManiaLivePlugins\eXpansion\AutoUpdate\AutoUpdate',
                '\ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin',
                '\ManiaLivePlugins\eXpansion\Gui\Gui',
                '\ManiaLivePlugins\eXpansion\Adm\Adm',
                '\ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad',
                '\ManiaLivePlugins\eXpansion\Database\Database'
            );

            $this->findAvailablePlugins();

            // adding menu as last (so it's last when eXpOnReady is called, and all other plugins are done.
            ConfigManager::getInstance()->loadSettings();

            //We Need the plugin Handler
            $pHandler = PluginHandler::getInstance();

            $this->autoLoadPlugins($this->plugins, $pHandler);

        } catch (\exception $ex) {
            $this->console("[AutoLoad] Error while loading Core plugins!" . $ex->getMessage());
            AdminGroups::getInstance()->announceToPermission(Permission::SERVER_ADMIN, '[AutoLoad] Error while starting expansion core. See console for more info.');
        }

        // do event to inform autoload is complete;
        Dispatcher::dispatch(
            new \ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent(
                \ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent::ON_AUTOLOAD_COMPLETE
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function eXpOnReady()
    {
        $this->registerManialinkCallback('pluginListShowConfig', false, true);
        $this->registerManialinkCallback('pluginListToggle', true, true);
        $this->registerManialinkCallback('pluginListSetGroup', true, true);
        $this->registerManialinkCallback('pluginListSearch', true);
        
        //We Need the plugin Handler
        $pHandler = PluginHandler::getInstance();

        // Normalize plugin names.
        $plugins = array();
        $changed = false;
        foreach ($this->config->plugins as $pname) {
            if ($pname[0] != '\\') {
                $pname = '\\' . $pname;
                $changed = true;
            }
            $plugins[] = $pname;
        }

        if ($changed) {
            $this->config->plugins = $plugins;
            // Save new settings
            ConfigManager::getInstance()->registerValueChange($this->getMetaData()->getVariable('plugins'));
            ConfigManager::getInstance()->check();

            // Log possible crash.
            $this->console('');
            $this->console('Settings updated !!');
            $this->console('eXpansion might crash at this point, please restart it.');
        }

        try {
            $this->autoLoadPlugins($this->config->plugins, $pHandler);
        } catch (\exception $ex) {
            $this->console("[AutoLoad] Error while AutoLoading additional plugins!" . $ex->getMessage());
            AdminGroups::getInstance()->announceToPermission(Permission::SERVER_ADMIN, '[AutoLoad] Error while starting optional plugins. See console for more info.');
        }

        $this->autoLoadPlugins(array('\\ManiaLivePlugins\\eXpansion\\Menu\\Menu'), $pHandler);

        if (!empty($this->toBeRemoved)) {
            $this->cleanPluginsArray($this->config->plugins, $this->toBeRemoved);
            ConfigManager::getInstance()->registerValueChange($this->getMetaData()->getVariable('plugins'));
            ConfigManager::getInstance()->check();
        }
        $this->configPlugins = $this->config->plugins;

        AdminGroups::addAdminCommand('plugins', $this, 'showPluginsWindow', Permission::EXPANSION_PLUGIN_START_STOP);

        // Compute groups from available plugins (same logic as old PluginList::populate firstDisplay)
        $groups = array();
        foreach ($this->availablePlugins as $metaData) {
            if (count($metaData->getGroups()) == 0) {
                $groups["Other"] = true;
            }
            foreach ($metaData->getGroups() as $name) {
                if ($name != "Core") {
                    $groups[$name] = true;
                }
            }
        }
        $groupNames = array_keys($groups);
        sort($groupNames, SORT_STRING);
        $this->pluginListGroups = $groupNames;

        $this->pluginListWindow = new Window("AutoLoad\Gui\Windows\PluginList.xml");
        $this->pluginListWindow->setName("PluginList");
        $this->pluginListWindow->setSize(172, 90);
        $this->pluginListWindow->setTitle("Plugin List");
        $this->pluginListWindow->registerCloseCallback(array($this, 'onPluginListWindowClosed'));
        $this->pluginListWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(8, 86));
    }

    /**
     * Called when the config file is loaded.
     */
    public function onConfigFileLoaded()
    {
        $toRemove = array();
        foreach ($this->configPlugins as $plugin) {
            if (!in_array($plugin, $this->config->plugins)) {
                $toRemove[] = $plugin;
            }
        }

        $toAdd = array();
        foreach ($this->config->plugins as $plugin) {
            if (!in_array($plugin, $this->configPlugins)) {
                $toAdd[] = $plugin;
            }
        }

        $pHandler = PluginHandler::getInstance();

        if (!empty($toRemove)) {
            foreach ($toRemove as $plugin) {
                if ($pHandler->isLoaded($plugin)) {
                    $pHandler->callPublicMethod($this, $plugin, 'eXpUnload', array());
                }
            }
        }

        if (!empty($toAdd)) {
            $this->autoLoadPlugins($toAdd, $pHandler);
        }
        $this->configPlugins = $this->config->plugins;
    }

    /**
     * Removed from a plugin list plugins that needs to be removed
     *
     * @param string[] $plugins List of plugins
     *
     * @param string[] $toRemove List pf plugins to be removed
     */
    private function cleanPluginsArray(&$plugins, $toRemove)
    {
        foreach ($toRemove as $plugin) {
            $pos = array_search($plugin, $plugins);
            if ($pos !== false) {
                unset($plugins[$pos]);
            }
        }
    }

    /**
     * Autoload a list plugins.
     *
     * This method will try and solve dependecies, so start plugins needed to start the plugins asked.
     *
     * @param string[]      $plugins  List of plugins to autoload
     * @param PluginHandler $pHandler The manialive plugin handler
     *
     * @throws \Maniaplanet\DedicatedServer\InvalidArgumentException
     *
     * @return bool
     */
    public function autoLoadPlugins($plugins, PluginHandler $pHandler)
    {
        //First attempt to load plugins
        $recheck = $this->loadPlugins($plugins, $pHandler);

        // New attempts to load plugins. If the list of plugins that needs checking don't change
        // it means we can't resolve the remaining dependencies.
        do {
            $lastSize = sizeof($recheck);
            $recheck = $this->loadPlugins($plugins, $pHandler);
        } while (!empty($recheck) && $lastSize != sizeof($recheck));

        foreach ($plugins as $pname) {
            $pHandler->ready($pname);
        }

        //If all plugins couldn't be loaded
        if (!empty($recheck)) {
            $this->eXpChatSendServerMessage(
                "couldn't Autoload all required plugins, see console log for more details."
            );
            $this->console(
                "Not all required plugins were loaded, "
                ."due to unmet dependencies or errors. list of not loaded plugins: "
            );
            foreach ($recheck as $pname) {
                $this->console($pname);
                $this->connection->chatSendServerMessage('Starting ' . $pname . '........$f00 Failure');
            }

            return false;
        }

        return true;
    }

    /**
     * Try to load multiple plugins.
     *
     * @param string[]      $list     List of plugins to load.
     * @param PluginHandler $pHandler The manialive plugin handler.
     *
     * @return array list of plugins that couldn't be loaded due to dependencies
     */
    public function loadPlugins($list, PluginHandler $pHandler)
    {
        //List of plugins that we coudln't load that we will recheck
        $recheck = array();

        foreach ($list as $pname) {
            try {
                if (!$this->loadPlugin($pname, $pHandler)) {
                    $recheck[] = $pname;
                }
            } catch (PluginNotFoundException $ex) {
                $this->toBeRemoved[] = $pname;
            }
        }

        return $recheck;
    }

    /**
     * Try and load a plugin. Will check for dependecies all the other criteries that allows a plugin to start.
     *
     * @param string        $pname    The name of the plugin to load
     * @param PluginHandler $pHandler The manialive plugin handler.
     *
     * @return bool
     * @throws PluginNotFoundException
     */
    public function loadPlugin($pname, PluginHandler $pHandler)
    {
        //List of plugins that were disabled
        $disabled = Config::getInstance()->disable;
        if (!is_array($disabled)) {
            $disabled = array($disabled);
        }

        try {
            if (!$pHandler->isLoaded($pname)) {
                if (in_array($pname, $disabled)) {
                    $this->console("[" . $pname . "]...Disabled -> not loading");
                } else {
                    if (!class_exists($pname)) {
                        $this->console("[" . $pname . "]...Doesen't exist -> not loading");
                        throw new PluginNotFoundException($pname);
                    }
                    /** @var MetaDataType $metaData */
                    $metaData = $pname::getMetaData();

                    $this->availablePlugins[$pname] = $metaData;
                    self::$allAvailablePlugins[$pname] = $metaData;

                    if (!$metaData->checkForPluginIncompatibility($pHandler->getLoadedPluginsList())) {
                        $this->console(
                            "[" . $pname . "]...Disabled -> Not Compatible : either can't run with a certain plugin "
                            ."or a loaded plugin can't with this plugin"
                        );
                        return false;
                    } elseif ($metaData->checkAll()) {
                        try {
                            $status = $pHandler->load($pname, false);
                        } catch (\Exception $ex) {
                            try {
                                $pHandler->unload($pname);
                            } catch (\Exception $ex) {

                            }
                            $status = false;
                        }

                        if (!$status) {
                            $this->console("[" . $pname . "]...FAIL -> will retry");
                            $recheck[] = $pname;
                        } else {
                            $this->debug("[" . $pname . "]...SUCCESS");
                        }
                    } else {
                        // @TODO display in the logs why not compatible.
                        $this->console("[" . $pname . "]...Disabled -> Not Compatible");
                    }
                }
            }
        } catch (PluginNotFoundException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            $this->console("[" . $pname . "]...FAIL -> will retry");
            return false;
        }

        return true;
    }

    /**
     * Start or stop a certain plugin.
     *
     * @param string       $login    Login of the user that tries to start or stop the process.
     * @param MetaDataType $metaData The metadata of the plugin we try to start.
     */
    public function togglePlugin($login, MetaDataType $metaData)
    {
        /**
         * @var PluginHandler $pHandler
         */
        $pHandler = PluginHandler::getInstance();
        $pluginId = $metaData->getPlugin();

        if ($this->isInStartList($pluginId) || $pHandler->isLoaded($pluginId)) {
            if (in_array($pluginId, $this->plugins)) {
                $this->eXpChatSendServerMessage(
                    "#admin_error#This plugin is a core element of eXpansion. It can't be unloaded",
                    $login
                );
            } else {
                $pos = array_search($pluginId, Config::getInstance()->plugins);
                if ($pos !== false) {
                    unset($this->config->plugins[$pos]);
                    ConfigManager::getInstance()->registerValueChange($this->getMetaData()->getVariable('plugins'));
                    ConfigManager::getInstance()->check();
                }
                if ($pHandler->isLoaded($pluginId)) {
                    $pHandler->callPublicMethod($this, $pluginId, 'eXpUnload', array());
                }
                $this->eXpChatSendServerMessage("#admin_action#Plugin stopped with success", $login);
            }
        } else {
            if ($this->autoLoadPlugins(array($pluginId), $pHandler)) {
                $this->eXpChatSendServerMessage("#admin_action#Plugin started with success", $login);
            } else {
                $this->eXpChatSendServerMessage(
                    "#admin_error#This plugin contains errors that prevented it from starting",
                    $login
                );
            }

            $this->config->plugins[] = $pluginId;

            ConfigManager::getInstance()->registerValueChange($this->getMetaData()->getVariable('plugins'));
            ConfigManager::getInstance()->check();
        }

        $this->showPluginsWindow($login);
        $this->configPlugins = $this->config->plugins;
    }

    /**
     * Find all available plugins in the available plugin paths
     */
    protected function findAvailablePlugins()
    {
        /**
         * @var Config $config
         */
        $config = Config::getInstance();

        foreach ($config->pluginPaths as $path => $depth) {
            $this->findAvailablePluginsInPath($path, $depth);
        }
    }

    /**
     * Search for plugins in a certain path at a certain depth
     *
     * @param $path
     * @param $depth
     */
    protected function findAvailablePluginsInPath($path, $depth)
    {
        if ($depth < 0) {
            return;
        } else {
            if (is_dir($path)) {
                $subFiles = scandir($path);

                if (in_array('MetaData.php', $subFiles)) {
                    $this->loadAvailablePluginMetaDataFromPath($path);
                } else {
                    foreach ($subFiles as $file) {
                        if ($file === '.' || $file === '..') {
                            continue;
                        }
                        if (is_dir($path . '/' . $file)) {
                            $this->findAvailablePluginsInPath($path . '/' . $file, $depth - 1);
                        }
                    }
                }
            } else {
                $this->console("Unknown plugin path : $path");
            }
        }
    }

    /**
     * Loads plugin metadata using plugins path.
     *
     * @param $path
     */
    protected function loadAvailablePluginMetaDataFromPath($path)
    {
        $classes = get_declared_classes();
        require_once $path . '/MetaData.php';
        $diff = array_diff(get_declared_classes(), $classes);
        $className = reset($diff);

        $exploded = explode('\\', $className);
        array_pop($exploded);
        $size = sizeof($exploded);
		if ($size != 0) {
			$pluginId = implode('\\', $exploded) . '\\' . $exploded[$size - 1];

			if ($pluginId[0] != '\\') {
				$pluginId = '\\' . $pluginId;
			}

			if (class_exists($pluginId)) {
				/**
				 * @var MetaDataType $metaData
				 */
				$metaData = $className::getInstance($pluginId);
				if ($metaData->getPlugin() == null) {
					$metaData->setPlugin($pluginId);
				} else {
					$pluginId = $metaData->getPlugin();
				}

				$this->availablePlugins[$pluginId] = $metaData;
				self::$allAvailablePlugins[$pluginId] = $metaData;

				uasort($this->availablePlugins, array($this, 'pluginNameCmp'));
				uasort(self::$allAvailablePlugins, array($this, 'pluginNameCmp'));
			}
		}
    }

    /**
     * Compare 2 plugins to sort them properly.
     *
     * @param MetaDataType $a Metadata of the first plugin
     * @param MetaDataType $b Metadata of the second plugin
     *
     * @return int
     */
    public function pluginNameCmp(MetaDataType $a, MetaDataType $b)
    {
        if ($a->getName() == $b->getName()) {
            return 0;
        }

        return ($a->getName() < $b->getName()) ? -1 : 1;
    }

    /**
     * Show list of plugins window
     *
     * @param string $login The user to show the list to.
     */
    public function showPluginsWindow($login)
    {
        $state        = isset($this->pluginListPlayerState[$login]) ? $this->pluginListPlayerState[$login] : array();
        $groupIdx     = isset($state['groupIdx']) ? $state['groupIdx'] : 0;
        $filterName   = isset($state['name'])     ? $state['name']     : '';
        $filterAuthor = isset($state['author'])   ? $state['author']   : '';
        $this->pluginListBuildAndShow($login, $groupIdx, $filterName, $filterAuthor);
    }

    private function pluginListBuildAndShow($login, $selectedGroupIdx = 0, $filterName = '', $filterAuthor = '')
    {
        $this->pluginListPlayerState[$login] = array(
            'groupIdx' => $selectedGroupIdx,
            'name'     => $filterName,
            'author'   => $filterAuthor,
        );

        $pHandler  = PluginHandler::getInstance();
        $configMgr = ConfigManager::getInstance();

        $selectedGroupName = isset($this->pluginListGroups[$selectedGroupIdx])
            ? $this->pluginListGroups[$selectedGroupIdx]
            : (count($this->pluginListGroups) ? $this->pluginListGroups[0] : '');

        $pluginsData = array();
        $i = 0;
        foreach ($this->availablePlugins as $pluginId => $metaData) {
            if (in_array("Core", $metaData->getGroups())) {
                continue;
            }
            if (!empty($filterName) && strpos(strtoupper($metaData->getName()), strtoupper($filterName)) === false) {
                continue;
            }
            if (!empty($filterAuthor) && strpos(strtoupper($metaData->getAuthor()), strtoupper($filterAuthor)) === false) {
                continue;
            }
            if ($selectedGroupName != '' && !in_array($selectedGroupName, $metaData->getGroups())) {
                continue;
            }

            $metaData->checkAll();

            $isLoaded  = $pHandler->isLoaded($pluginId);
            $isInStart = $this->isInStartList($pluginId);

            if ($isLoaded) {
                $toggleColorize = '0f0';
                $toggleTooltip  = "Plugin is running. Click to unload!";
            } elseif ($isInStart) {
                $toggleColorize = 'ff0';
                $toggleTooltip  = "Plugin not compatible with game mode, title or server settings.\n Plugin will be enabled when possible.";
            } else {
                $toggleColorize = 'f00';
                $toggleTooltip  = "Plugin not running. Click to load!";
            }

            $titleCompat   = $metaData->checkTitleCompatibility();
            $gameCompat    = $metaData->checkGameCompatibility();
            $otherIssues   = $metaData->checkOtherCompatibility();

            $titleColorize = $titleCompat ? '090' : 'f00';
            $titleTooltip  = $titleCompat
                ? "This plugin is compatible with the current Title"
                : "This plugin isn't compatible with the current Title";

            $gameColorize  = $gameCompat ? '090' : 'f00';
            $gameTooltip   = $gameCompat
                ? "This plugin is compatible with the current Game mode"
                : "This plugin isn't compatible with the current Game mode";

            if (empty($otherIssues)) {
                $otherColorize = '090';
                $otherTooltip  = "This plugin is compatible with current installation";
                $otherSizeY    = 5;
            } else {
                $otherColorize = 'f00';
                $otherTooltip  = "This plugin has a few compatibility issues :\n" . implode("\n", $otherIssues);
                $otherSizeY    = 5 * (count($otherIssues) + 1);
            }

            if ($isInStart || $isLoaded) {
                $startText     = "Stop";
                $startColorize = "F00";
            } else {
                $startText     = "Start";
                $startColorize = "0D0";
            }

            $pluginsData[] = array(
                'id'             => $pluginId,
                'index'          => $i++,
                'name'           => ($metaData->getName() == "" ? $pluginId : $metaData->getName()),
                'desc'           => $metaData->getDescription(),
                'toggleColorize' => $toggleColorize,
                'toggleTooltip'  => $toggleTooltip,
                'titleColorize'  => $titleColorize,
                'titleTooltip'   => $titleTooltip,
                'gameColorize'   => $gameColorize,
                'gameTooltip'    => $gameTooltip,
                'otherColorize'  => $otherColorize,
                'otherTooltip'   => $otherTooltip,
                'otherSizeY'     => $otherSizeY,
                'configAvailable'   => !empty($configMgr->getGroupedVariables($pluginId)),
                'startText'      => $startText,
                'startColorize'  => $startColorize,
            );
        }

        $groupsData = array();
        foreach ($this->pluginListGroups as $idx => $groupName) {
            $groupsData[] = array(
                'name'     => $groupName,
                'selected' => ($idx == $selectedGroupIdx),
            );
        }

        $this->pluginListWindow->setParam("plugins",         $pluginsData);
        $this->pluginListWindow->setParam("groups",          $groupsData);
        $this->pluginListWindow->setParam("groupNames",      $this->pluginListGroups);
        $this->pluginListWindow->setParam("currentGroupIdx", $selectedGroupIdx);
        $this->pluginListWindow->setParam("currentName",     $filterName);
        $this->pluginListWindow->setParam("currentAuthor",   $filterAuthor);
        $this->pluginListWindow->show($login);
    }

    public function pluginListSetGroup($login, $groupIdx, $params = array())
    {
        $filterName   = isset($params['name'])   ? $params['name']   : '';
        $filterAuthor = isset($params['author']) ? $params['author'] : '';
        $this->pluginListBuildAndShow($login, $groupIdx, $filterName, $filterAuthor);
    }

    public function pluginListSearch($login, $params = array())
    {
        $filterName   = isset($params['name'])   ? $params['name']   : '';
        $filterAuthor = isset($params['author']) ? $params['author'] : '';
        $groupIdx     = isset($params['group'])  ? intval($params['group']) : 0;
        $this->pluginListBuildAndShow($login, $groupIdx, $filterName, $filterAuthor);
    }

    public function pluginListToggle($login, $pluginId)
    {
        if (isset($this->availablePlugins[$pluginId])) {
            $this->togglePlugin($login, $this->availablePlugins[$pluginId]);
        }
    }

    public function pluginListShowConfig($login, $pluginId)
    {
        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'showExpSettings', $login, null, $pluginId);
    }

    public function onPluginListWindowClosed($login)
    {
        unset($this->pluginListPlayerState[$login]);
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
        if ($this->pluginListWindow instanceof Window) {
            $this->pluginListWindow->erase();
        }
        $this->pluginListWindow      = null;
        $this->pluginListPlayerState    = array();
    }

    /**
     * Check if a plugin is in the list of plugins to be used on start.
     *
     * @param string $pluginId The plugin to check
     *
     * @return bool
     */
    public function isInStartList($pluginId)
    {
        return in_array($pluginId, $this->plugins) || in_array($pluginId, Config::getInstance()->plugins);
    }

    /**
     * Get all available metadata.
     *
     * @return MetaDataType[]
     */
    public static function getAvailablePlugins()
    {
        return self::$allAvailablePlugins;
    }
}
