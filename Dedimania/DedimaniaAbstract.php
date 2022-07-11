<?php

namespace ManiaLivePlugins\eXpansion\Dedimania;

use ManiaLive\Application\ErrorHandling;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities\Time;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\Dedimania\Classes\Connection as DediConnection;
use ManiaLivePlugins\eXpansion\Dedimania\Events\Event as DediEvent;
use ManiaLivePlugins\eXpansion\Dedimania\Structures\DediPlayer;
use ManiaLivePlugins\eXpansion\Dedimania\Structures\DediRecord;

/**
 * Description of DedimaniaAbstract
 *
 * @author De Cramer Oliver
 */
abstract class DedimaniaAbstract extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin implements \ManiaLivePlugins\eXpansion\Dedimania\Events\Listener
{
    const DEBUG_NONE = 0; //00000
    const DEBUG_MAX_RANKS = 1; //00001

    public $debug = self::DEBUG_MAX_RANKS;

    protected $endMapTriggered = false;

    /** @var DediConnection */
    protected $dedimania;
    protected $running = false;

    /** @var Config */
    protected $config;

    /** @var Structures\DediRecord[] $records */
    protected $records = array();

    /** @var array */
    protected $rankings = array();

    /** @var string */
    protected $vReplay = "";

    /** @var string */
    protected $gReplay = "";

    /** @var Structures\DediRecord */
    protected $lastRecord;

    /* @var integer $recordCount */
    protected $recordCount = 30;

    protected $msg_norecord;
    protected $msg_welcome;
    protected $msg_premium;
    protected $msg_regular;
    protected $msg_new;
    protected $msg_secure;
    protected $msg_improved;

    public static $actionOpenRecs = -1;
    public static $actionOpenCps = -1;
    public static $actionOpenSecCps = -1;

    public function eXpOnInit()
    {
        $this->setPublicMethod("isRunning");
        $this->config = Config::getInstance();
    }

    public function eXpOnLoad()
    {
        $helpText = "\n\nPlease correct your config with these instructions: \nEdit and add following configuration "
            . "lines to manialive config.ini\n\n ManiaLivePlugins\\eXpansion\\Dedimania_Script\\Config."
            . "login = 'your_server_login_here' \n "
            . "ManiaLivePlugins\\eXpansion\\Dedimania_Script\\Config.code = 'your_server_code_here' \n\n "
            . "Visit http://dedimania.net/tm2stats/?do=register to get code for your server.";
        if (empty($this->config->login)) {
            $this->console("Server login is not configured for dedimania plugin!");
            $this->running = false;
        }
        if (empty($this->config->code)) {
            $this->console("Server code is not configured for dedimania plugin!");
            $this->running = false;
        }
        Dispatcher::register(DediEvent::getClass(), $this);
        $this->dedimania = DediConnection::getInstance();
        $this->msg_welcome = eXpGetMessage('#variable#Dedimania $0f0Connected! #variable#Top #rank#%1$s #variable#records enabled for you (%2$s #variable#account)');
        $this->msg_premium = eXpGetMessage('$FC3p$FD2r$FE1e$FF0m$FF0i$FE2u$FC3m');
        $this->msg_regular = eXpGetMessage('regular');
        $this->msg_norecord = eXpGetMessage('#dedirecord#No dedimania records found for the map!');
        $this->msg_new = eXpGetMessage('%1$s #dedirecord#new #rank#%2$s.#dedirecord# Dedimania Record! #time#%3$s');
        $this->msg_improved = eXpGetMessage('%1$s #dedirecord#improves #rank#%2$s.#dedirecord# Dedimania Record! #time#%3$s #dedirecord#(#rank#%4$s #time#-%5$s#dedirecord#)');
        $this->msg_secure = eXpGetMessage('%1$s #dedirecord#secures #rank#%2$s.#dedirecord# Dedimania Record! #time#%3$s #dedirecord#(#rank#%4$s #time#-%5$s#dedirecord#)');
    }

    public function eXpOnReady()
    {
        parent::eXpOnReady();
        $this->enableDedicatedEvents();
        $this->enableApplicationEvents();
        $this->enableStorageEvents();

        \ManiaLive\Event\Dispatcher::register(\ManiaLivePlugins\eXpansion\Core\Events\ScriptmodeEvent::getClass(), $this);

        $this->tryConnection();
        // $this->previewDediMessages();
    }

