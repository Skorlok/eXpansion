<?php

namespace ManiaLivePlugins\eXpansion\Maps;

use Exception;
use ManiaLib\Utils\Formatting;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminCmd;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\Bill;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Donate\Config as Donate;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\AddMaps;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\Jukelist;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\Maplist;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\MapInfo;
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
    private $actionShowMapList;
    private $actionShowJukeList;

    /** @var MapSortMode[] */
    public static $playerSortModes = array();
    public static $searchTerm = array();
    public static $searchField = array();
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

        $this->config = Config::getInstance();
        $this->donateConfig = Donate::getInstance();

        $this->setPublicMethod("queueMap");
        $this->setPublicMethod("queueMxMap");
        $this->setPublicMethod("replayMap");
        $this->setPublicMethod("replayMapInstant");
        $this->setPublicMethod("replayScoreReset");
        $this->setPublicMethod("returnQueue");
        $this->setPublicMethod("showMapList");
        $this->setPublicMethod("showMapList_menu");
        $this->setPublicMethod("showJukeList");
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
        $this->enableDatabase();

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        Menu::addMenuItem("Maps",
            array("Maps" => array(null, array(
                "Show Maps" => array(null, $aH->createAction(array($this, "showMapList"))),
                "Show Jukebox" => array(null, $aH->createAction(array($this, "showJukeList"))),
                "Add Local Maps" => array(Permission::MAP_ADD_LOCAL, $aH->createAction(array($this, "addMaps"))),
                '$f00Remove this' => array(Permission::MAP_REMOVE_MAP, $aH->createAction(array($this, "chat_removeMap"))),
                '$f00Trash this' => array(Permission::MAP_REMOVE_MAP, $aH->createAction(array($this, "chat_eraseMap")))
            )))
        );
    }

    public function eXpOnReady()
    {
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

        $this->registerChatCommand('best', "showBestMapList", 0, true);
        $this->registerChatCommand('worst', "showWorstMapList", 0, true);

        $this->registerChatCommand('mapinfo', "showMapInfo", 0, true);

        $this->registerChatCommand('nextmap', "chat_nextMap", 0, true);

        $this->registerChatCommand('jb', "jukebox", 0, true);
        $this->registerChatCommand('jb', "jukebox", 1, true);

        $this->registerChatCommand('jukebox', "jukebox", 0, true);
        $this->registerChatCommand('jukebox', "jukebox", 1, true);

        $this->registerChatCommand('history', "showHistoryList", 0, true);


        $this->nextMap = $this->storage->nextMap;

        Maplist::Initialize($this);
        Jukelist::$mainPlugin = $this;
        AddMaps::$mapsPlugin = $this;
        /** @var \ManiaLive\Gui\ActionHandler */
        $action = \ManiaLive\Gui\ActionHandler::getInstance();
        $this->actionShowMapList = $action->createAction(array($this, "showMapList_menu"));
        $this->actionShowJukeList = $action->createAction(array($this, "showJukeList"));

        $this->showCurrentMapWidget();
        $this->showNextMapWidget();

        $this->preloadHistory();
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
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
     * @param Map $map
     */
    public function showRec($login, $map)
    {
        $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "showRecsWindow", $login, $map);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (empty($login)) {
            return;
        }

        if (array_key_exists($login, self::$playerSortModes)) {
            unset(self::$playerSortModes[$login]);
        }
        if (array_key_exists($login, self::$searchTerm)) {
            unset(self::$searchTerm[$login]);
        }
        if (array_key_exists($login, self::$searchField)) {
            unset(self::$searchField[$login]);
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
            $widget->setParam("action", $this->actionShowMapList);
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
            $widget->setParam("action", $this->actionShowJukeList);
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
                        $this->eXpChatSendServerMessage($this->msg_skipleft, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), Formatting::stripCodes($queue->player->nickName, 'wosnm')));
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
                            $this->eXpChatSendServerMessage($this->msg_nextQueue, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $gbxInfo->authorNick, Formatting::stripCodes($queue->player->nickName, 'wosnm'), $queue->player->login, $queue->map->environnement));
                        } catch (Exception $e) {
                            $this->eXpChatSendServerMessage($this->msg_nextQueue, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->nickName, 'wosnm'), $queue->player->login, $queue->map->environnement));
                        }
                    } else {
                        $this->eXpChatSendServerMessage($this->msg_nextQueue, null, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->nickName, 'wosnm'), $queue->player->login, $queue->map->environnement));
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

    public function showMapList_menu($login)
    {
        $this->showMapList($login); // Fail safe for the exp menu who add "\ManiaLivePlugins\eXpansion\Menu\Menu" as params
    }

    public function showBestMapList($login)
    {
        $this->showMapList($login, "best");
    }

    public function showWorstMapList($login)
    {
        $this->showMapList($login, "worst");
    }

    public function showMapList($login, $params = null)
    {
        Maplist::Erase($login);

        if ($params) {

            if (array_key_exists($login, self::$playerSortModes) == false) {
                self::$playerSortModes[$login] = new \ManiaLivePlugins\eXpansion\Maps\Structures\MapSortMode();
            }

            if ($params == "novote" && $this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings')){
                $this->showNoVoteList($login);
                return;
            }

            else if ($params == "best") {
                self::$playerSortModes[$login]->column = "localrecord";
                self::$playerSortModes[$login]->sortMode = 1;
            }
            else if ($params == "worst") {
                self::$playerSortModes[$login]->column = "localrecord";
                self::$playerSortModes[$login]->sortMode = 2;
            }

            else if ($params == "nofinish") {
                self::$playerSortModes[$login]->column = "localrecord";
                self::$playerSortModes[$login]->sortMode = 2;
            }

            else if ($params == "newest") {
                self::$playerSortModes[$login]->column = "addTime";
                self::$playerSortModes[$login]->sortMode = 2;
            }
            else if ($params == "oldest") {
                self::$playerSortModes[$login]->column = "addTime";
                self::$playerSortModes[$login]->sortMode = 1;
            }

            else if ($params == "longest") {
                self::$playerSortModes[$login]->column = "goldTime";
                self::$playerSortModes[$login]->sortMode = 2;
            }
            else if ($params == "shortest") {
                self::$playerSortModes[$login]->column = "goldTime";
                self::$playerSortModes[$login]->sortMode = 1;
            }

            else {
                self::$searchTerm[$login] = $params;
                self::$searchField[$login] = null;
            }
        }

        $window = Maplist::Create($login);
        $window->setTitle(__('Maps on server', $login), " (" . count($this->storage->maps) . ")");
        $window->setHistory($this->history);
        $window->setCurrentMap($this->storage->currentMap);

        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords')) {
            $this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords', 'getPlayersRecordsForAllMaps', $login);
            Maplist::$localrecordsLoaded = true;
        } else {
            Maplist::$localrecordsLoaded = false;
        }

        $window->centerOnScreen();
        $window->setSize(200, 100);
        $window->updateList($login);
        $window->show();
    }

    public function showNoVoteList($login)
    {
        Maplist::Erase($login);
        $window = Maplist::Create($login);
        $window->setHistory($this->history);
        $window->setTitle(__("Maps You Didn't Vote For", $login));
        if ($this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            $this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords', 'getPlayersRecordsForAllMaps', $login);
            Maplist::$localrecordsLoaded = true;
        } else {
            Maplist::$localrecordsLoaded = false;
        }

        $voteList = array();
        $votes = $this->db->execute('SELECT * FROM exp_ratings WHERE login = "' . $login . '";')->fetchArrayOfObject();
        for ($i = 0; $i < count($votes); $i++) {
            $voteList[$votes[$i]->uid] = $votes[$i];
        }

        $noVoteList = array();
        foreach ($this->storage->maps as $id => $map) {
            if (!isset($voteList[$map->uId])) {
                array_push($noVoteList, $this->storage->maps[$id]);
            }
        }

        $window->centerOnScreen();
        $window->setSize(200, 100);
        $window->updateList($login, null, null, $noVoteList);
        $window->show();
    }

    public function showHistoryList($login)
    {
        Maplist::Erase($login);
        $window = Maplist::Create($login);
        $window->setHistory($this->history);
        $window->setTitle(__('History of Maps', $login));
        if ($this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            $window->setRecords($this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords', 'getPlayersRecordsForAllMaps', $login));
            Maplist::$localrecordsLoaded = true;
        } else {
            Maplist::$localrecordsLoaded = false;
        }

        $window->centerOnScreen();
        $window->setSize(200, 100);
        $window->updateList($login, null, null, $this->history);
        $window->show();
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
        if (!empty($this->config->publicQueuAmount) && $this->config->publicQueuAmount != -1) {
            if (isset($this->config->publicQueuAmount[sizeof($this->queue)])) {
                $amount = $this->config->publicQueuAmount[sizeof($this->queue)];
                return $amount != -1 ? $amount : 0;
            }
            return -1; //Impossible
        }
        return 0;
    }

    /**
     * Makes a player queu a map
     *
     * @param      $login       Player that wishes to queu the map
     * @param Map $map the map to queu
     * @param bool $isTemp will te map be deleted after being playerd
     */
    public function playerQueueMap($login, Map $map, $isTemp = false)
    {
        $amount = $this->getQueuAmount();

        if ($amount == 0 || AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_FREE)) {
            $this->queueMap($login, $map, $isTemp);
        } else {
            if ($amount != -1) {
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
                $msg = eXpGetMessage('#admin_error# $iYOu can\'t wish for a map at the moment.');
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
        $this->config = Config::getInstance();

        if ($this->storage->currentMap->uId == $map->uId) {
            $msg = eXpGetMessage('#admin_error# $iThis map is currently playing...');
            if ($sendMessages) {
                $this->eXpChatSendServerMessage($msg, $login);
            }
            return false;
        }

        foreach ($this->queue as $queue) {
            if ($queue->map->uId == $map->uId) {
                $msg = eXpGetMessage('#admin_error# $iThis map is already in the queue...');
                if ($sendMessages) {
                    $this->eXpChatSendServerMessage($msg, $login);
                }
                return false;
            }

            if (!AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN) && $queue->player->login == $login) {
                $msg = eXpGetMessage('#admin_error# $iYou already have a map in the queue...');
                if ($sendMessages) {
                    $this->eXpChatSendServerMessage($msg, $login);
                }
                return false;
            }
        }

        if (!AdminGroups::hasPermission($login, 'map_jukebox') && $this->config->bufferSize + 1 > 0) {
            for ($i = 0; $i <= $this->config->bufferSize + 1; $i++) {
                $cp = sizeof($this->history) - 1 - $i;
                if (isset($this->history[$cp])) {
                    if ($this->history[$cp]->uId == $map->uId) {
                        $msg = eXpGetMessage('#admin_error# $iMap has been played too recently...');
                        if ($sendMessages) {
                            $this->eXpChatSendServerMessage($msg, $login);
                        }
                        return false;
                    }
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

            $this->eXpChatSendServerMessage($this->msg_addQueue, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->nickName, 'wosnm'), $player->login, $queueCount));
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

            $this->eXpChatSendServerMessage($this->msg_addQueue, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->nickName, 'wosnm'), $player->login, $queueCount));
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__('Error: %s', $login, $e->getMessage()));
        }
    }

    /**
     * Changes the next map and slips the current map
     *
     * @param     $login   player that initiate the goto map
     * @param Map $map The next map
     */
    public function gotoMap($login, Map $map)
    {
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
                    $this->eXpChatSendServerMessage($this->msg_queueNow, null, array(Formatting::stripCodes($map->name, 'wosnm'), $gbxInfo->authorNick, Formatting::stripCodes($player->nickName, 'wosnm'), $login));
                } catch (Exception $e) {
                    $this->eXpChatSendServerMessage($this->msg_queueNow, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->nickName, 'wosnm'), $login));
                }
            } else {
                $this->eXpChatSendServerMessage($this->msg_queueNow, null, array(Formatting::stripCodes($map->name, 'wosnm'), $map->author, Formatting::stripCodes($player->nickName, 'wosnm'), $login));
            }

        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__('Error: %s', $login, $e->getMessage()));
        }
    }

    /**
     * Removes a map from a server
     *
     * @param     $login
     * @param Map $map
     */
    public function removeMap($login, Map $map)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $msg = eXpGetMessage('#admin_error# $iYou are not allowed to do that!');
            $this->eXpChatSendServerMessage($msg, $login);
            return;
        }

        try {
            $player = $this->storage->getPlayerObject($login);
            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#removed the map #variable#%3$s #admin_action# from the playlist');
            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->nickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author));
            $this->connection->removeMap($map->fileName);
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__("Error: %s", $login, $e->getMessage()));
        }
    }

    /**
     * Removes a map from the server and deletes the file
     *
     * @param     $login
     * @param Map $map
     */
    public function eraseMap($login, Map $map)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $msg = eXpGetMessage('#admin_error# $iYou are not allowed to do that!');
            $this->eXpChatSendServerMessage($msg, $login);
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
                $this->eXpChatSendServerMessage($msg, $recievers, array(Formatting::stripCodes($player->nickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author));
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
                    $this->eXpChatSendServerMessage($msg, $recievers, array(Formatting::stripCodes($player->nickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author, $additions));
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
            $windows = Maplist::GetAll();

            foreach ($windows as $window) {
                $login = $window->getRecipient();
                $this->showMapList($login);
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
        $mapList = $this->connection->getMapList(-1, 0);
        $mapCount = count($mapList);
        if ($mapCount == 0) {
            return;
        }

        $currentMapIndex = $this->connection->getCurrentMapIndex();
        $i = $currentMapIndex - 1;
        $this->history = array();

        $endIndex = $this->config->historySize - 1;
        if (sizeof($mapList) < $this->config->historySize - 1) {
            $endIndex = sizeof($mapList);
        }
        for ($j = 0; $j < $endIndex; $j++) {
            if (isset($mapList[$i])) {
                $this->history[] = $mapList[$i];
            }
            $i--;
            if ($i < 0) {
                $i = $mapCount - 1;
            }
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
            $this->removeMap($login, $this->storage->currentMap);
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
            $this->eraseMap($login, $this->storage->currentMap);
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
                        $this->eXpChatSendServerMessage($this->msg_nextQueue, $login, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $gbxInfo->authorNick, Formatting::stripCodes($queue->player->nickName, 'wosnm'), $queue->player->login));
                    } catch (Exception $e) {
                        $this->eXpChatSendServerMessage($this->msg_nextQueue, $login, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->nickName, 'wosnm'), $queue->player->login));
                    }
                } else {
                    $this->eXpChatSendServerMessage($this->msg_nextQueue, $login, array(Formatting::stripCodes($queue->map->name, 'wosnm'), $queue->map->author, Formatting::stripCodes($queue->player->nickName, 'wosnm'), $queue->player->login));
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
                $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($this->storage->getPlayerObject($login)->nickName, 'wosnm'), Formatting::stripCodes($queue->map->name, 'wosnm')));
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
                    $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($queue->player->nickName, 'wosnm'), Formatting::stripCodes($queue->map->name, 'wosnm')));
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
        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->nickName, 'wosnm'), $login));
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
                $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->nickName, 'wosnm'), $login));
                return;
            }
        }
		
        if (!$this->is_onEndMatch) {
            array_unshift($this->queue, new MapWish($player, $this->storage->currentMap, false));
        } else {
            $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
        }

        $msg = eXpGetMessage('#queue#Challenge set to be replayed!');
        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->nickName, 'wosnm'), $login));

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
                $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->nickName, 'wosnm'), $login));
                return;
            }
        }

        if (isset($this->history[1])) {
            $map = $this->history[1];
            array_unshift($this->queue, new MapWish($player, $map, false));

            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action#added previous map #variable#%3$s #admin_action# to the playlist');
            $this->eXpChatSendServerMessage( $msg, null, array(Formatting::stripCodes($player->nickName, 'wosnm'), null, Formatting::stripCodes($map->name, 'wosnm'), $map->author));
            if ($this->config->showNextMapWidget) {
                $this->showNextMapWidget();
            }
        } else {
            $msg = eXpGetMessage('#admin_error# $iThere are no previously played challenge!');
            $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->nickName, 'wosnm'), $login));
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
        $window = MapInfo::create($login);
        $window->setMap($uid);
        $window->setTitle("Map Info", $this->storage->currentMap->name);
        $window->setSize(160, 90);
        $window->show($login);
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

        Maplist::EraseAll();
        AddMaps::EraseAll();
        Jukelist::EraseAll();
        MapInfo::EraseAll();

        AdminGroups::removeAdminCommand($this->cmd_replay);
        AdminGroups::removeAdminCommand($this->cmd_erease);
        AdminGroups::removeAdminCommand($this->cmd_remove);
        AdminGroups::removeAdminCommand($this->cmd_prev);
        AdminGroups::removeAdminCommand($this->cmd_cjb);

        /** @var ActionHandler $action */
        $action = \ManiaLive\Gui\ActionHandler::getInstance();
        $action->deleteAction($this->actionShowJukeList);
        $action->deleteAction($this->actionShowMapList);
    }
}
