<?php

namespace ManiaLivePlugins\eXpansion\Maps;

use Exception;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Utils\Formatting;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Gui\CustomUI;
use ManiaLive\Gui\Window;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminCmd;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\Bill;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\DonatePanel\Config as DonateConfig;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Maps\Gui\Widgets\CurrentMapWidget;
use ManiaLivePlugins\eXpansion\Maps\Gui\Widgets\NextMapWidget;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\AddMaps;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\Jukelist;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\Maplist;
use ManiaLivePlugins\eXpansion\Maps\Structures\MapSortMode;
use ManiaLivePlugins\eXpansion\Maps\Structures\MapWish;
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
    private $atPodium = false;
    private $instantReplay = false;
    private $paymentInProgress = false;
    private $messages;

    /** @var MapWish */
    private $voteItem;
    private $msg_addQueue;
    private $msg_nextQueue;
    private $msg_nextMap;
    private $msg_queueNow;
    private $msg_jukehelp;
    private $msg_errDwld;
    private $msg_errMxId;
    private $msg_mapAdd;
    private $wasWarmup = false;
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
    private $cmd_tag;

    private $actions = array();
    private $maps = array();
    private $ratings = array();

    private $isRestartMap = false;
    private $is_onBeginMatch = false;
    private $is_onEndMatch = false;

    public function eXpOnInit()
    {
        $this->messages = new \StdClass();

        $this->config = Config::getInstance();
        $this->config->bufferSize = $this->config->bufferSize + 1;
        $this->donateConfig = DonateConfig::getInstance();

        $this->setPublicMethod("queueMap");
        $this->setPublicMethod("queueMxMap");
        $this->setPublicMethod("replayMap");
        $this->setPublicMethod("replayMapInstant");
        $this->setPublicMethod("replayScoreReset");
        $this->setPublicMethod("returnQueue");
        $this->setPublicMethod("showMapList");
        $this->setPublicMethod("showJukeList");
        if ($this->expStorage->isRemoteControlled == false) {
            $this->setPublicMethod("addMaps");
        }
    }

    public function eXpOnReady()
    {

        $cmd = AdminGroups::addAdminCommand('map remove', $this, 'chat_removeMap', Permission::MAP_REMOVE_MAP);
        $cmd->setHelp(eXpGetMessage('Removes current map from the playlist.'));
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "remove");
        $this->cmd_remove = $cmd;

        $cmd = AdminGroups::addAdminCommand('map erase', $this, 'chat_eraseMap', Permission::MAP_REMOVE_MAP);
        $cmd->setHelp(eXpGetMessage('Erases current map from the playlist.'));
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "nuke this");
        AdminGroups::addAlias($cmd, "trash this");
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

        $this->registerChatCommand('list', "showMapList", 0, true);
        $this->registerChatCommand('maps', "showMapList", 0, true);
        $this->registerChatCommand('mapinfo', "showMapInfo", 0, true);

        $this->registerChatCommand('nextmap', "chat_nextMap", 0, true);

        $this->registerChatCommand('jb', "jukebox", 0, true);
        $this->registerChatCommand('jb', "jukebox", 1, true);


        $this->nextMap = $this->storage->nextMap;

        Maplist::Initialize($this);
        Jukelist::$mainPlugin = $this;
        Gui\Windows\AddMaps::$mapsPlugin = $this;
        /** @var \ManiaLive\Gui\ActionHandler */
        $action = \ManiaLive\Gui\ActionHandler::getInstance();
        $this->actionShowMapList = $action->createAction(array($this, "showMapList"));
        $this->actionShowJukeList = $action->createAction(array($this, "showJukeList"));


        CustomUI::HideForAll(CustomUI::CHALLENGE_INFO);
        $this->showCurrentMapWidget(null);
        $this->showNextMapWidget(null);

        $this->preloadHistory();

        // this is for fixes to storm gamemodes
        $this->enableScriptEvents(array("LibXmlRpc_BeginMap", "LibXmlRpc_EndMap", "LibXmlRpc_BeginPodium"));
    }

    public function eXpOnLoad()
    {
        $this->msg_addQueue = eXpGetMessage(
            '#variable#%1$s  #queue#has been added to the map queue '
            .'by #variable#%3$s#queue#, in the #variable#%5$s #queue#position'
        ); // '%1$s' = Map Name, '%2$s' = Map author %, '%3$s' = nickname, '%4$s' = login, '%5$s' = # in queue
        $this->msg_nextQueue = eXpGetMessage(
            '#queue#Next map will be #variable#%1$s  #queue#by #variable#%2$s#queue#, as requested by #variable#%3$s'
        ); // '%1$s' = Map Name, '%2$s' = Map author %, '%3$s' = nickname, '%4$s' = login
        $this->msg_nextMap = eXpGetMessage(
            '#queue#Next map will be #variable#%1$s  #queue#by #variable#%2$s#queue#'
        ); // '%1$s' = Map Name, '%2$s' = Map author
        $this->msg_queueNow = eXpGetMessage(
            '#queue#Map changed to #variable#%1$s  #queue#by #variable#%2$s#queue#, as requested by #variable#%3$s'
        ); // '%1$s' = Map Name, '%2$s' = Map author %, '%3$s' = nickname, '%4$s' = login
        $this->msg_jukehelp = eXpGetMessage('#queue#/jb uses next params: drop, reset and show');
        $this->msg_errDwld = eXpGetMessage('#admin_error#Error downloading, or MX is down!');
        $this->msg_errToLarge = eXpGetMessage('#admin_error#The map is to large to be added to a server');
        $this->msg_errMxId = eXpGetMessage("#admin_error#You must include a MX map ID!");
        $this->msg_mapAdd = eXpGetMessage(
            '#admin_action#Map #variable# %1$s #admin_action#added to playlist by #variable#%2$s'
        );
        $this->enableDedicatedEvents();
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
        $this->callPublicMethod(
            "\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords",
            "showRecsWindow",
            $login,
            $map
        );
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

    /**
     *    is a fix for storm gamemodes, which all doesn't emit onBeginMatch event.
     */
    public function LibXmlRpc_BeginMap()
    {
        $this->is_onEndMatch = false;

        if (!$this->is_onBeginMatch) {
            $this->onBeginMatch();
            $this->is_onBeginMatch = true;
        }
    }

   public function onBeginMatch()
    {
        $this->is_onEndMatch = false;

        if ($this->is_onBeginMatch) {
            return;
        }

        $this->is_onBeginMatch = true;
        $this->atPodium = false;

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
                array_shift($this->queue);
            } else {
                if ($this->tries < 3) {
                    $this->tries++;
                } else {
                    $this->tries = 0;
                    array_shift($this->queue);
                }
            }
        }

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            $this->nextMap = $queue->map;
        }

        if ($this->isRestartMap == false) {
            array_unshift($this->history, $this->storage->currentMap);
            if (count($this->history) > Config::getInstance()->historySize) {
                array_pop($this->history);
            }
        }

        $this->isRestartMap = false;
        $this->showCurrentMapWidget(null);
        $this->showNextMapWidget(null);
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->is_onEndMatch = false;
        $this->is_onBeginMatch = false;
        $this->showCurrentMapWidget(null);
        $this->showNextMapWidget(null);
        CustomUI::HideForAll(CustomUI::CHALLENGE_INFO);
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {

    }

    public function onBeginRound()
    {
        $this->wasWarmup = $this->connection->getWarmUp();
    }

    public function showCurrentMapWidget($login)
    {
        if ($this->config->showCurrentMapWidget) {
            $info = CurrentMapWidget::Create(null, true);
            $info->setMap($this->storage->currentMap);
            $info->setLayer(Window::LAYER_SCORES_TABLE);
            $info->setAction($this->actionShowMapList);
            $info->show();
        }
    }

    public function showNextMapWidget($login)
    {
        if ($this->config->showNextMapWidget) {
            $info = NextMapWidget::Create(null, true);
            $info->setLayer(Window::LAYER_SCORES_TABLE);
            $info->setAction($this->actionShowJukeList);
            $info->setMap($this->nextMap);
            $info->show();
        }
    }

    /**
     * is a fix for storm gamemodes, which all doesn't emit onEndMatch
     */
    public function LibXmlRpc_EndMap()
    {
        $this->is_onBeginMatch = false;
        if (!$this->is_onEndMatch) {
            $this->onEndMatch(null, null);
            $this->is_onEndMatch = true;
        }
    }

    /**
     * is a fix for storm gamemodes, which all doesn't emit onEndMatch
     */
    public function LibXmlRpc_BeginPodium()
    {
        $this->is_onBeginMatch = false;
        if (!$this->is_onEndMatch) {
            $this->onEndMatch(null, null);
            $this->is_onEndMatch = true;
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap, $enduroSkipMap = false)
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false && $enduroSkipMap != true) {
            return;
        }
        $this->is_onBeginMatch = false;
        if ($this->is_onEndMatch) {
            return;
        }
        $this->is_onEndMatch = true;

        $this->config = Config::getInstance();
        // if ($this->wasWarmup) {
        //    return;
        // }

        $this->atPodium = true;

        NextMapWidget::EraseAll();
        CurrentMapWidget::EraseAll();

        if (count($this->queue) > 0) {
            reset($this->queue);
            $queue = current($this->queue);
            try {
                $this->connection->chooseNextMap($queue->map->fileName);

                if ($this->config->showEndMatchNotices) {
                    $this->eXpChatSendServerMessage(
                        $this->msg_nextQueue,
                        null,
                        array(
                            Formatting::stripCodes($queue->map->name, 'wosnm'),
                            $queue->map->author,
                            Formatting::stripCodes($queue->player->nickName, 'wosnm'),
                            $queue->player->login, $queue->map->environnement
                        )
                    );
                }
            } catch (Exception $e) {
                $this->eXpChatSendServerMessage('Error: %s', $queue->player->login, array($e->getMessage()));
                $key = key($this->queue);
                unset($this->queue[$key]);
                $this->eXpChatSendServerMessage(
                    'Recovering from error, map removed from jukebox...',
                    $queue->player->login
                );
            }
        } else {
            if ($this->config->showEndMatchNotices) {
                $map = $this->storage->nextMap;
                if ($this->instantReplay == true) {
                    $this->instantReplay = false;
                    $map = $this->storage->currentMap;
                }
                $this->eXpChatSendServerMessage(
                    $this->msg_nextMap,
                    null,
                    array(Formatting::stripCodes($map->name, 'wosnm'), $map->author)
                );
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
                case "reset":
                    if (AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN)) {
                        $this->emptyWishes($login);
                    }
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

    public function showMapList($login)
    {
        Maplist::Erase($login);
        self::$searchField[$login] = "name";

        $window = Maplist::Create($login);
        $window->setTitle(__('Maps on server', $login), " (" . count($this->storage->maps) . ")");
        $window->setHistory($this->history);
        $window->setCurrentMap($this->storage->currentMap);

        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords')) {
            $this->callPublicMethod(
                '\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords',
                'getPlayersRecordsForAllMaps',
                $login
            );
            Maplist::$localrecordsLoaded = true;
        } else {
            Maplist::$localrecordsLoaded = false;
        }

        $window->centerOnScreen();
        $window->setSize(180, 100);
        $window->updateList($login);
        $window->show();
    }

    public function showHistoryList($login)
    {
        Maplist::Erase($login);
        $window = Maplist::Create($login);
        $window->setHistory($this->history);
        $window->setTitle(__('History of Maps', $login));
        if ($this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            $window->setRecords(
                $this->callPublicMethod(
                    '\\ManiaLivePlugins\\eXpansion\\LocalRecords',
                    'getPlayersRecordsForAllMaps',
                    $login
                )
            );
        }
        if ($this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings')) {
            $window->setRatings(
                $this->callPublicMethod('\\ManiaLivePlugins\\eXpansion\\MapRatings\\MapRatings', 'getRatings')
            );
        }

        $window->centerOnScreen();
        $window->setSize(180, 100);
        $window->updateList($login, 'name', 'null', $this->history);
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
                        $msg = eXpGetMessage(
                            '#admin_error# $iA payment for wishin a track is in progress please try later.'
                        );
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

                    $bill = $this->eXpStartBill(
                        $login,
                        $toLogin,
                        $amount,
                        __("Are you sure you want to wish this map to be played", $login),
                        array($this, 'validateQueuMap')
                    );

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

        if (!AdminGroups::hasPermission($login, 'map_jukebox') && $this->config->bufferSize > 0) {
            for ($i = 0; $i <= $this->config->bufferSize; $i++) {
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
                    $this->redrawNextMapWidget();
                }
            }

            $this->eXpChatSendServerMessage(
                $this->msg_addQueue,
                null,
                array(
                    Formatting::stripCodes($map->name, 'wosnm'),
                    $map->author,
                    Formatting::stripCodes($player->nickName, 'wosnm'),
                    $player->login,
                    $queueCount
                )
            );
        } catch (Exception $e) {
            $this->eXpChatSendServerMessage(__('Error: %s', $login, $e->getMessage()));
        }
    }

    /**
     * Redraws the next map widget for all players on the server
     */
    public function redrawNextMapWidget()
    {
        $this->showNextMapWidget(null);
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
                    $this->redrawNextMapWidget();
                }
            }
            if ($queueCount <= 31) {
                $queueCount = date('jS', strtotime('2007-01-' . $queueCount));
            }

            $this->eXpChatSendServerMessage(
                $this->msg_addQueue,
                null,
                array(
                    Formatting::stripCodes($map->name, 'wosnm'),
                    $map->author,
                    Formatting::stripCodes($player->nickName, 'wosnm'),
                    $player->login,
                    $queueCount
                )
            );
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
            $map = $this->connection->getNextMapInfo();
            if ($this->config->showNextMapWidget) {
                $this->redrawNextMapWidget();
            }
            $this->connection->nextMap();
            $this->eXpChatSendServerMessage(
                $this->msg_queueNow,
                null,
                array(
                    Formatting::stripCodes($map->name, 'wosnm'),
                    $map->author,
                    Formatting::stripCodes($player->nickName, 'wosnm'),
                    $login
                )
            );
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
            $msg = eXpGetMessage(
                '#admin_action#Admin #variable#%1$s #admin_action#removed '
                .'the map #variable#%3$s #admin_action# from the playlist'
            );
            $this->eXpChatSendServerMessage(
                $msg,
                null,
                array(
                    Formatting::stripCodes($player->nickName, 'wosnm'),
                    null,
                    Formatting::stripCodes($map->name, 'wosnm'),
                    $map->author
                )
            );
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
                    $msg = eXpGetMessage(
                        '#admin_action#Admin #variable#%1$s #admin_action#removed '
                        .'the map #variable#%3$s #admin_action# from playlist!'
                    );
                } else {
                    $msg = eXpGetMessage(
                        '#admin_error#Map #variable#%3$s #admin_error# '
                        .'not found at playlist, perhaps it was already removed ?'
                    );
                    $recievers = $login;
                }
                $this->eXpChatSendServerMessage(
                    $msg,
                    $recievers,
                    array(
                        Formatting::stripCodes($player->nickName, 'wosnm'),
                        null,
                        Formatting::stripCodes($map->name, 'wosnm'),
                        $map->author
                    )
                );

                return;
            } else {

                try {
                    unlink(Helper::getPaths()->getDefaultMapPath() . $map->fileName);
                    if ($found) {
                        $additions = "playlist and disk!";
                    } else {
                        $additions = "disk!";
                    }
                } catch (\Exception $ex) {
                    if ($found) {
                        $additions = "playlist";
                    }
                }
                if ($additions != "") {
                    $msg = eXpGetMessage(
                        '#admin_action#Admin #variable#%1$s #admin_action#erased '
                        .'the map #variable#%3$s by %4$s #admin_action# from %5$s'
                    );
                    $this->eXpChatSendServerMessage(
                        $msg,
                        $recievers,
                        array(
                            Formatting::stripCodes($player->nickName, 'wosnm'),
                            null,
                            Formatting::stripCodes($map->name, 'wosnm'),
                            $map->author,
                            $additions
                        )
                    );
                } else {
                    $msg = eXpGetMessage(
                        '#admin_error#Nothing to do, the map has been removed already from playlist and from disk!'
                    );
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
        if ($this->config->showNextMapWidget) {
            foreach (NextMapWidget::getAll() as $widget) {
                $widget->setMap($this->nextMap);
                $widget->redraw($widget->getRecipient());
            }
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
    public function chat_removeMap($login, $params)
    {
        if (is_numeric($params[0])) {
            if (is_object($this->storage->maps[$params[0]])) {
                $this->removeMap($login, $this->storage->maps[$params[0]]);
            }

            return;
        }

        if ($params[0] == "this") {
            $this->removeMap($login, $this->storage->currentMap);

            return;
        }
    }

    /**
     * Chat command to erease a map
     *
     * @param $login
     * @param $params
     */
    public function chat_eraseMap($login, $params)
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
                $this->eXpChatSendServerMessage(
                    $this->msg_nextQueue,
                    $login,
                    array(
                        Formatting::stripCodes($queue->map->name, 'wosnm'),
                        $queue->map->author,
                        Formatting::stripCodes($queue->player->nickName, 'wosnm'),
                        $queue->player->login
                    )
                );
            } else {
                $this->eXpChatSendServerMessage(
                    $this->msg_nextMap,
                    $login,
                    array(
                        Formatting::stripCodes($this->storage->nextMap->name, 'wosnm'),
                        $this->storage->nextMap->author
                    )
                );
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
                $this->eXpChatSendServerMessage(
                    $msg,
                    null,
                    array(
                        Formatting::stripCodes($this->storage->getPlayerObject($login)->nickName, 'wosnm'),
                        Formatting::stripCodes($queue->map->name, 'wosnm')
                    )
                );
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
            $this->redrawNextMapWidget();
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
                    $this->eXpChatSendServerMessage(
                        $msg,
                        null,
                        array(
                            Formatting::stripCodes($queue->player->nickName, 'wosnm'),
                            Formatting::stripCodes($queue->map->name, 'wosnm')
                        )
                    );
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
            $this->redrawNextMapWidget();
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
            $this->redrawNextMapWidget();
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
        $this->instantReplay = true;
        foreach (NextMapWidget::getAll() as $widget) {
            $widget->setMap($this->storage->currentMap);
            $widget->redraw($widget->getRecipient());
        }
		$this->isRestartMap = true;
        $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
    }

    /**
     * Restart the current map
     *
     * @param $login
     */
    public function replayScoreReset($login)
    {
        $this->instantReplay = true;
		$this->isRestartMap = true;
        foreach (NextMapWidget::getAll() as $widget) {
            $widget->setMap($this->storage->currentMap);
            $widget->redraw($widget->getRecipient());
        }
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
                $this->eXpChatSendServerMessage(
                    $msg,
                    $login,
                    array(Formatting::stripCodes($player->nickName, 'wosnm'), $login)
                );
                return;
            }
        }
		
		$this->isRestartMap = true;
        if (!$this->atPodium) {
            array_unshift($this->queue, new MapWish($player, $this->storage->currentMap, false));
        } else {
            $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
        }

        $msg = eXpGetMessage('#queue#Challenge set to be replayed!');
        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->nickName, 'wosnm'), $login));

        if ($this->config->showNextMapWidget && !$this->atPodium) {
            $this->nextMap = $this->storage->currentMap;
            $this->redrawNextMapWidget();
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
                $this->eXpChatSendServerMessage(
                    $msg,
                    $login,
                    array(Formatting::stripCodes($player->nickName, 'wosnm'), $login)
                );

                return;
            }
        }

        if (isset($this->history[1])) {
            $map = $this->history[1];
            array_unshift($this->queue, new MapWish($player, $map, false));

            $msg = eXpGetMessage(
                '#admin_action#Admin #variable#%1$s #admin_action#added previous '
                .'map #variable#%3$s #admin_action# to the playlist'
            );
            $this->eXpChatSendServerMessage(
                $msg,
                null,
                array(
                    Formatting::stripCodes($player->nickName, 'wosnm'),
                    null,
                    Formatting::stripCodes($map->name, 'wosnm'),
                    $map->author,
                )
            );

        } else {
            $msg = eXpGetMessage('#admin_error# $iThere are no previously played challenge!');
            $this->eXpChatSendServerMessage(
                $msg,
                $login,
                array(Formatting::stripCodes($player->nickName, 'wosnm'), $login)
            );
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
            $this->eXpChatSendServerMessage(
                eXpGetMessage(
                    "#admin_error#Can't continue, since this instance of eXpansion is running remote agains the server"
                ),
                $login
            );

            return;
        }
        $window = Gui\Windows\AddMaps::Create($login);
        $window->setTitle('Add Maps on server');
        $window->centerOnScreen();
        $window->setSize(130, 100);
        $window->show();
    }

    /**
     * Chat command to add map from Mania exchange
     *
     * @param $login
     * @param $params
     */
    public function addMxMap($login, $params)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage(AdminGroups::getNoPermissionMsg(), $login);

            return;
        }

        foreach ($params as $param) {

            if (is_numeric($param) && $param >= 0) {

                $trkid = ltrim($param, '0');
                $remotefile = 'http://tm.mania-exchange.com/tracks/download/' . $trkid;
                $file = file_get_contents($remotefile);

                if ($file === false || $file == -1) {
                    $this->eXpChatSendServerMessage($this->msg_errDwld, $login);
                } else {
                    if (strlen($file) >= 1024 * 1024) {
                        $this->eXpChatSendServerMessage($this->msg_errToLarge, $login);

                        return;
                    }
                    $game = $this->connection->getVersion();
                    $path = Helper::getPaths()->getDownloadMapsPath() . $game->titleId . "/" . $trkid . ".Map.Gbx";

                    if (!$lfile = @fopen($path, 'wb')) {
                        $this->eXpChatSendServerMessage(
                            '#admin_error#Error creating file. Please contact admin.',
                            $login
                        );
                    }
                    if (!fwrite($lfile, $file)) {
                        $this->eXpChatSendServerMessage(
                            '#admin_error#Error saving file - unable to write data. Please contact admin.',
                            $login
                        );
                        fclose($lfile);

                        return;
                    }
                    fclose($lfile);

                    try {
                        $this->connection->addMap($path);
                        $mapinfo = $this->connection->getMapInfo($path);
                        $this->eXpChatSendServerMessage(
                            $this->msg_mapAdd,
                            null,
                            array($mapinfo->name, $this->storage->getPlayerObject($login)->nickName)
                        );
                    } catch (Exception $e) {
                        $this->connection->chatSendServerMessage(__('Error:', $e->getMessage()));
                    }
                }
            } else {
                $this->eXpChatSendServerMessage($this->msg_errMxId, $login);
            }
        }
    }

    public function showMapInfo($login, $uid = null)
    {
        if ($uid == null) {
            $uid = $this->storage->currentMap->uId;
        }
        $window = Gui\Windows\MapInfo::create($login);
        $window->setMap($uid);
        $window->setTitle("Map Info", $this->storage->currentMap->name);
        $window->setSize(160, 90);
        $window->show($login);
    }

    public function onMapRestart()
    {
        $this->wasWarmup = true;
    }

    public function onMapSkip()
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro) {
            $this->onEndMatch(array(),array(), true);
        }
    }

    public function eXpOnUnload()
    {
        CurrentMapWidget::EraseAll();
        NextMapWidget::EraseAll();
        Maplist::EraseAll();
        AddMaps::EraseAll();
        Jukelist::EraseAll();
        Gui\Windows\MapInfo::EraseAll();
        CustomUI::ShowForAll(CustomUI::CHALLENGE_INFO);

        AdminGroups::removeAdminCommand($this->cmd_replay);
        AdminGroups::removeAdminCommand($this->cmd_erease);
        AdminGroups::removeAdminCommand($this->cmd_remove);

        /** @var ActionHandler $action */
        $action = \ManiaLive\Gui\ActionHandler::getInstance();
        $action->deleteAction($this->actionShowJukeList);
        $action->deleteAction($this->actionShowMapList);
    }
}