    public function previewDediMessages()
    {
        $this->eXpChatSendServerMessage($this->msg_record, null, array(\ManiaLib\Utils\Formatting::stripCodes('test', 'wosnm'), rand(1, 100), Time::fromTM(rand(10000, 100000)), rand(1, 100), Time::fromTM(rand(10000, 100000))));

        $this->eXpChatSendServerMessage($this->msg_newRecord, null, array(\ManiaLib\Utils\Formatting::stripCodes('test', 'wosnm'), rand(1, 100), Time::fromTM(rand(10000, 100000))));
    }

    private $settingsChanged = array();

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->settingsChanged[$var->getName()] = true;
        if ($this->settingsChanged['login'] && $this->settingsChanged['code']) {
            $this->tryConnection();
            $this->settingsChanged = array();
        }
    }

    public function tryConnection()
    {
        if (!$this->running) {
            if (empty($this->config->login) || empty($this->config->code)) {
                $admins = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
                $admins->announceToPermission(Permission::EXPANSION_PLUGIN_SETTINGS, "#admin_error#Server login or/and Server code is empty in Dedimania Configuration");
                $this->console("\$f00Server code or/and login is not configured for dedimania plugin!");
            } else {
                try {
                    $this->dedimania->openSession($this->expStorage->version->titleId, $this->config);
                    $this->registerChatCommand("dedirecs", "showRecs", 0, true);
                    $this->registerChatCommand("dedicps", "showCps", 0, true);
                    $this->registerChatCommand("dediseccps", "showSecCps", 0, true);
                    $this->registerChatCommand("dedicps", "showCpDiff", 1, true);
                    $cmd = AdminGroups::addAdminCommand("savededi", $this, "force_dedisave", Permission::GAME_SETTINGS);
                    $cmd->setHelp("Force dedimania to send dedi records");
                    $this->setPublicMethod("showRecs");
                    $this->setPublicMethod("showCps");
                    $this->setPublicMethod("showSecCps");
                    $this->setPublicMethod("getRecords");

                    $this->running = true;
                    $admins = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
                    $admins->announceToPermission('expansion_settings', "#admin_action#Dedimania connection successfull.");

                    self::$actionOpenRecs = \ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, "showRecs"));
                    self::$actionOpenCps = \ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, "showCps"));
                    self::$actionOpenSecCps = \ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, "showSecCps"));
                } catch (\Exception $ex) {
                    $admins = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
                    $admins->announceToPermission('expansion_settings', "#admin_error#Server login or/and Server code is wrong in Dedimania Configuration");
                    $admins->announceToPermission('expansion_settings', "#admin_error#" . $ex->getMessage());
                    $this->console("\$f00Server code or/and login is wrong for the dedimania plugin!");
                }
            }
        }
    }

    public function checkSession($login)
    {
        $this->dedimania->checkSession();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (!$this->running) {
            return;
        }
        $player = $this->storage->getPlayerObject($login);
        $this->dedimania->playerConnect($player, $isSpectator);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (!$this->running) {
            return;
        }
        $this->dedimania->playerDisconnect($login);
    }

    public function BeginMap()
    {
        $this->endMapTriggered = true;
        $this->records = array();
        if ($this->storage->currentMap->nbCheckpoints <= 1) {
            return;
        }
        $this->dedimania->getChallengeRecords();
    }

    public function onBeginMatch()
    {
        if (!$this->endMapTriggered) {
            $this->records = array();
            if ($this->storage->currentMap->nbCheckpoints <= 1) {
                return;
            }
            $this->dedimania->getChallengeRecords();
        }
    }

    public function EndMatch()
    {
        $this->endMapTriggered = false;
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        $this->endMapTriggered = true;
    }

    public function onBeginRound()
    {
        if (!$this->running) {
            return;
        }
    }

    /**
     * rearrages the records list + recreates the indecies
     *
     * @param string $login is passed to check the server,map and player maxranks for new driven record
     */
    public function reArrage($login)
    {
        // sort by time
        $this->sortAsc($this->records, "time");

        $maxrank = DediConnection::$serverMaxRank;
        if (DediConnection::$players[$login]->maxRank > $maxrank) {
            $maxrank = DediConnection::$players[$login]->maxRank;
        }

        $this->debugMaxRanks('Server Max Rank is : ' . DediConnection::$serverMaxRank);
        $this->debugMaxRanks('Checking with      : ' . $maxrank);

        $i = 0;
        $newrecords = array();
        foreach ($this->records as $record) {
            $i++;
            if (array_key_exists($record->login, $newrecords)) {
                continue;
            }

            $record->place = $i;
            // if record holder is at server, then we must check for additional
            if ($record->login == $login) {
                // if record is greater than players max rank, don't allow
                if ($record->place > $maxrank) {
                    $this->debugMaxRanks("record place: " . $record->place . " is greater than max rank: " . $maxrank);
                    $this->debugMaxRanks("not adding record.");
                    continue;
                }


                // update checkpoints for the record
                $playerinfo = \ManiaLivePlugins\eXpansion\Core\Core::$playerInfo;

                $record->checkpoints = implode(",", $playerinfo[$login]->checkpoints);

                // add record
                $newrecords[$record->login] = $record;
                // othervice
            } else {
                // check if some record needs to be erased from the list...
                //  if ($record->place > DediConnection::$dediMap->mapMaxRank)
                //  continue;

                $newrecords[$record->login] = $record;
            }
        }

        // assign  the new records
        //$this->records = array_slice($newrecords, 0, DediConnection::$dediMap->mapMaxRank);

        $this->records = $newrecords;
        // assign the last place
        $this->lastRecord = end($this->records);

        // recreate new records entry for update_records
        $data = array('Records' => array());
        $i = 1;
        foreach ($this->records as $record) {
            $data['Records'][] = array(
                "Login" => $record->login, "MaxRank" => $record->maxRank, "NickName" => $record->nickname,
                "Best" => $record->time, "Rank" => $i, "Checks" => $record->checkpoints
            );
            $i++;
        }

        \ManiaLive\Event\Dispatcher::dispatch(new DediEvent(DediEvent::ON_UPDATE_RECORDS, $data));
    }

    public function compare_bestTime($a, $b)
    {
        if ($a['BestTime'] == $b['BestTime']) {
            return 0;
        }

        return ($a['BestTime'] < $b['BestTime']) ? -1 : 1;
    }

    private function sortAsc(&$array, $prop)
    {
        usort($array, function ($a, $b) use ($prop) {
            return $a->$prop > $b->$prop ? 1 : -1;
        });
    }

    public function onDedimaniaOpenSession()
    {
        $players = array();
        foreach ($this->storage->players as $player) {
            if ($player->login != $this->storage->serverLogin) {
                $players[] = array($player, false);
            }
        }
        foreach ($this->storage->spectators as $player) {
            $players[] = array($player, true);
        }

        $this->dedimania->playerMultiConnect($players);

        if ($this->storage->currentMap->nbCheckpoints > 1) {
            $this->dedimania->getChallengeRecords();
        }

        $this->rankings = array();
    }

    public function onDedimaniaGetRecords($data)
    {

        $this->records = array();

        foreach ($data['Records'] as $record) {
            $this->records[$record['Login']] = new DediRecord(
                $record['Login'],
                $record['NickName'],
                $record['MaxRank'],
                $record['Best'],
                $record['Rank'],
                $record['Checks']
            );
        }
        $this->lastRecord = end($this->records);
        $this->recordCount = count($this->records);

        $this->debug("Dedimania get records:");
    }

    public function eXpOnUnload()
    {
        $this->disableTickerEvent();
        $this->disableDedicatedEvents();
        \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\Records::EraseAll();
        \ManiaLive\Gui\ActionHandler::getInstance()->deleteAction(self::$actionOpenCps);
        \ManiaLive\Gui\ActionHandler::getInstance()->deleteAction(self::$actionOpenSecCps);
        \ManiaLive\Gui\ActionHandler::getInstance()->deleteAction(self::$actionOpenRecs);
        self::$actionOpenRecs = -1;
        self::$actionOpenCps = -1;
        self::$actionOpenSecCps = -1;


        Dispatcher::unregister(DediEvent::getClass(), $this);
    }

    /**
     *
     * @param type $data
     */
    public function onDedimaniaUpdateRecords($data)
    {
        $this->debug("Dedimania update records:");
    }

    /**
     * onDedimaniaNewRecord($record)
     * gets called on when player has driven a new record for the map
     *
     * @param Structures\DediRecord $record
     */
    public function onDedimaniaNewRecord($record)
    {
        try {
            if ($this->config->disableMessages == true) {
                return;
            }

            $recepient = $record->login;
            if ($this->config->show_record_msg_to_all) {
                $recepient = null;
            }

            $time = \ManiaLive\Utilities\Time::fromTM($record->time);
            if (substr($time, 0, 3) === "0:0") {
                $time = substr($time, 3);
            } else {
                if (substr($time, 0, 2) === "0:") {
                    $time = substr($time, 2);
                }
            }
            $noRedirect = false;
            if ($record->place <= Config::getInstance()->noRedirectTreshold) {
                $noRedirect = true;
            }
            $this->eXpChatSendServerMessage($this->msg_new,$recepient,array(\ManiaLib\Utils\Formatting::stripCodes($record->nickname, "wos"), $record->place, $time),$noRedirect);
        } catch (\Exception $e) {
            $this->console("Error: couldn't show dedimania message" . $e->getMessage());
        }
    }

    /**
     *
     * @param Structures\DediRecord $record
     * @param Structures\DediRecord $oldRecord
         */
    public function onDedimaniaRecord($record, $oldRecord)
    {
        $this->debug("improved dedirecord:");
        $this->debug($record);
        try {
            if ($this->config->disableMessages == true) {
                return;
            }
            $recepient = $record->login;
            if ($this->config->show_record_msg_to_all) {
                $recepient = null;
            }

            $diff = \ManiaLive\Utilities\Time::fromTM($record->time - $oldRecord->time);
            if (substr($diff, 0, 3) === "0:0") {
                $diff = substr($diff, 3);
            } else {
                if (substr($diff, 0, 2) === "0:") {
                    $diff = substr($diff, 2);
                }
            }
            $time = \ManiaLive\Utilities\Time::fromTM($record->time);
            if (substr($time, 0, 3) === "0:0") {
                $time = substr($time, 3);
            } else {
                if (substr($time, 0, 2) === "0:") {
                    $time = substr($time, 2);
                }
            }

            $noRedirect = false;
            if ($record->place <= Config::getInstance()->noRedirectTreshold) {
                $noRedirect = true;
            }

            if ($oldRecord->place != $record->place) {
                $this->eXpChatSendServerMessage($this->msg_improved,$recepient,array(\ManiaLib\Utils\Formatting::stripCodes($record->nickname, "wos"),$record->place,$time,$oldRecord->place, $diff),$noRedirect);
            } else {
                $this->eXpChatSendServerMessage($this->msg_secure,$recepient,array(\ManiaLib\Utils\Formatting::stripCodes($record->nickname, "wos"),$record->place,$time,$oldRecord->place, $diff),$noRedirect);
            }
            $this->debug("message sent.");
        } catch (\Exception $e) {
            $this->console("Error: couldn't show dedimania message");
        }
    }

    /**
     * @param DediPlayer $playerData
     */
    public function onDedimaniaPlayerConnect($playerData)
    {
        if ($playerData->banned && !$this->config->allowBannedPlayersToJoin) {
            $this->connection->kick($playerData->login, "Can't join server, you're banned from dedimania!");
        }

        $out = $this->msg_regular;
        if ($playerData->maxRank > 15) {
            $out = $this->msg_premium;
        }
        $this->eXpChatSendServerMessage($this->msg_welcome, $playerData->login, array($playerData->maxRank, $out), true);
    }

    public function onDedimaniaPlayerDisconnect($login)
    {

    }

    public function getRecords()
    {
        return $this->records;
    }

    public function showRecs($login)
    {
        \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\Records::Erase($login);

        if (sizeof($this->records) == 0) {
            $this->eXpChatSendServerMessage($this->msg_norecord, $login);
            return;
        }
        try {
            $window = \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\Records::Create($login);
            $window->setTitle(__('Dedimania -records for', $login), $this->storage->currentMap->name);
            $window->centerOnScreen();
            $window->populateList($this->records);
            $url = "http://dedimania.net/tm2stats/?do=stat&Envir=" . $this->storage->currentMap->environnement . "&RecOrder3=REC-ASC&UId=" . $this->storage->currentMap->uId . "&Show=RECORDS";
            $window->setDediUrl($url);

            $window->setSize(120, 100);
            $window->show();
        } catch (\Exception $e) {
            ErrorHandling::displayAndLogError($e);
        }
    }

    public function showCps($login)
    {
        \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\RecordCps::Erase($login);

        if (sizeof($this->records) == 0) {
            $this->eXpChatSendServerMessage($this->msg_norecord, $login);
            return;
        }
        try {
            $window = \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\RecordCps::Create($login);
            $window->setTitle(__('Dedimania cps for ', $login), $this->storage->currentMap->name);
            $window->centerOnScreen();
            $window->populateList($this->records);
            $window->setSize(170, 110);
            $window->show();
        } catch (\Exception $e) {
            ErrorHandling::displayAndLogError($e);
        }
    }

    public function showSecCps($login)
    {
        \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\RecordSecCps::Erase($login);

        if (sizeof($this->records) == 0) {
            $this->eXpChatSendServerMessage($this->msg_norecord, $login);
            return;
        }
        try {
            $window = \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\RecordSecCps::Create($login);
            $window->setTitle(__('Dedimania sectors for ', $login), $this->storage->currentMap->name);
            $window->centerOnScreen();
            $window->populateList($this->records);
            $window->setSize(170, 110);
            $window->show();
        } catch (\Exception $e) {
            ErrorHandling::displayAndLogError($e);
        }
    }

    public function showCpDiff($login, $params)
    {
        if (sizeof($this->records) == 0) {
            $this->eXpChatSendServerMessage($this->msg_norecord, $login);
            return;
        }
        \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\CpDiff::Erase($login);

        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            $localrecs = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
        }
        $player_localrec = ArrayOfObj::getObjbyPropValue($localrecs, "login", $login);
        if ($player_localrec) {
            if ($this->records[$login]) {
                if ($this->records[$login]->time > $player_localrec->time) {
                    $player = $player_localrec;
                } else {
                    $player = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
                    $player->place = $this->records[$login]->place;
                    $player->nickName = $this->records[$login]->nickname;
                    $player->ScoreCheckpoints = explode(",", $this->records[$login]->checkpoints);
                }
            } else {
                $player = $player_localrec;
            }
        } else {
            $player = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
            $player->place = $this->records[$login]->place;
            $player->nickName = $this->records[$login]->nickname;
            $player->ScoreCheckpoints = explode(",", $this->records[$login]->checkpoints);
        }

        $target_data = ArrayOfObj::getObjbyPropValue($this->records, "place", $params);
        $target = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
        $target->place = $target_data->place;
        $target->nickName = $target_data->nickname;
        $target->ScoreCheckpoints = explode(",", $target_data->checkpoints);

        $window = \ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows\CpDiff::Create($login);
        $window->setTitle(__('Dedimania CheckPoints Difference', $login));
        $window->populateList(array($player, $target));
        $window->setSize(200, 100);
        $window->centerOnScreen();
        $window->show();
    }

    public function force_dedisave($fromLogin, $params)
    {
        if ($this->storage->currentMap->nbCheckpoints <= 1) {
            $this->eXpChatSendServerMessage("#admin_error#Map not supported (need at least 1 CP), dedimania records not sent !", $fromLogin);
            return;
        }

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();

        if ($gamemode == GameInfos::GAMEMODE_TEAM) {
            return;
        }

        $rankings = array();
        foreach(Core::$rankings as $rank) {

            if (isset($rank->best_lap_checkpoints) && $rank->bestTime > 0) {
                if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_CUP) {
                    $rankings[$rank->login] = array('Login' => $rank->login, 'BestTime' => $rank->bestTime, 'BestCheckpoints' => implode(",", $rank->best_race_checkpoints));
                } else {
                    $rankings[$rank->login] = array('Login' => $rank->login, 'BestTime' => $rank->bestTime, 'BestCheckpoints' => implode(",", $rank->best_race_checkpoints));
                }
            }

            elseif (isset($rank->bestCheckpoints) && $rank->bestTime > 0) {
                if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_CUP) {
                    $rankings[$rank->login] = array('Login' => $rank->login, 'BestTime' => $rank->bestTime, 'BestCheckpoints' => implode(",", $rank->bestCheckpoints));
                } else {
                    $rankings[$rank->login] = array('Login' => $rank->login, 'BestTime' => $rank->bestTime, 'BestCheckpoints' => implode(",", $rank->bestCheckpoints));
                }
            }
        }

        if (count($rankings) < 1) {
            $this->eXpChatSendServerMessage("#admin_error#No score found, dedi not sent !", $fromLogin);
            return;
        }
        usort($rankings, array($this, "compare_BestTime"));

        $this->rankings = $rankings;

        $firstPlayerInfos = ArrayOfObj::getObjbyPropValue(Core::$rankings, "login", $rankings[0]['Login']);

        if (isset($firstPlayerInfos->best_race_checkpoints)) {
            $this->getVReplay($rankings[0]['Login'], $firstPlayerInfos->best_race_checkpoints);
        } else {
            $this->getVReplay($rankings[0]['Login'], array());
        }
        $this->getGReplay($rankings[0]['Login']);

        $this->sendScores();
        $this->EndMatch();
        $this->records = array();
        $this->dedimania->getChallengeRecords();

        $this->eXpChatSendServerMessage('$0c0Dedimania records sent', $fromLogin);
    }

    public function isRunning()
    {
        return $this->running;
    }

    protected function debugMaxRanks($debugMsg)
    {
        if (($this->debug & self::DEBUG_MAX_RANKS) == self::DEBUG_MAX_RANKS) {
            $this->debug('[Max Ranks]' . $debugMsg);
        }
    }
}
