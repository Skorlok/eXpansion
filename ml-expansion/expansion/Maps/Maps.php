<?php

namespace ManiaLivePlugins\eXpansion\Maps;

use Exception;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminCmd;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\Bill;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Donate\Config as Donate;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\AddMaps;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\Jukelist;
use ManiaLivePlugins\eXpansion\Maps\Structures\MapSortMode;
use ManiaLivePlugins\eXpansion\Maps\Structures\MapWish;
use ManiaLivePlugins\eXpansion\Maps\Structures\MapInfos;
use ManiaLivePlugins\eXpansion\Menu\Menu;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\Map;

class Maps extends ExpPlugin
{
    /** @var  Config */
    private $config;

    /** @var DonateConfig */
    private $donateConfig;

    /** @var MapWish[] */
    private $queue = array();

    /** @var Map[] */
    private $history = array();
    private $nextMap;
    private $tries = 0;
    private $paymentInProgress = false;
    private $messages;

    /** @var MapWish */
    private $msg_addQueue;
    private $msg_nextQueue;
    private $msg_nextMap;
    private $msg_queueNow;
    private $msg_jukehelp;
    private $msg_errDwld;
    private $msg_errMxId;
    private $msg_mapAdd;
    private $msg_errToLarge;
    private $msg_skipleft;

    private $removeAllDirectAction;
    private $removeAllAction;
    
    private $mapInfoWindow;
    private $mapListWindow;
    private $filterWindow;

    private $mapListWindowOpened = array();

    public static $actionOpenMapList = -1;
    public static $dbMapsByUid = array();

    /**
     * @var AdminCmd
     */
    private $cmd_remove;
    private $cmd_erease;
    private $cmd_replay;
    private $cmd_prev;
    private $cmd_cjb;

    private $is_onEndMatch = false;

    public function eXpOnInit()
    {
        $this->messages = new \StdClass();

        /** @var Config $config */
        $this->config = Config::getInstance();
        $this->donateConfig = Donate::getInstance();

        $this->setPublicMethod("queueMap");
        $this->setPublicMethod("queueMxMap");
        $this->setPublicMethod("replayMap");
        $this->setPublicMethod("replayMapInstant");
        $this->setPublicMethod("replayScoreReset");
        $this->setPublicMethod("returnQueue");
        $this->setPublicMethod("showMapList");
        $this->setPublicMethod("showJukeList");
        $this->setPublicMethod("showMapInfo");
        if ($this->expStorage->isRemoteControlled == false) {
            $this->setPublicMethod("addMaps");
        }
    }

    public function eXpOnLoad()
    {
        $this->msg_addQueue = eXpGetMessage('#variable#%1$s  #queue#has been added to the map queue by #variable#%3$s#queue#, in the #variable#%5$s #queue#position'); // '%1$s' = Map Name, '%2$s' = Map author %, '%3$s' = nickname, '%4$s' = login, '%5$s' = # in queue
        $this->msg_nextQueue = eXpGetMessage('#queue#Next map will be #variable#%1$s  #queue#by #variable#%2$s#queue#, as requested by #variable#%3$s'); // '%1$s' = Map Name, '%2$s' = Map author %, '%3$s' = nickname, '%4$s' = login
        $this->msg_nextMap = eXpGetMessage('#queue#Next map will be #variable#%1$s  #queue#by #variable#%2$s#queue#'); // '%1$s' = Map Name, '%2$s' = Map author
        $this->msg_queueNow = eXpGetMessage('#queue#Map changed to #variable#%1$s  #queue#by #variable#%2$s#queue#, as requested by #variable#%3$s'); // '%1$s' = Map Name, '%2$s' = Map author %, '%3$s' = nickname, '%4$s' = login
        $this->msg_jukehelp = eXpGetMessage('#queue#/jb uses next params: drop and show');
        $this->msg_errDwld = eXpGetMessage('#admin_error#Error downloading, or MX is down!');
        $this->msg_errToLarge = eXpGetMessage('#admin_error#The map is to large to be added to a server');
        $this->msg_errMxId = eXpGetMessage("#admin_error#You must include a MX map ID!");
        $this->msg_mapAdd = eXpGetMessage('#admin_action#Map #variable# %1$s #admin_action#added to playlist by #variable#%2$s');
        $this->msg_skipleft = eXpGetMessage('#queue#Skipping map #variable#%1$s #queue#, because #variable#%2$s #queue#left'); // '%1$s' = Map Name, '%2$s' = requester nickname
        $this->enableDedicatedEvents();

        Menu::addMenuItem("Maps",
            array("Maps" => array(null, array(
                "Show Maps" => array(null, "exp:eXpansion.Maps:showMapList"),
                "Show Jukebox" => array(null, "exp:eXpansion.Maps:showJukeList"),
                "Add Local Maps" => array(Permission::MAP_ADD_LOCAL, "exp:eXpansion.Maps:addMaps"),
                '$f00Remove this' => array(Permission::MAP_REMOVE_MAP, "exp:eXpansion.Maps:chat_removeMap"),
                '$f00Trash this' => array(Permission::MAP_REMOVE_MAP, "exp:eXpansion.Maps:chat_eraseMap")
            )))
        );
    }

    public function eXpOnReady()
    {
        $this->registerManialinkCallback('addMaps');
        $this->registerManialinkCallback('chat_removeMap');
        $this->registerManialinkCallback('chat_eraseMap');
        $this->registerManialinkCallback('playerQueueMap', false, true);
        $this->registerManialinkCallback('removeMap', false, true);
        $this->registerManialinkCallback('eraseMap', false, true);
        $this->registerManialinkCallback('gotoMap', false, true);
        $this->registerManialinkCallback('showRec', false, true);
        $this->registerManialinkCallback('showMapInfo', false, true);
        $this->registerManialinkCallback('showMapList');
        $this->registerManialinkCallback('showJukeList');
        $this->registerManialinkCallback('doSearchMap', true);
        $this->registerManialinkCallback('doSearchAuthor', true);
        $this->registerManialinkCallback('openFilterWindow');
        $this->registerManialinkCallback('clearPlayerFilter');
        $this->registerManialinkCallback('doSort', false, true);
        $this->registerManialinkCallback('applyFilter', true, true);
        $this->registerManialinkCallback('applySortFilter', true, true);
        $this->registerManialinkCallback('removeAllMaps');
        
        $cmd = AdminGroups::addAdminCommand('removethis', $this, 'chat_removeMap', Permission::MAP_REMOVE_MAP);
        $cmd->setHelp(eXpGetMessage('Removes current map from the playlist.'));
        $cmd->setMinParam(0);
        $this->cmd_remove = $cmd;

        $cmd = AdminGroups::addAdminCommand('erasethis', $this, 'chat_eraseMap', Permission::MAP_REMOVE_MAP);
        $cmd->setHelp(eXpGetMessage('Erases current map from the playlist.'));
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "trashthis");
        $this->cmd_erease = $cmd;

        $cmd = AdminGroups::addAdminCommand('replaymap', $this, 'replayMap', Permission::MAP_RES);
        $cmd->setHelp(eXpGetMessage('Sets current challenge to replay at end of match'));
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "replay");
        $this->cmd_replay = $cmd;

        $cmd = AdminGroups::addAdminCommand('previous', $this, 'previousMap', Permission::MAP_RES);
        $cmd->setHelp(eXpGetMessage('Adds previous map back to the Jukebox.'));
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "prev");
        $this->cmd_prev = $cmd;

        $cmd = AdminGroups::addAdminCommand('clearjukebox', $this, 'emptyWishes', Permission::MAP_JUKEBOX_ADMIN);
        $cmd->setHelp(eXpGetMessage('Clear the Jukebox.'));
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "cjb");
        $this->cmd_cjb = $cmd;

        $this->registerChatCommand('list', "showMapList", -1, true);
        $this->registerChatCommand('maps', "showMapList", -1, true);

        $this->registerChatCommand('mapinfo', "showMapInfo", 0, true);

        $this->registerChatCommand('nextmap', "chat_nextMap", 0, true);

        $this->registerChatCommand('jb', "jukebox", 0, true);
        $this->registerChatCommand('jb', "jukebox", 1, true);

        $this->registerChatCommand('jukebox', "jukebox", 0, true);
        $this->registerChatCommand('jukebox', "jukebox", 1, true);

        $this->registerChatCommand('history', "showHistoryList", 0, true);

        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords')) {
            $this->registerChatCommand('best', "showBestMapList", 0, true);
            $this->registerChatCommand('worst', "showWorstMapList", 0, true);
        }

        $this->enablePluginEvents();


        $this->nextMap = $this->storage->nextMap;

        Jukelist::$mainPlugin = $this;
        AddMaps::$mapsPlugin = $this;

        $this->mapInfoWindow = new Window("Maps\Gui\Windows\MapInfo.xml");
        $this->mapInfoWindow->setSize(160, 90);

        $this->showCurrentMapWidget();
        $this->showNextMapWidget();

        $this->preloadHistory();

        $this->filterWindow = new Window('Maps\Gui\Windows\MaplistFilter.xml');
        $this->filterWindow->setName('MaplistFilter');
        $this->filterWindow->setTitle("Maplist Filters Selection");
        $this->filterWindow->setSize(120, 112);

        $this->mapListWindow = new Window('Maps\Gui\Windows\Maplist.xml');
        $this->mapListWindow->setName('Maplist');
        $this->mapListWindow->setSize(214, 100);
        $this->mapInfoWindow->registerCloseCallback(array($this, 'onMapListWindowClosed'));

        $this->removeAllAction = Gui::createConfirm("exp:eXpansion.Maps:removeAllMaps");
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        /** @var Config $config */
        $this->config = Config::getInstance();

        if ($this->config->showCurrentMapWidget) {
            $this->showCurrentMapWidget();
        } else {
            $widget = new Widget("Maps\Gui\Widgets\CurrentMapWidget.xml");
            $widget->setName("Current Map Widget");
            $widget->setLayer("scorestable");
            $widget->erase();
        }

        if ($this->config->showNextMapWidget) {
            $this->showNextMapWidget();
        } else {
            $widget = new Widget("Maps\Gui\Widgets\NextMapWidget.xml");
            $widget->setName("Next Map");
            $widget->setLayer("scorestable");
            $widget->erase();
        }
    }

    /**
     *
     * @return boolean
     */
    public function isLocalRecordsLoaded()
    {
        return $this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords');
    }

    /**
     * showRec($login, $map)
     *
     * @param string $login
     * @param string $uid
     */
    public function showRec($login, $uid)
    {
        $map = $this->findMapByUId($uid);
        if (!$map) {
            $this->eXpChatSendServerMessage('Map not found', $login);
            return;
        }

        $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "showRecsWindow", $login, $map);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (empty($login)) {
            return;
        }

        if (isset(MapsFilterHelper::$playerSortModes[$login])) {
            unset(MapsFilterHelper::$playerSortModes[$login]);
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->is_onEndMatch = false;

        $this->nextMap = $this->storage->nextMap;

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            if ($queue->map->uId == $this->storage->currentMap->uId) {
                if ($queue->isTemp) {
                    try {
                        $this->connection->removeMap($queue->map->fileName);
                    } catch (Exception $e) {
                        $ac = AdminGroups::getInstance();
                        $ac->announceToPermission(Permission::SERVER_ADMIN, "Error: %s", array($e->getMessage()));
                        $this->console("Error while removing temporarily added map!");
                        $this->console($e->getMessage());
                    }
                }
                $this->tries = 0;
                array_shift($this->queue);
            } else {
                if ($this->tries < 3) {
                    $this->tries++;
                } else {
                    $this->tries = 0;
                    array_shift($this->queue);
                }
            }
            $this->nextMap = $queue->map;
        }

        if (!isset($this->history[0]) || $this->history[0]->uId != $this->storage->currentMap->uId) {
            array_unshift($this->history, $this->storage->currentMap);
            if (count($this->history) > Config::getInstance()->historySize) {
                array_pop($this->history);
            }
        }

        $this->showCurrentMapWidget();
        $this->showNextMapWidget();
    }

    public function onBeginMatch()
    {
        $this->is_onEndMatch = false;
        $this->showCurrentMapWidget();
        $this->showNextMapWidget();
    }

    public function showCurrentMapWidget()
    {
        if ($this->config->showCurrentMapWidget) {
            /*$playerModel = "";
            if (isset($this->storage->currentMap->playerModel)) {
                $playerModel = '/' . $this->storage->currentMap->playerModel;
            }*/
            $environment = $this->storage->currentMap->environnement /*. $playerModel*/;
            $country = "http://reaby.kapsi.fi/ml/flags/Other%20Countries.dds";
            if ($this->storage->currentMap->author == "Nadeo") {
                $country = "http://reaby.kapsi.fi/ml/flags/France.dds";
            }


            $widget = new Widget("Maps\Gui\Widgets\CurrentMapWidget.xml");
            $widget->setName("Current Map Widget");
            $widget->setLayer("scorestable");
            $widget->setPosition($this->config->currentMapWidget_PosX, $this->config->currentMapWidget_PosY, 0);
            $widget->setSize(90, 15);
            $widget->registerScript(new Script("Maps\Gui\Scripts_CurrentMap"));
            $widget->setParam("country", $country);
            $widget->setParam("environment", $environment);
            $widget->show(null, true);
        }
    }

    public function showNextMapWidget()
    {
        if ($this->config->showNextMapWidget) {
            if (count($this->queue) > 0) {
                reset($this->queue);
                $queue = current($this->queue);
                
                if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $queue->map->fileName)) {
                    try {
                        $gbxInfo = new GBXChallMapFetcher(true, false, false);
                        $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $queue->map->fileName);
                    } catch (Exception $e) {
						$gbxInfo = new MapInfos();
                        $gbxInfo->name = $queue->map->name;
                        $gbxInfo->authorNick = $queue->map->author;
                        $gbxInfo->envir = $queue->map->environnement;
                        $gbxInfo->author = $queue->map->author;
					}
                } else {
                    $gbxInfo = new MapInfos();
                    $gbxInfo->name = $queue->map->name;
                    $gbxInfo->authorNick = $queue->map->author;
                    $gbxInfo->envir = $queue->map->environnement;
                    $gbxInfo->author = $queue->map->author;
                }

            } else {

                if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $this->storage->nextMap->fileName)) {
                    try {
                        $gbxInfo = new GBXChallMapFetcher(true, false, false);
                        $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $this->storage->nextMap->fileName);
                    } catch (Exception $e) {
						$gbxInfo = new MapInfos();
                        $gbxInfo->name = $this->storage->nextMap->name;
                        $gbxInfo->authorNick = $this->storage->nextMap->author;
                        $gbxInfo->envir = $this->storage->nextMap->environnement;
                        $gbxInfo->author = $this->storage->nextMap->author;
					}
                } else {
                    $gbxInfo = new MapInfos();
                    $gbxInfo->name = $this->storage->nextMap->name;
                    $gbxInfo->authorNick = $this->storage->nextMap->author;
                    $gbxInfo->envir = $this->storage->nextMap->environnement;
                    $gbxInfo->author = $this->storage->nextMap->author;
                }

            }

            $country = "http://reaby.kapsi.fi/ml/flags/Other%20Countries.dds";
            if ($gbxInfo->author == "Nadeo") {
                $country = "http://reaby.kapsi.fi/ml/flags/France.dds";
            }

            $widget = new Widget("Maps\Gui\Widgets\NextMapWidget.xml");
            $widget->setName("Next Map");
            $widget->setLayer("scorestable");
            $widget->setPosition($this->config->nextMapWidget_PosX, $this->config->nextMapWidget_PosY, 0);
            $widget->setSize(60, 15);
            $widget->setParam("nickname", $widget->handleSpecialChars($gbxInfo->authorNick));
            $widget->setParam("mapname", $widget->handleSpecialChars($gbxInfo->name));
            $widget->setParam("country", $country);
            $widget->setParam("environment", $gbxInfo->envir);
            $widget->show(null, true);
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap, $enduroSkipMap = false)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false && $enduroSkipMap != true) {
            return;
        }

        if ($this->is_onEndMatch) {
            return;
        }
        $this->is_onEndMatch = true;

        /** @var Config $config */
        $this->config = Config::getInstance();

        $widget = new Widget("Maps\Gui\Widgets\CurrentMapWidget.xml");
        $widget->setName("Current Map Widget");
        $widget->setLayer("scorestable");
        $widget->erase();

        $widget = new Widget("Maps\Gui\Widgets\NextMapWidget.xml");
        $widget->setName("Next Map");
        $widget->setLayer("scorestable");
        $widget->erase();

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);

            if ($this->config->skipLeft) {

                while (!isset($this->storage->players[$queue->player->login]) && !isset($this->storage->spectators[$queue->player->login])) {

                    if ($this->config->skipRight || !AdminGroups::hasPermission($queue->player->login, Permission::MAP_JUKEBOX_ADMIN)) {
                        $this->eXpChatSendServerMessage($this->msg_skipleft, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), Formatting::stripCodes($queue->player->cleanNickName, 'wosnm')));
                        array_shift($this->queue);
                    } else {
                        break;
                    }
                    
                    if (count($this->queue) > 0) {
                        reset($this->queue);
                        $queue = current($this->queue);
                    } else {
                        break;
                    }
                }
            }
        }

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);

            try {
                $this->connection->chooseNextMap($queue->map->fileName);

                if ($this->config->showEndMatchNotices || $this->config->showEndMatchNoticesJukebox) {

                    if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $queue->map->fileName)) {
                        try {
                            $gbxInfo = new GBXChallMapFetcher(true, false, false);
                            $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $queue->map->fileName);
                            $this->eXpChatSendServerMessage($this->msg_nextQueue, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $gbxInfo->authorNick, Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), $queue->player->login, $queue->map->environnement));
                        } catch (Exception $e) {
                            $this->eXpChatSendServerMessage($this->msg_nextQueue, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), $queue->player->login, $queue->map->environnement));
                        }
                    } else {
                        $this->eXpChatSendServerMessage($this->msg_nextQueue, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), $queue->player->login, $queue->map->environnement));
                    }
                }
            } catch (Exception $e) {
                $this->eXpChatSendServerMessage('Error: %s', $queue->player->login, array($e->getMessage()));
                $key = key($this->queue);
                unset($this->queue[$key]);
                $this->eXpChatSendServerMessage('Recovering from error, map removed from jukebox...', $queue->player->login);
            }
        } else {
            if ($this->config->showEndMatchNotices) {
                $map = $this->storage->nextMap;

                if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $this->storage->nextMap->fileName)) {
                    try {
                        $gbxInfo = new GBXChallMapFetcher(true, false, false);
                        $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $this->storage->nextMap->fileName);
                        $this->eXpChatSendServerMessage($this->msg_nextMap, null, array(Formatting::stripCodes($map->name, 'wosnm'), $gbxInfo->authorNick));
                    } catch (Exception $e) {
                        $this->eXpChatSendServerMessage($this->msg_nextMap, null, array(Formatting::stripCodes($map->name, 'wosnm'), $this->storage->nextMap->author));
                    }
                } else {
                    $this->eXpChatSendServerMessage($this->msg_nextMap, null, array(Formatting::stripCodes($map->name, 'wosnm'), $this->storage->nextMap->author));
                }
            }
        }
    }

    /**
     * Handler for jukebox chat
     *
     * @param        $login
     * @param string $args
     */
    public function jukebox($login, $args = "")
    {
        try {
            switch (strtolower($args)) {
                case "drop":
                    $this->chat_dropQueue($login);
                    break;
                case "list":
                case "show":
                    $this->showJukeList($login);
                    break;
                default:
                    $this->eXpChatSendServerMessage($this->msg_jukehelp, $login);
                    break;
            }
        } catch (Exception $e) {
            $this->console($e->getFile() . ":" . $e->getLine());
        }
    }

    public function showJukeList($login)
    {
        $window = Jukelist::Create($login);
        $window->setList($this->queue);
        $window->centerOnScreen();
        $window->setTitle(__("Jukebox", $login));
        $window->setSize(180, 100);
        $window->show();
    }

    public function onPluginLoaded($pluginId)
    {
        if ($pluginId == '\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords') {
            $this->registerChatCommand('best', "showBestMapList", 0, true);
            $this->registerChatCommand('worst', "showWorstMapList", 0, true);
        }
    }
    
    public function onPluginUnloaded($pluginId)
    {
        if ($pluginId == '\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords') {
            $this->unregisterChatCommand('best');
            $this->unregisterChatCommand('worst');
        }
    }

    public function doSort($login, $column)
    {
        if ($column != MapsFilterHelper::$playerSortModes[$login]->column) {
            MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
            MapsFilterHelper::$playerSortModes[$login]->column   = $column;
        } else {
            MapsFilterHelper::$playerSortModes[$login]->sortMode = (MapsFilterHelper::$playerSortModes[$login]->sortMode + 1) % 3;
        }
        $this->showMapListWindow($login);
    }

    public function doSearchMap($login, $entries)
    {
        MapsFilterHelper::$playerSortModes[$login]->searchTerm = isset($entries['searchbox']) ? $entries['searchbox'] : '';
        MapsFilterHelper::$playerSortModes[$login]->searchField = "name";
        $this->showMapListWindow($login);
    }

    public function doSearchAuthor($login, $entries)
    {
        MapsFilterHelper::$playerSortModes[$login]->searchTerm = isset($entries['searchbox']) ? $entries['searchbox'] : '';
        MapsFilterHelper::$playerSortModes[$login]->searchField = "author";
        $this->showMapListWindow($login);
    }

    public function openFilterWindow($login)
    {
        $sortMode = isset(MapsFilterHelper::$playerSortModes[$login]) ? MapsFilterHelper::$playerSortModes[$login]->sortMode : 1;
        $this->filterWindow->setParam("isAsc", $sortMode == 1);
        $this->filterWindow->setParam("isDesc", $sortMode == 2);
        $this->filterWindow->setParam("targetLogin", isset(MapsFilterHelper::$playerSortModes[$login]) ? MapsFilterHelper::$playerSortModes[$login]->filterParam : null);
        $this->filterWindow->setParam("hideLocalRecords", !$this->isPluginLoaded('\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords'));
        $this->filterWindow->setParam("hideMapRatings", !$this->isPluginLoaded('\ManiaLivePlugins\eXpansion\MapRatings\MapRatings'));
        $this->filterWindow->show($login);
    }

    public function applyFilter($login, $filter, $entries = array())
    {
        // it should not be possible to use these filters if the LocalRecords plugin is not loaded, but security checks are always good to have.
        if ($filter === 'behindlogin' || $filter === 'nofinish' || $filter === 'finished' || $filter === 'noauthor' || $filter === 'nogold' || $filter === 'nosilver' || $filter === 'nobronze') {
            if (!$this->isPluginLoaded('\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords')) {
                $this->eXpChatSendServerMessage("#error#You cannot use this filter because the LocalRecords plugin is not loaded!", $login);
                return;
            }
        }
        if ($filter === 'novote' || $filter === 'voted') {
            if (!$this->isPluginLoaded('\ManiaLivePlugins\eXpansion\MapRatings\MapRatings')) {
                $this->eXpChatSendServerMessage("#error#You cannot use this filter because the MapRatings plugin is not loaded!", $login);
                return;
            }
        }

        if ($filter === 'behindlogin') {
            $targetLogin = isset($entries['behindLogin']) ? trim($entries['behindLogin']) : '';
            if (empty($targetLogin)) {
                $this->eXpChatSendServerMessage("#error#You must enter a login to filter behind!", $login);
                return;
            } else if ($targetLogin === $login) {
                $this->eXpChatSendServerMessage("#error#You cannot filter behind yourself!", $login);
                return;
            }
            $dbPlayer = $this->db->execute("SELECT `player_login` FROM `exp_players` WHERE `player_login` = ". $this->db->quote($targetLogin))->fetchObject();
            if (!$dbPlayer) {
                $this->eXpChatSendServerMessage("#error#The login you entered does not exist in the database!", $login);
                return;
            }
            $dbPlayerRecords = $this->db->execute("SELECT count(`record_challengeuid`) AS `record_count` FROM `exp_records` WHERE `record_playerlogin` = ". $this->db->quote($targetLogin))->fetchObject();
            if (!isset($dbPlayerRecords->record_count) || $dbPlayerRecords->record_count <= 0) {
                $this->eXpChatSendServerMessage("#error#The login you entered has no records in the database!", $login);
                return;
            }
            MapsFilterHelper::$playerSortModes[$login]->filterParam = $targetLogin;
        }
        MapsFilterHelper::$playerSortModes[$login]->searchFilter = $filter;
        $this->filterWindow->erase($login);
        $this->showMapListWindow($login);
    }

    public function applySortFilter($login, $column, $entries = array())
    {
        $direction = isset($entries['sortDir_desc']) && $entries['sortDir_desc'] == '1' ? 2 : 1;
        MapsFilterHelper::$playerSortModes[$login]->column   = $column;
        MapsFilterHelper::$playerSortModes[$login]->sortMode = $direction;
        $this->filterWindow->erase($login);
        $this->showMapListWindow($login);
    }

    public function clearPlayerFilter($login)
    {
        MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
        MapsFilterHelper::$playerSortModes[$login]->searchField = '';
        MapsFilterHelper::$playerSortModes[$login]->column = '';
        MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
        MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
        MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
        $this->showMapListWindow($login);
    }

    public function removeAllMaps($login)
    {
        $chunkSize  = 1000;
        $offset     = 0;
        $removed    = 0;

        try {
            $currentMap = $this->connection->getCurrentMapInfo();
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage("Oops, couldn't get current map: " . $e->getMessage());
            return;
        }

        for ($i = 0; $i < 500; $i++) {
            try {
                $chunk = $this->connection->getMapList($chunkSize, $offset);
            } catch (\Exception $e) {
                break;
            }
            if (empty($chunk)) {
                break;
            }

            $toRemove = array();
            foreach ($chunk as $map) {
                if ($map->fileName !== $currentMap->fileName) {
                    $toRemove[] = $map->fileName;
                }
            }

            if (!empty($toRemove)) {
                try {
                    $this->connection->removeMapList($toRemove);
                    $removed += count($toRemove);
                } catch (\Exception $e) {
                    $this->connection->chatSendServerMessage("Error while removing maps: " . $e->getMessage());
                    return;
                }
            }

            if (count($chunk) < $chunkSize) {
                break;
            }

            // The list shrinks as we remove maps; only advance offset for maps we kept (the current map)
            $kept    = count($chunk) - count($toRemove);
            $offset += $kept;
        }

        $this->connection->chatSendServerMessage("Maplist cleared: " . $removed . " maps removed.", $login);
    }

    private function findMapByUId($uid)
    {
        foreach ($this->storage->maps as $map) {
            if ($map->uId === $uid) {
                return $map;
            }
        }
        return null;
    }

    public function showMapListWindow($login)
    {
        $localrecordsLoaded = false;
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords')) {
            $this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords', 'getPlayersRecordsForAllMaps', $login);
            $localrecordsLoaded = true;
        }

        $mapratingLoaded = false;
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\MapRatings\MapRatings')) {
            $this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings', 'getPlayersRatingsForAllMaps', $login);
            $mapratingLoaded = true;
        }

        if (!isset($this->mapListWindowOpened[$login])) {
            $this->mapListWindowOpened[$login] = true;
        }

        $filteredMaps = MapsFilterHelper::filterMaps($login, $this->history);

        $items = array();
        $data  = array();
        $x     = 0;

        foreach ($filteredMaps as $sortableMap) {
            $uid = $sortableMap->uId;
            $queueMapAction  = 'exp:eXpansion.Maps:playerQueueMap:' . $uid;
            $removeMapAction = 'exp:eXpansion.Maps:removeMap:' . $uid;
            $trashMapAction  = 'exp:eXpansion.Maps:eraseMap:' . $uid;
            $jumpMapAction   = 'exp:eXpansion.Maps:gotoMap:' . $uid;
            $showRecsAction  = 'exp:eXpansion.Maps:showRec:' . $uid;
            $showInfoAction  = 'exp:eXpansion.Maps:showMapInfo:' . $uid;

            if (isset($sortableMap->mapRating)) {
                $rate = ($sortableMap->mapRating->rating / 5) * 100;
                $rate = round($rate) . "%" . '  $n' . "(" . $sortableMap->mapRating->totalvotes . ")";
                if ($sortableMap->mapRating->rating == -1) {
                    $rate = " - ";
                }
            } else {
                $rate = " - ";
            }

            $localrecord = "-";
            if (isset($sortableMap->localRecords) && isset($sortableMap->localRecords[$login])) {
                $localrecord = $sortableMap->localRecords[$login][0] + 1;
            }

            $playerVote = "-";
            if (isset($sortableMap->mapRating->playerVotes[$login])) {
                $playerVote = $sortableMap->mapRating->playerVotes[$login] . " / 5";
            }

            $items[$x] = array(Gui::fixString($sortableMap->author), Gui::fixString($sortableMap->name), $sortableMap->environnement, Time::fromTM($sortableMap->goldTime), $localrecord, $rate, $playerVote, "Info", "Recs", "Jump", "x", "Trash");
            $data[$x] = array(-1, $queueMapAction, -1, -1, -1, -1, -1, $showInfoAction, $showRecsAction, $jumpMapAction, $removeMapAction, $trashMapAction);

            $x++;
        }

        /** @var \ManiaLivePlugins\eXpansion\Gui\Config $config */
        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $sortMode   = MapsFilterHelper::$playerSortModes[$login];
        $hasSorted  = $sortMode->column && $sortMode->sortMode;
        $hasFilter  = $sortMode->searchFilter && isset(MapsFilterHelper::$availableFilters[$sortMode->searchFilter]);
        $mapCount   = count($filteredMaps);

        if ($hasSorted && $hasFilter) {
            $sortColumn  = __(MapsFilterHelper::$availableSortModes[$sortMode->column], $login);
            $sortText    = ($sortMode->sortMode == 1 ? __('ascending', $login) : __('descending', $login));
            $filterLabel = __(MapsFilterHelper::$availableFilters[$sortMode->searchFilter], $login);
            $this->mapListWindow->setTitle('Maps on server (%1$s) - Sorted by \'%2$s\' %3$s - Filtered by \'%4$s\'', array($mapCount, $sortColumn, $sortText, $filterLabel));
        } elseif ($hasSorted) {
            $sortColumn = __(MapsFilterHelper::$availableSortModes[$sortMode->column], $login);
            $sortText   = ($sortMode->sortMode == 1 ? __('ascending', $login) : __('descending', $login));
            $this->mapListWindow->setTitle('Maps on server (%1$s) - Sorted by \'%2$s\' %3$s', array($mapCount, $sortColumn, $sortText));
        } elseif ($hasFilter) {
            $filterLabel = __(MapsFilterHelper::$availableFilters[$sortMode->searchFilter], $login);
            $this->mapListWindow->setTitle('Maps on server (%1$s) - Filtered by \'%2$s\'', array($mapCount, $filterLabel));
        } else {
            $this->mapListWindow->setTitle('Maps on server (%s)', array($mapCount));
        }

        $this->mapListWindow->setParam("searchTerm",  $this->mapListWindow->handleSpecialChars(MapsFilterHelper::$playerSortModes[$login]->searchTerm));
        $this->mapListWindow->setParam("bgColorize",  $config->style_widget_title_bgColorize);
        $this->mapListWindow->setParam("hideRecs",    !$localrecordsLoaded);
        $this->mapListWindow->setParam("hideRating",  !$mapratingLoaded);
        $this->mapListWindow->setParam("hideJump",    !AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN));
        $this->mapListWindow->setParam("hideRemove",  !AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP));

        $hasRemove = AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP);
        $this->mapListWindow->setParam("removeAllAction", $hasRemove ? $this->removeAllAction : null);

        $this->mapListWindow->setParam("maplistItems", $items);
        $this->mapListWindow->setParam("maplistData",  $data);

        $this->mapListWindow->show($login);
    }

    public function onMapListWindowClosed($login)
    {
        if (isset($this->mapListWindowOpened[$login])) {
            unset($this->mapListWindowOpened[$login]);
        }
    }

    public function showBestMapList($login)
    {
        if (!isset(MapsFilterHelper::$playerSortModes[$login])) {
            MapsFilterHelper::$playerSortModes[$login] = new MapSortMode();
        }
        MapsFilterHelper::$playerSortModes[$login]->searchFilter = "finished";
        MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
        MapsFilterHelper::$playerSortModes[$login]->column = "localrecord";
        MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
        MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
        MapsFilterHelper::$playerSortModes[$login]->searchField = '';

        $this->showMapListWindow($login);
    }

    public function showWorstMapList($login)
    {
        if (!isset(MapsFilterHelper::$playerSortModes[$login])) {
            MapsFilterHelper::$playerSortModes[$login] = new MapSortMode();
        }
        MapsFilterHelper::$playerSortModes[$login]->searchFilter = "finished";
        MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
        MapsFilterHelper::$playerSortModes[$login]->column = "localrecord";
        MapsFilterHelper::$playerSortModes[$login]->sortMode = 2;
        MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
        MapsFilterHelper::$playerSortModes[$login]->searchField = '';

        $this->showMapListWindow($login);
    }

    public function showMapList($login, $params = null)
    {
        if ($params) {
            if (!isset(MapsFilterHelper::$playerSortModes[$login])) {
                MapsFilterHelper::$playerSortModes[$login] = new MapSortMode();
            }

            // commands from UASECO: /list
            // nofinish (filter: nofinish)
            // norank (filter: nofinish)
            // nogold (filter: nogold)
            // noauthor (filter: noauthor)
            // norecent (filter: norecent)
            // best (sort: localrecord asc + filter: finished)
            // worst (sort: localrecord desc + filter: finished)
            // longest (sort: goldTime desc)
            // shortest (sort: goldTime asc)
            // newest (sort: addTime desc)
            // oldest (sort: addTime asc)
            // novote (filter: novote)
            // karma (won't be implemented)

            // commands from UASECO: /elist
            // jukebox (show only jukeboxed maps) (won't be implemented)
            // author (show author list) (won't be implemented)
            // norecent (filter: norecent)
            // onlyrecent (filter: recent)
            // norank (filter: nofinish which is equivalent to norank)
            // onlyrank (filter: finished)
            // nomulti (filter: nomultilap)
            // onlymulti (filter: multilap)
            // noauthor (filter: noauthor)
            // nogold (filter: nogold)
            // nosilver (filter: nosilver)
            // nobronze (filter: nobronze)
            // nofinish (filter: nofinish)
            // best (sort: localrecord asc + filter: finished)
            // worst (sort: localrecord desc + filter: finished)
            // shortest (sort: goldTime asc)
            // longest (sort: goldTime desc)
            // newest (sort: addTime desc)
            // oldest (sort: addTime asc)
            // map (sort: name asc)
            // bestkarma (sort: rating asc)
            // worstkarma (sort: rating desc)

            if ($params === "nofinish" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "nofinish";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "norank" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "nofinish";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "nogold" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "nogold";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "noauthor" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "noauthor";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "norecent") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "norecent";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "best" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "finished";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "localrecord";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "worst" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "finished";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "localrecord";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 2;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "longest") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "goldTime";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 2;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "shortest") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "goldTime";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "newest") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "addTime";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 2;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "oldest") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "addTime";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "novote" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "novote";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "onlyrecent") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "recent";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "onlyrank" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "finished";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "nomulti") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "nomultilap";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "onlymulti") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "multilap";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "nosilver" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "nosilver";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "nobronze" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = "nobronze";
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "map") {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "name";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "bestkarma" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "rating";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 1;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else if ($params === "worstkarma" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings')) {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = "rating";
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 2;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            } else {
                MapsFilterHelper::$playerSortModes[$login]->searchFilter = '';
                MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
                MapsFilterHelper::$playerSortModes[$login]->column = '';
                MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
                MapsFilterHelper::$playerSortModes[$login]->searchTerm = $params;
                MapsFilterHelper::$playerSortModes[$login]->searchField = '';
            }
        }

        $this->showMapListWindow($login);
    }

    public function showHistoryList($login)
    {
        if (!isset(MapsFilterHelper::$playerSortModes[$login])) {
            MapsFilterHelper::$playerSortModes[$login] = new MapSortMode();
        }
        MapsFilterHelper::$playerSortModes[$login]->searchFilter = "recent";
        MapsFilterHelper::$playerSortModes[$login]->filterParam = '';
        MapsFilterHelper::$playerSortModes[$login]->column = '';
        MapsFilterHelper::$playerSortModes[$login]->sortMode = 0;
        MapsFilterHelper::$playerSortModes[$login]->searchTerm = '';
        MapsFilterHelper::$playerSortModes[$login]->searchField = '';

        $this->showMapListWindow($login);
    }

    /**
     * Returns the amount of planets that needs to be payed to wish a map
     *
     * @return integer 0 : if for free
     *        -1 : if queu is full
     *        X ; the ammount to pay
     */
    public function getQueuAmount()
    {
        if (!empty($this->config->publicQueueAmount) && $this->config->publicQueueAmount != -1) {
            if (isset($this->config->publicQueueAmount[sizeof($this->queue)])) {
                $amount = $this->config->publicQueueAmount[sizeof($this->queue)];
                return $amount != -1 ? $amount : 0;
            }
            return -1; //Impossible
        }
        return 0;
    }

    /**
     * Makes a player queu a map
     *
     * @param string $login  Player that wishes to queu the map
     * @param string $uid    The map to queue
     * @param bool $isTemp   Will te map be deleted after being playerd
     */
    public function playerQueueMap($login, $uid, $isTemp = false)
    {
        $map = $this->findMapByUId($uid);
        if (!$map) {
            $this->eXpChatSendServerMessage('Map not found', $login);
            return;
        }
        
        $amount = $this->getQueuAmount();

        if ($amount == 0 || AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_FREE)) {
            $this->queueMap($login, $map, $isTemp);
        } else {
            if ($amount > -1) {
                if ($this->checkQueuMap($login, $map, true)) {

                    if ($this->paymentInProgress) {
                        $msg = eXpGetMessage('#admin_error# $iA payment for wishin a track is in progress please try later.');
                        $this->eXpChatSendServerMessage($msg, $login);
                        return;
                    }

                    //Start Bill
                    $this->paymentInProgress = true;

                    if (!empty($this->donateConfig->toLogin)) {
                        $toLogin = $this->donateConfig->toLogin;
                    } else {
                        $toLogin = $this->storage->serverLogin;
                    }

                    $bill = $this->eXpStartBill($login, $toLogin, $amount, __("Are you sure you want to wish this map to be played", $login), array($this, 'validateQueuMap'));
                    $bill->setSubject('map_wish');
                    $bill->setErrorCallback(5, array($this, 'failQueuMap'));
                    $bill->setErrorCallback(6, array($this, 'failQueuMap'));
                    $bill->map = $map;
                }
            } else {
                $msg = eXpGetMessage('#admin_error# $iYou can\'t wish for a map at the moment.');
                $this->eXpChatSendServerMessage($msg, $login);
            }
        }
    }

    public function validateQueuMap(Bill $bill)
    {
        $this->paymentInProgress = false;
        $this->queueMap($bill->getSourceLogin(), $bill->map, false, false);
    }

    public function failQueuMap(Bill $bill, $state, $stateName)
    {
        $this->paymentInProgress = false;
    }

    /**
     * Check if a map can be queud by a player
     *
     * @param      $login             The player that tries to queu the map
     * @param Map $map the map to be queud
     * @param bool $sendMessages should an error message be sent to the player
     *
     * @return bool if the map can be added
     */
    public function checkQueuMap($login, Map $map, $sendMessages = false)
    {
        /** @var Config $config */
        $this->config = Config::getInstance();

        if ($this->storage->currentMap->uId == $map->uId) {
            $msg = eXpGetMessage('#admin_error# $iThis map is currently playing...');
            if ($sendMessages) {
                $this->eXpChatSendServerMessage($msg, $login);
            }
            return false;
        }

        $loginCount = 0;
        foreach ($this->queue as $queue) {
            if ($queue->map->uId == $map->uId) {
                $msg = eXpGetMessage('#admin_error# $iThis map is already in the queue...');
                if ($sendMessages) {
                    $this->eXpChatSendServerMessage($msg, $login);
                }
                return false;
            }

            if (!AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN) && $queue->player->login == $login) {
                $loginCount++;
                if ($this->config->maxPlayerQueueSize >= 0 &&$loginCount >= $this->config->maxPlayerQueueSize) {
                    $msg = eXpGetMessage('#admin_error# $iYou have reached the maximum number of maps you can queue...');
                    if ($sendMessages) {
                        $this->eXpChatSendServerMessage($msg, $login);
                    }
                    return false;
                }
            }
        }

        if ($this->config->maxPlayerQueueSize >= 0 && $loginCount >= $this->config->maxPlayerQueueSize) {
            $msg = eXpGetMessage('#admin_error# $iYou have reached the maximum number of maps you can queue...');
            if ($sendMessages) {
                $this->eXpChatSendServerMessage($msg, $login);
            }
            return false;
        }

        if (!AdminGroups::hasPermission($login, 'map_jukebox')) {
            foreach ($this->history as $histMap) {
                if ($histMap->uId == $map->uId) {
                    $msg = eXpGetMessage('#admin_error# $iMap has been played too recently...');
                    if ($sendMessages) {
                        $this->eXpChatSendServerMessage($msg, $login);
                    }
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Queus the map if possible
     *
     * @param      $login       The player that wants to queu the map
     * @param Map $map The map to be queud
     * @param bool $isTemp will the map be deleted after
     * @param bool $check should we check if adding the map is possible
     */
    public function queueMap($login, Map $map, $isTemp = false, $check = true)
    {

        $player = $this->storage->getPlayerObject($login);

        try {
            if ($check && !$this->checkQueuMap($login, $map, true)) {
                return;
            }

            $this->queue[] = new MapWish($player, $map, $isTemp);

            $queueCount = count($this->queue);
            if ($queueCount == 1) {
                $this->nextMap = $map;
                if ($this->config->showNextMapWidget) {
                    $this->showNextMapWidget();
                }
            }

            $this->eXpChatSendServerMessage($this->msg_addQueue, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->cleanNickName, 'wosnm'), $player->login, $queueCount));
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__('Error: %s', $login, $e->getMessage()));
        }
    }

    public function queueMxMap($login, $file)
    {
        try {
            $this->connection->addMap($file);
            $player = $this->storage->getPlayerObject($login);
            $map = $this->connection->getMapInfo($file);

            $this->queue[] = new MapWish($player, $map, true);

            $queueCount = count($this->queue);
            if ($queueCount == 1) {
                $this->nextMap = $map;
                if ($this->config->showNextMapWidget) {
                    $this->showNextMapWidget();
                }
            }
            if ($queueCount <= 31) {
                $queueCount = date('jS', strtotime('2007-01-' . $queueCount));
            }

            $this->eXpChatSendServerMessage($this->msg_addQueue, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->cleanNickName, 'wosnm'), $player->login, $queueCount));
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__('Error: %s', $login, $e->getMessage()));
        }
    }

    /**
     * Changes the next map and slips the current map
     *
     * @param string $login  player that initiate the goto map
     * @param string $uid    The next map
     */
    public function gotoMap($login, $uid)
    {
        $map = $this->findMapByUId($uid);
        if (!$map) {
            $this->eXpChatSendServerMessage('Map not found', $login);
            return;
        }

        try {
            $player = $this->storage->getPlayerObject($login);

            $this->connection->chooseNextMap($map->fileName);
            $this->is_onEndMatch = true; // Make eXpansion ignore jukebox
            $map = $this->connection->getNextMapInfo();
            $this->connection->nextMap();

            $widget = new Widget("Maps\Gui\Widgets\CurrentMapWidget.xml");
            $widget->setName("Current Map Widget");
            $widget->setLayer("scorestable");
            $widget->erase();

            $widget = new Widget("Maps\Gui\Widgets\NextMapWidget.xml");
            $widget->setName("Next Map");
            $widget->setLayer("scorestable");
            $widget->erase();

            if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $map->fileName)) {
                try {
                    $gbxInfo = new GBXChallMapFetcher(true, false, false);
                    $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $map->fileName);
                    $this->eXpChatSendServerMessage($this->msg_queueNow, null, array(Formatting::stripCodes($map->name, 'wosnm'), $gbxInfo->authorNick, Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
                } catch (Exception $e) {
                    $this->eXpChatSendServerMessage($this->msg_queueNow, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
                }
            } else {
                $this->eXpChatSendServerMessage($this->msg_queueNow, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
            }

        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__('Error: %s', $login, $e->getMessage()));
        }
    }

    /**
     * Removes a map from a server
     *
     * @param string $login
     * @param string $uid
     */
    public function removeMap($login, $uid)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $msg = eXpGetMessage('#admin_error# $iYou are not allowed to do that!');
            $this->eXpChatSendServerMessage($msg, $login);
            return;
        }

        $map = $this->findMapByUId($uid);
        if (!$map) {
            $this->eXpChatSendServerMessage('Map not found', $login);
            return;
        }

        try {
            $player = $this->storage->getPlayerObject($login);
            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#removed the map #variable#%3$s #admin_action# from the playlist');
            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author));
            $this->connection->removeMap($map->fileName);
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__("Error: %s", $login, $e->getMessage()));
        }
    }

    /**
     * Removes a map from the server and deletes the file
     *
     * @param string $login
     * @param string $uid
     */
    public function eraseMap($login, $uid)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $msg = eXpGetMessage('#admin_error# $iYou are not allowed to do that!');
            $this->eXpChatSendServerMessage($msg, $login);
            return;
        }

        $map = $this->findMapByUId($uid);
        if (!$map) {
            $this->eXpChatSendServerMessage('Map not found', $login);
            return;
        }

        try {
            $player = $this->storage->getPlayerObject($login);
            $found = false;
            foreach ($this->storage->maps as $storagemap) {
                if ($storagemap->uId == $map->uId) {
                    $found = true;
                    $this->connection->removeMap($map->fileName);
                }
            }
            $msg = "";
            $recievers = null;
            $additions = "";
            if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->isRemoteControlled) {
                if ($found) {
                    $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#removed the map #variable#%3$s #admin_action# from playlist!');
                } else {
                    $msg = eXpGetMessage('#admin_error#Map #variable#%3$s #admin_error# not found at playlist, perhaps it was already removed ?');
                    $recievers = $login;
                }
                $this->eXpChatSendServerMessage($msg, $recievers, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author));
                return;
            } else {

                try {
                    if (file_exists(Helper::getPaths()->getDefaultMapPath() . $map->fileName)) {
                        unlink(Helper::getPaths()->getDefaultMapPath() . $map->fileName);

                        if ($found) {
                            $additions = "playlist and disk!";
                        } else {
                            $additions = "disk!";
                        }
                    }
                } catch (\Exception $ex) {
                    if ($found) {
                        $additions = "playlist";
                    }
                }
                if ($additions != "") {
                    $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#erased the map #variable#%3$s by %4$s #admin_action# from %5$s');
                    $this->eXpChatSendServerMessage($msg, $recievers, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author, $additions));
                } else {
                    $msg = eXpGetMessage('#admin_error#Nothing to do, the map has been removed already from playlist and from disk!');
                    $this->eXpChatSendServerMessage($msg, $login);
                }
            }
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__("Error: %s", $login, $e->getMessage()));
        }
    }

    /**
     * When the map list is modified refresh the queu adn update the widgets & windows
     *
     * @param int $curMapIndex
     * @param int $nextMapIndex
     * @param bool $isListModified
     */
    public function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
    {
        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            $this->nextMap = $queue->map;
        } else {
            $this->nextMap = $this->storage->nextMap;
        }
        // update all widgets
        if ($this->config->showNextMapWidget && !$this->is_onEndMatch) {
            $this->showNextMapWidget();
        }
        // update all open Maplist windows
        if ($isListModified) {
            foreach ($this->mapListWindowOpened as $login => $unused) {
                $this->showMapListWindow($login);
            }
        }
    }

    /**
     * @return MapWish[] The list of maps in the queue
     */
    public function returnQueue()
    {
        return $this->queue;
    }

    /**
     * Loads map history when expansion starts
     */
    public function preloadHistory()
    {
        $mapCount = count($this->storage->maps);
        if ($mapCount == 0) {
            return;
        }

        $chunkSize       = 1000;
        $currentMapIndex = $this->connection->getCurrentMapIndex();
        $endIndex        = min($this->config->historySize - 1, $mapCount);
        $this->history   = array();

        // Determine which absolute map indices we need (backwards, with wraparound)
        $neededIndices = array();
        $idx = $currentMapIndex - 1;
        for ($j = 0; $j < $endIndex; $j++) {
            if ($idx < 0) {
                $idx = $mapCount - 1;
            }
            $neededIndices[$idx] = true;
            $idx--;
        }

        // Identify which chunks contain those indices
        $chunkNums = array();
        foreach ($neededIndices as $absIdx => $unused) {
            $chunkNums[(int)($absIdx / $chunkSize)] = true;
        }

        // Fetch only the needed chunks
        $fetchedMaps = array();
        foreach (array_keys($chunkNums) as $chunkNum) {
            try {
                $chunk = $this->connection->getMapList($chunkSize, $chunkNum * $chunkSize);
                foreach ($chunk as $posInChunk => $map) {
                    $fetchedMaps[$chunkNum * $chunkSize + $posInChunk] = $map;
                }
            } catch (\Exception $e) {}
        }

        // Reconstruct history in the correct backwards order
        $idx = $currentMapIndex - 1;
        for ($j = 0; $j < $endIndex; $j++) {
            if ($idx < 0) {
                $idx = $mapCount - 1;
            }
            if (isset($fetchedMaps[$idx])) {
                $this->history[] = $fetchedMaps[$idx];
            }
            $idx--;
        }

        array_unshift($this->history, $this->storage->currentMap);
    }

    /**
     * Chat command to remove a map
     *
     * @param $login
     * @param $params
     */
    public function chat_removeMap($login)
    {
        try {
            $this->removeMap($login, $this->storage->currentMap->uId);
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__("Error: %s", $login, $e->getMessage()));
        }
    }

    /**
     * Chat command to erease a map
     *
     * @param $login
     * @param $params
     */
    public function chat_eraseMap($login)
    {
        try {
            $this->eraseMap($login, $this->storage->currentMap->uId);
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__("Error: %s", $login, $e->getMessage()));
        }
    }

    /**
     * Chat command to get information about the next map
     *
     * @param null $login
     */
    public function chat_nextMap($login = null)
    {
        if ($login != null) {
            if (count($this->queue) > 0) {
                reset($this->queue);
                $queue = current($this->queue);

                if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $queue->map->fileName)) {
                    try {
                        $gbxInfo = new GBXChallMapFetcher(true, false, false);
                        $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $queue->map->fileName);
                        $this->eXpChatSendServerMessage($this->msg_nextQueue, $login, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $gbxInfo->authorNick, Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), $queue->player->login));
                    } catch (Exception $e) {
                        $this->eXpChatSendServerMessage($this->msg_nextQueue, $login, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), $queue->player->login));
                    }
                } else {
                    $this->eXpChatSendServerMessage($this->msg_nextQueue, $login, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), $queue->player->login));
                }

            } else {

                if (file_exists($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $this->storage->nextMap->fileName)) {
                    try {
                        $gbxInfo = new GBXChallMapFetcher(true, false, false);
                        $gbxInfo->processFile($this->connection->getMapsDirectory() . DIRECTORY_SEPARATOR . $this->storage->nextMap->fileName);
                        $this->eXpChatSendServerMessage($this->msg_nextMap, $login, array(Formatting::stripCodes($this->storage->nextMap->name, 'wosnm'), $gbxInfo->authorNick));
                    } catch (Exception $e) {
                        $this->eXpChatSendServerMessage($this->msg_nextMap, $login, array(Formatting::stripCodes($this->storage->nextMap->name, 'wosnm'), $this->storage->nextMap->author));
                    }
                } else {
                    $this->eXpChatSendServerMessage($this->msg_nextMap, $login, array(Formatting::stripCodes($this->storage->nextMap->name, 'wosnm'), $this->storage->nextMap->author));
                }
            }
        }
    }

    /**
     * Removes one map from the queu
     *
     * @param $login
     * @param $map
     */
    public function dropQueue($login, $map)
    {
        $i = 0;
        foreach ($this->queue as $queue) {
            if ($queue->map->uId == $map->uId) {
                array_splice($this->queue, $i, 1);
                $msg = eXpGetMessage('#variable#%1$s #queue#removed #variable#%2$s #queue#from the queue..');
                $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($this->storage->getPlayerObject($login)->cleanNickName, 'wosnm'), Formatting::stripCodes($queue->map->name, 'wosnm')));
                $this->showJukeList($login);
                break;
            }
            $i++;
        }
        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            $this->nextMap = $queue->map;
        } else {
            $this->nextMap = $this->storage->nextMap;
        }
        if ($this->config->showNextMapWidget) {
            $this->showNextMapWidget();
        }
    }

    /**
     * Chat command to drop all the maps added  by the player from the queue
     *
     * @param null $login
     */
    public function chat_dropQueue($login = null)
    {
        if ($login == null) {
            return;
        }

        if (count($this->queue) > 0) {
            $player = $this->storage->getPlayerObject($login);
            $i = 0;
            foreach ($this->queue as $queue) {
                if ($queue->player == $player) {
                    array_splice($this->queue, $i, 1);
                    $msg = eXpGetMessage('#variable#%1$s #queue#removed #variable#%2$s #queue#from the queue..');
                    $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($queue->player->cleanNickName, 'wosnm'), Formatting::stripCodes($queue->map->name, 'wosnm')));
                    break;
                }
                $i++;
            }
        } else {
            return;
        }
        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            $this->nextMap = $queue->map;
        } else {
            $this->nextMap = $this->storage->nextMap;
        }
        if ($this->config->showNextMapWidget) {
            $this->showNextMapWidget();
        }
    }

    /**
     * Empties totaly the queue
     *
     * @param $login
     */
    public function emptyWishesGui($login)
    {
        $this->emptyWishes($login);
        $this->showJukeList($login);
    }

    /**
     * Empties totaly the queue
     *
     * @param $login
     */
    public function emptyWishes($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN)) {
            $this->eXpChatSendServerMessage(AdminGroups::getNoPermissionMsg(), $login);
            return;
        }
        $player = $this->storage->getPlayerObject($login);
        $this->queue = array();
        $this->nextMap = $this->storage->nextMap;

        if ($this->config->showNextMapWidget) {
            $this->showNextMapWidget();
        }

        $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#emptied the map queue list');
        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
    }

    /**
     * Restart the current map
     *
     * @param $login
     */
    public function replayMapInstant($login)
    {
        $this->showNextMapWidget();
        $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
    }

    /**
     * Restart the current map
     *
     * @param $login
     */
    public function replayScoreReset($login)
    {
        $this->showNextMapWidget();
        $this->connection->restartMap(false);
    }

    /**
     * Add the current map to the begining of the queue for replay
     *
     * @param $login
     */
    public function replayMap($login)
    {
        $player = $this->storage->getPlayerObject($login);

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            if ($queue->map->uId == $this->storage->currentMap->uId) {
                $msg = eXpGetMessage('#admin_error# $iChallenge already set to be replayed!');
                $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
                return;
            }
        }
		
        if (!$this->is_onEndMatch) {
            array_unshift($this->queue, new MapWish($player, $this->storage->currentMap, false));
        } else {
            $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
        }

        $msg = eXpGetMessage('#queue#Challenge set to be replayed!');
        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));

        if ($this->config->showNextMapWidget && !$this->is_onEndMatch) {
            $this->nextMap = $this->storage->currentMap;
            $this->showNextMapWidget();
        }
    }

    public function previousMap($login)
    {
        $player = $this->storage->getPlayerObject($login);

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            if ($queue->map->uId == $this->storage->currentMap->uId) {
                $msg = eXpGetMessage('#admin_error# $iChallenge already set to be replayed!');
                $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
                return;
            }
        }

        if (isset($this->history[1])) {
            $map = $this->history[1];
            array_unshift($this->queue, new MapWish($player, $map, false));

            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#added previous map #variable#%3$s #admin_action# to the playlist');
            $this->eXpChatSendServerMessage( $msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author));
            if ($this->config->showNextMapWidget) {
                $this->showNextMapWidget();
            }
        } else {
            $msg = eXpGetMessage('#admin_error# $iThere are no previously played challenge!');
            $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $login));
        }
    }

    /**
     * Opens the local add maps window
     *
     * @param $login
     */
    public function addMaps($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_LOCAL)) {
            $this->eXpChatSendServerMessage(AdminGroups::getNoPermissionMsg(), $login);
            return;
        }
        if ($this->expStorage->isRemoteControlled) {
            $this->eXpChatSendServerMessage(eXpGetMessage("#admin_error#Can't continue, since this instance of eXpansion is running remote agains the server"), $login);
            return;
        }
        $window = AddMaps::Create($login);
        $window->setTitle('Add Maps on server');
        $window->centerOnScreen();
        $window->setSize(130, 100);
        $window->show();
    }

    public function showMapInfo($login, $uid = null)
    {
        if ($uid == null) {
            $uid = $this->storage->currentMap->uId;
        }

        $map = \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $uid);
        if ($map === false) {
            return;
        }

        $authorNick  = "n/a";
        $mood        = isset($map->mood) ? $map->mood : "";
        $nbLap       = isset($map->nbLap) ? $map->nbLap : 0;
        $nbCheck     = isset($map->nbCheckpoint) ? $map->nbCheckpoint : 0;
        $authorTime  = isset($map->authorTime) ? $map->authorTime : 0;
        $silverTime  = isset($map->silverTime) ? $map->silverTime : 0;
        $bronzeTime  = isset($map->bronzeTime) ? $map->bronzeTime : 0;
        $songFile    = isset($map->songFile) ? $map->songFile : "";
        $modName     = isset($map->modName) ? $map->modName : "";
        $carType     = "";
        $modUrl      = "";
        $songUrl     = "";

        try {
            $mapPath = $this->connection->getMapsDirectory();
            if (file_exists($mapPath . DIRECTORY_SEPARATOR . $map->fileName)) {
                $gbxInfo = new GBXChallMapFetcher(true, false, false);
                $gbxInfo->processFile($mapPath . DIRECTORY_SEPARATOR . $map->fileName);
                if ($gbxInfo) {
                    $mood       = $gbxInfo->mood;
                    $nbLap      = $gbxInfo->nbLaps;
                    $nbCheck    = $gbxInfo->nbChecks;
                    $authorTime = $gbxInfo->authorTime;
                    $silverTime = $gbxInfo->silverTime;
                    $bronzeTime = $gbxInfo->bronzeTime;
                    $songFile   = $gbxInfo->songFile;
                    $modName    = $gbxInfo->modName;
                    $authorNick = $gbxInfo->authorNick;
                    $carType    = $gbxInfo->vehicle;
                    $modUrl     = $gbxInfo->modUrl ? $gbxInfo->modUrl : "";
                    $songUrl    = $gbxInfo->songUrl ? $gbxInfo->songUrl : "";
                }
            }
        } catch (\Exception $ex) {
            $this->console("Info: Map not found or error while reading gbx info for map.");
        }

        $date = new \DateTime();
        $date->setTimestamp((int)$map->addTime);

        $this->mapInfoWindow->setTitle("Map Info %s", array($map->name));
        $this->mapInfoWindow->setParam("uid",         $map->uId);
        $this->mapInfoWindow->setParam("fileName",    $map->fileName);
        $this->mapInfoWindow->setParam("mapName",     $map->name);
        $this->mapInfoWindow->setParam("author",      $map->author);
        $this->mapInfoWindow->setParam("authorNick",  $authorNick);
        $this->mapInfoWindow->setParam("mood",        $mood);
        $this->mapInfoWindow->setParam("mapStyle",    $map->mapStyle);
        $this->mapInfoWindow->setParam("mapType",     $map->mapType);
        $this->mapInfoWindow->setParam("environment", $map->environnement);
        $this->mapInfoWindow->setParam("carType",     $carType);
        $this->mapInfoWindow->setParam("addDate",     $date->format("d.m.Y"));
        $this->mapInfoWindow->setParam("authorTime",  \ManiaLive\Utilities\Time::fromTM($authorTime));
        $this->mapInfoWindow->setParam("goldTime",    \ManiaLive\Utilities\Time::fromTM($map->goldTime));
        $this->mapInfoWindow->setParam("silverTime",  \ManiaLive\Utilities\Time::fromTM($silverTime));
        $this->mapInfoWindow->setParam("bronzeTime",  \ManiaLive\Utilities\Time::fromTM($bronzeTime));
        $this->mapInfoWindow->setParam("checkpoints", strval($nbCheck));
        $this->mapInfoWindow->setParam("laps",        strval($nbLap));
        $this->mapInfoWindow->setParam("displayCost", strval($map->copperPrice));
        $this->mapInfoWindow->setParam("songName",    $songFile);
        $this->mapInfoWindow->setParam("modName",     $modName);
        $this->mapInfoWindow->setParam("modUrl",      $modUrl);
        $this->mapInfoWindow->setParam("songUrl",     $songUrl);
        $this->mapInfoWindow->show($login);
    }

    public function onMapRestart()
    {
        $this->is_onEndMatch = true;
    }

    public function onMapSkip()
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup") {
            $this->onEndMatch(array(),array(), true);
        }
    }

    public function eXpOnUnload()
    {
        $widget = new Widget("Maps\Gui\Widgets\CurrentMapWidget.xml");
        $widget->setName("Current Map Widget");
        $widget->setLayer("scorestable");
        $widget->erase();

        $widget = new Widget("Maps\Gui\Widgets\NextMapWidget.xml");
        $widget->setName("Next Map");
        $widget->setLayer("scorestable");
        $widget->erase();

        AddMaps::EraseAll();
        Jukelist::EraseAll();

        if ($this->mapInfoWindow instanceof Window) {
            $this->mapInfoWindow->erase();
        }
        $this->mapInfoWindow = null;
        if ($this->filterWindow instanceof Window) {
            $this->filterWindow->erase();
        }
        $this->filterWindow = null;
        if ($this->mapListWindow instanceof Window) {
            $this->mapListWindow->erase();
        }
        $this->mapListWindow = null;

        $this->mapListWindowOpened = array();

        AdminGroups::removeAdminCommand($this->cmd_replay);
        AdminGroups::removeAdminCommand($this->cmd_erease);
        AdminGroups::removeAdminCommand($this->cmd_remove);
        AdminGroups::removeAdminCommand($this->cmd_prev);
        AdminGroups::removeAdminCommand($this->cmd_cjb);
    }
}
