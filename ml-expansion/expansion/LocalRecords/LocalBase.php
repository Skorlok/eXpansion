<?php

namespace ManiaLivePlugins\eXpansion\LocalRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Core\I18n\Message;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event;
use ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record;
use ManiaLivePlugins\eXpansion\Menu\Menu;

abstract class LocalBase extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    const SCORE_TYPE_TIME = 'time';

    const SCORE_TYPE_SCORE = 'score';

    /**
     * List of the records for the current track
     *
     * @var Record[] Array int => Record
     */
    protected $currentChallengeRecords = array();

    /** @var Window */
    protected $recordsWindow;

    /** @var Window */
    protected $ranksWindow;

    /** @var Window */
    protected $topSumsWindow;

    /** @var Window */
    protected $sectorWindow;

    /** @var Window */
    protected $cpsWindow;

    /** @var Window */
    protected $cpDiffWindow;

    /** @var Window Same template as $cpsWindow, filled with sector times instead */
    protected $secCpsWindow;

    protected $extendedClassName;

    /**
     * The best times and other statistics of the current players on the server
     *
     * @var Record[] Array[$login] = Record
     */
    protected $currentChallengePlayerRecords = array();

    protected $currentChallangeSectorTimes = array();

    protected $currentChallangeSectorsCps = array();

    /**
     * The current 100 best ranks in the server
     *
     * @var array int => login
     */
    protected $ranks = array();

    /**
     * The rank of players connected to the
     *
     * @var array login => int
     */
    protected $player_ranks = array();

    /**
     * Total amount of players that has a rank
     *
     * @var int
     */
    protected $total_ranks = -1;

    /**
     * Checking if we trued to get ranks before
     *
     * @var bool
     */
    protected $rank_needUpdated = false;

    /**
     * @var Config
     */
    protected $config;

    /**
     * All the messages need to be sent;
     *
     * @var Message
     */
    protected $msg_secure;
    protected $msg_new;
    protected $msg_improved;
    protected $msg_BeginMap;
    protected $msg_newMap;
    protected $msg_personalBest;
    protected $msg_noPB;
    protected $msg_showRank;
    protected $msg_noRank;
    protected $msg_secure_top1;
    protected $msg_secure_top5;
    protected $msg_new_top1;
    protected $msg_new_top5;
    protected $msg_improved_top1;
    protected $msg_improved_top5;
    protected $msg_equals;
    protected $msg_equals_top5;
    protected $msg_equals_top1;
    protected $msg_showRankAndAverage;
    protected $msg_nextRankAndAverage;
    protected $msg_no_nextRank;

    public static $txt_rank;
    public static $txt_nick;
    public static $txt_score;
    public static $txt_sector;
    public static $txt_cp;
    public static $txt_login;
    public static $txt_avgScore;
    public static $txt_nbFinish;
    public static $txt_wins;
    public static $txt_lastRec;
    public static $txt_ptime;
    public static $txt_nbRecords;

    public static $openRecordsAction = -1;

    abstract protected function getScoreType();

    abstract public function formatScore($score);

    abstract protected function isBetterTime($newTime, $oldTime);

    abstract protected function secureBy($newTime, $oldTime);

    abstract protected function getDbOrderCriteria();

    abstract public function getNbOfLaps();

    public function eXpOnInit()
    {
        $classParts = explode('\\', get_class($this));
        $this->extendedClassName = end($classParts);

        LocalBase::$openRecordsAction = 'exp:eXpansion.' . $this->extendedClassName . ':showRecsWindow';

        $this->config = Config::getInstance();

        $this->setPublicMethod("getCurrentChallangePlayerRecord");
        $this->setPublicMethod("getPlayersRecordsForAllMaps");
        $this->setPublicMethod("getRecords");
        $this->setPublicMethod("getRanks");
        $this->setPublicMethod("getPlayerRank");
        $this->setPublicMethod("getTotalRanked");
        $this->setPublicMethod("showRecsWindow");
        $this->setPublicMethod("showRanksWindow");
        $this->setPublicMethod("showCpWindow");
        $this->setPublicMethod("showSecCpWindow");
        $this->setPublicMethod("showTopSums");

        //The Database plugin is needed.
        $this->addDependency(new \ManiaLive\PluginHandler\Dependency("\\ManiaLivePlugins\\eXpansion\\Database\\Database"));
    }

    public function eXpOnLoad()
    {
        //Recovering the multi language messages

        // %1$s - nickname; %2$s - rank; %3$s - time; %4$s - old rank; %5$s - time difference
        $this->msg_secure = eXpGetMessage('#variable#%1$s #record#secures #rank#%2$s. #record#Local Record!#time#%3$s #record#(#rank#%4$s #time#-%5$s#record#)');
        // %1$s - nickname; %2$s - rank; %3$s - time
        $this->msg_new = eXpGetMessage('#variable#%1$s #record#new #rank#%2$s.#record# Local Record! #time#%3$s');
        // %1$s - nickname; %2$s - rank; %3$s - time
        $this->msg_equals = eXpGetMessage('#record#Oops! #variable#%1$s #record#equals #rank#%2$s#record#. Local Record! #time#%3$s');
        // %1$s - nickname; %2$s - rank; %3$s - time; %4$s - old rank; %5$s - time difference
        $this->msg_improved = eXpGetMessage('#variable#%1$s #record#improves #rank#%2$s. #record#Local Record! #time#%3$s #record#(#rank#%4$s #time#-%5$s#record#)');

        // %1$s - nickname; %2$s - rank; %3$s - time; %4$s - old rank; %5$s - time difference
        $this->msg_secure_top5 = eXpGetMessage('#variable#%1$s #record_top#secures #rank#%2$s.#record_top# Local Record! #time#%3$s #record_top#(#rank#%4$s #time#-%5$s#record_top#)');
        // %1$s - nickname; %2$s - rank; %3$s - time
        $this->msg_new_top5 = eXpGetMessage('#variable#%1$s #record_top#new #rank#%2$s.#record_top# Local Record! #time#%3$s');
        // %1$s - nickname; %2$s - rank; %3$s - time
        $this->msg_equals_top5 = eXpGetMessage('#record_top#Oops! #variable#%1$s #record_top#equals #rank#%2$s#record_top#. Local Record! #time#%3$s');
        // %1$s - nickname; %2$s - rank; %3$s - time; %4$s - old rank; %5$s - time difference
        $this->msg_improved_top5 = eXpGetMessage('#variable#%1$s #record_top#improves #rank#%2$s.#record_top# Local Record! #time#%3$s #record_top#(#rank#%4$s #time#-%5$s#record_top#)');


        // %1$s - nickname; %2$s - rank; %3$s - time; %4$s - old rank; %5$s - time difference
        $this->msg_secure_top1 = eXpGetMessage('$o$FF0Co$FE0ng$FD0rat$FC0ul$FB0ati$FA0on$F90s$fff %1$s$f90!$z$s$o$fff %2$s.$f90 Local Record! #time#%3$s $f90(#rank#%4$s #time#-%5$s$F90)');
        // %1$s - nickname; %2$s - rank; %3$s - time
        $this->msg_new_top1 = eXpGetMessage('$o$FF0Co$FE0ng$FD0rat$FC0ul$FB0ati$FA0on$F90s$fff %1$s$f90!$z$s$o$fff %2$s.$f90 Local Record! #time#%3$s');
        // %1$s - nickname; %2$s - rank; %3$s - time

        $this->msg_equals_top1 = eXpGetMessage('$o$0CFO$2DFo$3DFo$5EFo$6EFp$8FFs$9FF!$fff %1$s $z$s$9ff$oequals #rank#%2$s.$9ff$o Local Record! #time#%3$s');
        // %1$s - nickname; %2$s - rank; %3$s - time; %4$s - old rank; %5$s - time difference
        $this->msg_improved_top1 = eXpGetMessage('$z$o$FF0Co$FE0ng$FD0rat$FC0ul$FB0ati$FA0on$F90s$fff %1$s$F90!$z$s$o$fff %2$s.$f90 Local Record! #time#%3$s $f90(#rank#%4$s #time#-%5$s$F90)');

        // %1$s - map name,
        $this->msg_newMap = eXpGetMessage('#variable#%1$s  #record#is a new Map. Currently no record!');
        // %1$s - map name, %2$s - record, %3$s - nickname
        $this->msg_BeginMap = eXpGetMessage('#record#Current record on #variable#%1$s  #record#is #time#%2$s #record#by #variable#%3$s');
        // %1$s - pb, %2$s - place (if any), %3$s - average, %4$s - # of finishes
        $this->msg_personalBest = eXpGetMessage('#record#Personal Best: #time#%1$s  #record#($n #rank#%2$s$n #record#)  Average: #time#%3$s #record#($n #variable#%4$s #record#$n finishes $n)');
        $this->msg_noPB = eXpGetMessage('#admin_error# $iYou have not finished this map yet..');
        // %1$s - server rank, %2$s - total # of ranks
        $this->msg_showRank = eXpGetMessage('#record#Server rank: #rank#%1$s#record#/#rank#%2$s');
        // %1$s - server rank, %2$s - total # of ranks, %3 - average score
        $this->msg_showRankAndAverage = eXpGetMessage('#record#Server rank: #rank#%1$s#record#/#rank#%2$s #record#Average: $s#time#%3$s');
        $this->msg_noRank = eXpGetMessage('#admin_error#$iNot enough local records to obtain ranking yet..');
        // %1$s - nextplayerlogin, %2$s - server rank, %3$s - total # of ranks, %4 - average score, %5 - diff
        $this->msg_nextRankAndAverage = eXpGetMessage('#record#The next better ranked player is #variable#%1$s: #rank#%2$s#record#/#rank#%3$s #record#Average: $s#time#%4$s [$s#time#%5$s]');
        $this->msg_no_nextRank = eXpGetMessage('#record#You are already the best ranked player');

        self::$txt_rank = eXpGetMessage("#");
        self::$txt_nick = eXpGetMessage("NickName");
        self::$txt_score = eXpGetMessage("Score");
        self::$txt_sector = eXpGetMessage("Sector");
        self::$txt_cp = eXpGetMessage("CheckPoint Times");
        self::$txt_avgScore = eXpGetMessage("Average Score");
        self::$txt_nbFinish = eXpGetMessage("Finishes");
        self::$txt_wins = eXpGetMessage("Nb Wins");
        self::$txt_lastRec = eXpGetMessage("Last Rec Date");
        self::$txt_ptime = eXpGetMessage("Play Time");
        self::$txt_nbRecords = eXpGetMessage("nb Rec");
        self::$txt_login = eXpGetMessage("Login");

        $this->enableStorageEvents();
        $this->enableDedicatedEvents();
        $this->enableDatabase();

        //List of all records
        $cmd = $this->registerChatCommand("recs", "showRecsWindow", 0, true);
        $cmd->help = 'Show Records Window';

        $cmd = $this->registerChatCommand("topsums", "showTopSums", 0, true);
        $cmd->help = 'show Top Sums';

        //Top 100 ranked players
        $cmd = $this->registerChatCommand("top100", "showRanksWindow", 0, true);
        $cmd->help = 'Show Server Ranks Window';

        $cmd = $this->registerChatCommand("ranks", "showRanksWindow", 0, true);
        $cmd->help = 'Show Server Ranks Window';

        $cmd = $this->registerChatCommand("rank", "chat_showRank", 0, true);
        $cmd->help = 'Show Player Rank';

        $cmd = $this->registerChatCommand("nextrank", "chat_nextRank", 0, true);
        $cmd->help = 'Show the next better ranked player';

        $cmd = $this->registerChatCommand("pb", "chat_personalBest", 0, true);
        $cmd->help = 'Show Player Personal Best';

        $cmd = $this->registerChatCommand("cps", "showCpWindow", 0, true);
        $cmd->help = 'Show Checkpoint times';

        $cmd = $this->registerChatCommand("seccps", "showSecCpWindow", 0, true);
        $cmd->help = 'Show Sectors times';

        $cmd = $this->registerChatCommand("cps", "showCPDiffWindow", 1, true);
        $cmd->help = 'Show Checkpoint difference';

        $cmd = $this->registerChatCommand("localcps", "showCpDiffNoDediWindow", 1, true);
        $cmd->help = 'Show Checkpoint difference without dedimania';

        $cmd = $this->registerChatCommand("sectors", "showSectorWindow", 0, true);
        $cmd->help = 'Show Players Best Sector times';

        $cmd = AdminGroups::addAdminCommand("delrec", $this, "chat_delRecord", Permission::LOCAL_RECORDS_DELETE);
        $cmd->setHelp("Deletes all records by login");

        Menu::addMenuItem("LocalRecords",
            array("Records" => array(null, array(
                "Local" => array(null, 'exp:eXpansion.' . $this->extendedClassName . ':showRecsWindow'),
                "Hall of Fame" => array(null, 'exp:eXpansion.' . $this->extendedClassName . ':showTopSums'),
                "Server Ranks" => array(null, 'exp:eXpansion.' . $this->extendedClassName . ':showRanksWindow')
            )))
        );

        //$this->previewRecordMessages();
    }

    public function previewRecordMessages()
    {
        $messages = array($this->msg_improved_top1, $this->msg_improved_top5, $this->msg_improved, $this->msg_secure_top1, $this->msg_secure_top5, $this->msg_secure);

        $messages2 = array($this->msg_equals_top1, $this->msg_equals_top5, $this->msg_equals, $this->msg_new_top1, $this->msg_new_top5, $this->msg_new);

        foreach ($messages as $msg) {
            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes('test', 'wosnm'), rand(1, 100), Time::fromTM(rand(10000, 100000)), rand(1, 100), Time::fromTM(rand(10000, 100000))));
        }

        foreach ($messages2 as $msg) {
            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes("test", 'wosnm'), rand(1, 100), Time::fromTM(rand(10000, 100000))));
        }
    }

    public function eXpOnReady()
    {
        //Creating the records table
        if (!$this->db->tableExists("exp_records")) {
            $q = "CREATE TABLE `exp_records` (
                    `record_id` MEDIUMINT( 9 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `record_challengeuid` VARCHAR( 27 ) NOT NULL DEFAULT '0',
                    `record_playerlogin` VARCHAR( 30 ) NOT NULL DEFAULT '0',
                    `record_nbLaps` INT( 3 ) NOT NULL,
                    `record_score` MEDIUMINT( 9 ) DEFAULT '0',
                    `record_nbFinish` MEDIUMINT( 4 ) DEFAULT '0',
                    `record_avgScore` MEDIUMINT( 9 ) DEFAULT '0',
                    `record_checkpoints` TEXT,
                    `record_date` INT( 9 ) NOT NULL,
                    KEY(`record_challengeuid` ,  `record_playerlogin` ,  `record_nbLaps`)
                ) CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = MYISAM ;";
            $this->db->execute($q);
        }

        $database = $this->db->execute("DESCRIBE `exp_records`")->fetchArrayOfObject();

        // CHECK
        $recordDateFound = false;
        foreach ($database as $field) {
            if ($field->Field == "record_date") {
                if ($field->Type != "int(12)") {
                    $q = "ALTER TABLE `exp_records` CHANGE `record_date` `record_date` INT( 12 ) NOT NULL;";
                    $this->db->execute($q);
                }
            }

            if ($field->Field == "score_type") {
                $recordDateFound = true;
            }
        }

        if (!$recordDateFound) {
            $q = "ALTER TABLE `exp_records` ADD COLUMN score_type VARCHAR(10) DEFAULT 'time'";
            $this->db->execute($q);
        }


        if ($this->db->tableExists("exp_ranks")) {
            $q = 'DROP TABLE `exp_ranks`;';
            $this->db->execute($q);
        }

        $this->onBeginMap("", "", "");

        $this->getRanks();

        $this->registerManialinkCallback('showRecsWindow', false, true);
        $this->registerManialinkCallback('deleteRecord', false, true);
        $this->registerManialinkCallback('showCpWindow', false, true);
        $this->registerManialinkCallback('showSecCpWindow', false, true);
        $this->registerManialinkCallback('showSectorWindow');
        $this->registerManialinkCallback('showRanksWindow');
        $this->registerManialinkCallback('showTopSums');
        $this->registerManialinkCallback('delRec', false, true);
        $this->registerManialinkCallback('delRecs', false, true);

        $this->recordsWindow = new Window("LocalRecords\Gui\Windows\Records.xml");
        $this->recordsWindow->setName("Records");
        $this->recordsWindow->setSize(180, 100);
        $this->recordsWindow->setTitle('Records on a Map');

        $this->ranksWindow = new Window("LocalRecords\Gui\Windows\Ranks.xml");
        $this->ranksWindow->setName("Ranks");
        $this->ranksWindow->setSize(150, 100);
        $this->ranksWindow->setTitle('Server Ranks');

        $this->topSumsWindow = new Window("LocalRecords\Gui\Windows\TopSums.xml");
        $this->topSumsWindow->setName("TopSums");
        $this->topSumsWindow->setSize(100, 90);
        $this->topSumsWindow->setTitle("TopSums");

        $this->sectorWindow = new Window("LocalRecords\Gui\Windows\Sector.xml");
        $this->sectorWindow->setName("Sectors");
        $this->sectorWindow->setSize(160, 100);
        $this->sectorWindow->setTitle('Sector Times on Map');

        $this->cpsWindow = new Window("LocalRecords\Gui\Windows\Cps.xml");
        $this->cpsWindow->setName("Cps");
        $this->cpsWindow->setSize(170, 110);
        $this->cpsWindow->setTitle('CheckPoints on Map');

        $this->secCpsWindow = new Window("LocalRecords\Gui\Windows\Cps.xml");
        $this->secCpsWindow->setName("SecCps");
        $this->secCpsWindow->setSize(170, 110);
        $this->secCpsWindow->setTitle('Sectors on Map');

        $this->cpDiffWindow = new Window("LocalRecords\Gui\Windows\CpDiff.xml");
        $this->cpDiffWindow->setName("CpDiff");
        $this->cpDiffWindow->setSize(200, 100);
        $this->cpDiffWindow->setTitle('Local CheckPoints Difference');
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->config = Config::getInstance();
    }

    /**
     * getPlayersRecordsForAllMaps($login)
     *
     * @param string $login
     *
     * @return array $list -> $list[mapuid] = (int) position
     */
    public function getPlayersRecordsForAllMaps($login)
    {
        $count = 0;
        $uids = "";
        foreach ($this->storage->maps as $map) {
            if (!isset($map->localRecords)) {
                $map->localRecords = array();
            }
            if (!isset($map->localRecords[$login])) {
                $count++;

                $uids .= $this->db->quote($map->uId) . ",";
                $mapsByUid[$map->uId] = $map;
            }
        }

        if ($count > 0) {
            $uids = trim($uids, ",");

            $q = 'SELECT `rank`, `record_challengeuid` as `uid`, `record_score` as `score` FROM (SELECT *, IF(@prev <> exp_records.record_challengeuid, @rn:=-1,@rn), @prev:=exp_records.record_challengeuid, @rn:=@rn+1 AS `rank` FROM `exp_records`, (SELECT @rn:=0) rn, (SELECT @prev:=\'\') prev ORDER BY exp_records.record_challengeuid ASC, exp_records.record_score ASC) temp  WHERE `record_challengeuid` IN (' . $uids . ')	AND `record_playerlogin` = ' . $this->db->quote($login);
            $data = $this->db->execute($q);

            while ($row = $data->fetchObject()) {
                $mapsByUid[$row->uid]->localRecords[$login] = array($row->rank, $row->score);
            }
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        //We get all the records
        $this->updateCurrentChallengeRecords();

        //Sending begin map messages
        if (sizeof($this->currentChallengeRecords) == 0 && $this->config->sendBeginMapNotices) {
            $this->eXpChatSendServerMessage($this->msg_newMap, null, array(Formatting::stripCodes($this->storage->currentMap->name, 'wosnm')));
        } else {
            if ($this->config->sendBeginMapNotices) {
                $time = $this->formatScore($this->currentChallengeRecords[0]->time);

                $this->eXpChatSendServerMessage($this->msg_BeginMap, null, array(Formatting::stripCodes($this->storage->currentMap->name, 'wosnm'), $time, Formatting::stripCodes(Formatting::fixTags($this->currentChallengeRecords[0]->nickName), 'wosnm')));
                foreach ($this->storage->players as $login => $player) {
                    $this->chat_personalBest($login, null);
                }
                foreach ($this->storage->spectators as $login => $player) {
                    $this->chat_personalBest($login, null);
                }
            }
        }

        //send Ranking
        if ($this->config->sendRankingNotices) {
            foreach ($this->storage->players as $login => $player) {
                $this->chat_showRank($login);
            }

            foreach ($this->storage->spectators as $login => $player) {
                $this->chat_showRank($login);
            }
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        //Checking for lap constraints
        $nbLaps = $this->getNbOfLaps();

        $this->debug("Nb Laps : " . $nbLaps);

        //We update the database
        //First of the best records
        $currentMap = $this->storage->currentMap;
        foreach ($this->storage->maps as $map) {
            if ($map->uId == $this->storage->currentMap->uId) {
                $currentMap = $map;
                break;
            }
        }

        $currentMap->localRecords = array();
        foreach ($this->currentChallengeRecords as $i => $record) {
            $currentMap->localRecords[$record->login] = array($record->place - 1, $record->time);
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        // Added this to calulate the new ranks during every map change -reaby
        $this->rank_needUpdated = true;
        $this->getRanks();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $uid = $this->storage->currentMap->uId;
        //If the player doesn't have a record get best time and other...
        $this->getFromDbPlayerRecord($login, $uid);

        //Send a message telling him about records on this map
        if (sizeof($this->currentChallengeRecords) == 0 && $this->config->sendBeginMapNotices) {
            $this->eXpChatSendServerMessage($this->msg_newMap, $login, array(Formatting::stripCodes($this->storage->currentMap->name, 'wosnm')));
        } else {
            if ($this->config->sendBeginMapNotices) {
                $time = $this->formatScore($this->currentChallengeRecords[0]->time);

                $this->eXpChatSendServerMessage( $this->msg_BeginMap, $login, array(Formatting::stripCodes($this->storage->currentMap->name, 'wosnm'), $time, Formatting::stripCodes(Formatting::fixTags($this->currentChallengeRecords[0]->nickName), 'wosnm')));
            }
        }

        //Get rank of the player
        if ($this->config->sendRankingNotices) {
            $this->chat_showRank($login);
        }
    }

    private function checkRecordTreshold($place)
    {
        /** @var Config $config */
        $config = Config::getInstance();
        if ($place <= $config->noRedirectTreshold) {
            return true;
        }
        return false;
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (isset($this->player_ranks[$login]) && $this->player_ranks[$login] > 100) {
            //And rank data
            unset($this->player_ranks[$login]);
        }
    }

    /**
     * Will add a a record to the current map records buffer.
     * The record will only be save on endMap
     *
     * @param string $login the login of the player who did the time
     * @param int $score His score/time
     * @param array() $cpScore list of CheckPoint times
     */
    public function addRecord($login, $score, $cpScore)
    {
        $uid = $this->storage->currentMap->uId;
        $player = $this->storage->getPlayerObject($login);
        $force = false;
        $isNew = false;
        $this->currentChallangeSectorTimes = array();

        if (is_object($player) == false) {
            $this->console("Notice: Error while saving record for login '" . $login . "',couldn't fetch player object!");
            return;
        }

        //Player doesen't have record need to create one
        if (!isset($this->currentChallengePlayerRecords[$login])) {
            $record = new Record();
            $record->login = $login;
            $record->nickName = $player->nickName;
            $record->time = $score;
            $record->nbFinish = 1;
            $record->avgScore = $score;
            $record->nation = $player->path;
            $record->uId = $uid;
            $record->place = sizeof($this->currentChallengeRecords) + 1;
            $record->ScoreCheckpoints = $cpScore;
            $i = sizeof($this->currentChallengeRecords);
            if ($i > $this->config->recordsCount) {
                $i = $this->config->recordsCount;
            }
            $this->currentChallengeRecords[$i] = $record;
            $this->currentChallengePlayerRecords[$login] = $record;

            $force = true;
            $isNew = true;

            $this->debug("$login just did his firs time of $score on this map");
        } else {
            //We update the old records average time and nbFinish
            $this->currentChallengePlayerRecords[$login]->nbFinish++;
            $avgScore = (($this->currentChallengePlayerRecords[$login]->nbFinish - 1) * $this->currentChallengePlayerRecords[$login]->avgScore + $score) / $this->currentChallengePlayerRecords[$login]->nbFinish;
            $this->currentChallengePlayerRecords[$login]->avgScore = $avgScore;

            $this->debug("$login just did a new time of $score. His current rank is " . $this->currentChallengePlayerRecords[$login]->place);
        }

        $nrecord = $this->currentChallengePlayerRecords[$login];

        //Now we need to find it's rank
        if ($force || $this->isBetterTime($score, $nrecord->time)) {

            //Saving old rank and time
            $recordrank_old = $nrecord->place;
            $recordtime_old = $nrecord->time;

            //Updating time with new time/score
            $nrecord->time = $score;

            //Update the checkoints
            $nrecord->ScoreCheckpoints = $cpScore;
            //And the date on which the record was driven
            $nrecord->date = time();

            //Now we need to try and find a rank to the time
            $i = $recordrank_old - 2;

            //IF old rank was to bad to take in considaration. Let's try the worst record and see
            if ($i >= $this->config->recordsCount) {
                $i = $this->config->recordsCount - 1;
            }

            $this->debug("Starting to look for the rank of $login 's record at rank $i+1");

            $firstRecord = ($i < 0);

            //For each record worse then the new, push it back and push forward the new one
            while ($i >= 0 && !$this->isBetterTime($this->currentChallengeRecords[$i]->time, $nrecord->time)) {
                $record = $this->currentChallengeRecords[$i];

                $this->debug("$login is getting better : " . $nrecord->place . "=>" . ($nrecord->place - 1) . "And " . $record->login . " is getting worse" . $record->place . "=>" . ($record->place + 1));

                //New record takes old recs place
                $this->currentChallengeRecords[$i] = $nrecord;
                //and old takes new recs place
                $this->currentChallengeRecords[$i + 1] = $record;
                //Old record get's worse
                $record->place = $i + 2;
                //new get's better
                $nrecord->place = $i + 1;
                $i--;
            }

            if ($firstRecord) {
                $nrecord->place = 1;
            }
            $nrecord->ScoreCheckpoints = $cpScore;

            $this->debug("$login new rec Rank found : " . $nrecord->place . " Old was : " . $recordrank_old);

            //If relay don't send message, host server will send one.
            if ($this->expStorage->isRelay) {
                return;
            }

            /*
             * Found new Rank sending message
             */
            //Formating Time
            $time = $this->formatScore($nrecord->time);

            //No new rank, just drove a better time
            if ($nrecord->place == $recordrank_old && !$force && $nrecord->place <= $this->config->recordsCount) {
                $securedBy = $this->secureBy($nrecord->time, $recordtime_old);

                // equals time
                if ($nrecord->time == $recordtime_old) {
                    $msg = $this->msg_equals;
                    if ($nrecord->place <= 5) {
                        $msg = $this->msg_equals_top5;
                        if ($nrecord->place == 1) {
                            $msg = $this->msg_equals_top1;
                        }
                    }
                    if ($nrecord->place <= $this->config->recordPublicMsgTreshold) {
                        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time), $this->checkRecordTreshold($nrecord->place));
                    } else {
                        $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time), $this->checkRecordTreshold($nrecord->place));
                    }

                // improves time
                } else {
                    $msg = $this->msg_secure;
                    if ($nrecord->place <= 5) {
                        $msg = $this->msg_secure_top5;
                        if ($nrecord->place == 1) {
                            $msg = $this->msg_secure_top1;
                        }
                    }
                    if ($nrecord->place <= $this->config->recordPublicMsgTreshold) {
                        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time, $recordrank_old, $securedBy), $this->checkRecordTreshold($nrecord->place));
                    } else {
                        $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time, $recordrank_old, $securedBy), $this->checkRecordTreshold($nrecord->place));
                    }
                }

                Dispatcher::dispatch(new Event(Event::ON_UPDATE_RECORDS, $this->currentChallengeRecords));
            } else {
                //Improved time and new Rank
                if ($nrecord->place < $recordrank_old && !$force && $nrecord->place <= $this->config->recordsCount) {
                    $securedBy = $this->secureBy($nrecord->time, $recordtime_old);

                    $msg = $this->msg_improved;
                    if ($nrecord->place <= 5) {
                        $msg = $this->msg_improved_top5;
                        if ($nrecord->place == 1) {
                            $msg = $this->msg_improved_top1;
                        }
                    }

                    if ($nrecord->place <= $this->config->recordPublicMsgTreshold) {
                        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time, $recordrank_old, $securedBy), $this->checkRecordTreshold($nrecord->place));
                    } else {
                        $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time, $recordrank_old, $securedBy), $this->checkRecordTreshold($nrecord->place));
                    }

                    Dispatcher::dispatch(new Event(Event::ON_NEW_RECORD, $this->currentChallengeRecords, $nrecord));
                } else {
                    //First record the player drove
                    if ($nrecord->place <= $this->config->recordsCount) {
                        $msg = $this->msg_new;
                        if ($nrecord->place <= 5) {
                            $msg = $this->msg_new_top5;
                            if ($nrecord->place == 1) {
                                $msg = $this->msg_new_top1;
                            }
                        }
                        if ($nrecord->place <= $this->config->recordPublicMsgTreshold) {
                            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time), $this->checkRecordTreshold($nrecord->place));
                        } else {
                            $this->eXpChatSendServerMessage($msg, $login, array(Formatting::stripCodes($player->cleanNickName, 'wosnm'), $nrecord->place, $time), $this->checkRecordTreshold($nrecord->place));
                        }

                        Dispatcher::dispatch(new Event(Event::ON_NEW_RECORD, $this->currentChallengeRecords, $nrecord));
                    }
                }
            }
            Dispatcher::dispatch(new Event(Event::ON_PERSONAL_BEST, $nrecord));
            $this->updateRecordInDatabase($this->currentChallengePlayerRecords[$login], $isNew);
        } else {
            Dispatcher::dispatch(new Event(Event::ON_NEW_FINISH, $login));
            $this->updateRecordInDatabase($this->currentChallengePlayerRecords[$login], $isNew);
        }
    }

    /**
     * Will update the record in the database.
     *
     * @param Record $record
     * @param        $isNew
     *
     * @return bool Was the record updated in the database
     */
    protected function updateRecordInDatabase(Record $record, $isNew)
    {
        //If relay server host will save records no need to do it here
        if ($this->expStorage->isRelay) {
            return;
        }

        $uid = $record->uId;

        if ($isNew) {
            //If the record is new we insert
            $q = 'INSERT INTO `exp_records` (`record_challengeuid`, `record_playerlogin`, `record_nbLaps` ,`record_score`, `record_nbFinish`, `record_avgScore`, `record_checkpoints`, `record_date` , `score_type`) VALUES(' . $this->db->quote($uid) . ', ' . $this->db->quote($record->login) . ', ' . $this->db->quote($this->getNbOfLaps()) . ', ' . $this->db->quote($record->time) . ', ' . $this->db->quote($record->nbFinish) . ', ' . $this->db->quote($record->avgScore) . ', ' . $this->db->quote(implode(",", $record->ScoreCheckpoints)) . ', ' . $this->db->quote($record->date) . ', ' . $this->db->quote($this->getScoreType()) . ')';
            $this->db->execute($q);
        } else {
            //If it isn't but it has been updated we update
            $q = 'UPDATE `exp_records` SET `record_score` = ' . $this->db->quote($record->time) . ', `record_nbFinish` = ' . $this->db->quote($record->nbFinish) . ', `record_avgScore` = ' . $this->db->quote($record->avgScore) . ', `record_checkpoints` = ' . $this->db->quote(implode(",", $record->ScoreCheckpoints)) . ', `record_date` = ' . $this->db->quote($record->date) . ' WHERE `record_challengeuid` = ' . $this->db->quote($uid) . ' AND `record_playerlogin` =  ' . $this->db->quote($record->login) . ' AND `record_nbLaps` = ' . $this->db->quote($this->getNbOfLaps()) . ' AND `score_type` = ' . $this->db->quote($this->getScoreType()) . ';';
            $this->db->execute($q);
        }
    }

    /**
     * @param Record $record Record to delete
     * @param int $nbLaps
     *
     * @return bool
     */
    protected function deleteRecordInDatabase(Record $record, $nbLaps)
    {
        try {
            $q = 'DELETE FROM `exp_records` WHERE record_challengeuid = ' . $this->db->quote($record->uId) . ' AND record_playerlogin =' . $this->db->quote($record->login) . ' AND record_nbLaps = ' . $this->db->quote($nbLaps) . ' AND score_type = ' . $this->db->quote($this->getScoreType());
            $this->db->execute($q);
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * updateCurrentChallengeRecords()
     * Updates currentChallengePlayerRecords and the currentChallengeRecords arrays
     * with the current Challange Records.
     *
     * @return void
     */
    protected function updateCurrentChallengeRecords()
    {
        //If relay server host will save records no need to do it here
        if ($this->expStorage->isRelay) {
            return;
        }

        $this->currentChallangeSectorTimes = array();
        $this->currentChallangeSectorsCps = $this->calcCP($this->storage->currentMap->nbCheckpoints);

        $this->currentChallengePlayerRecords = array(); //reset
        $this->currentChallengeRecords = array(); //reset
        //Fetch best records
        $this->currentChallengeRecords = $this->buildCurrentChallangeRecords(); // fetch

        $uid = $this->storage->currentMap->uId;

        //Getting current players records
        foreach ($this->storage->players as $login => $player) { // get players
            $this->getFromDbPlayerRecord($login, $uid);
        }

        //Getting current spectators records
        foreach ($this->storage->spectators as $login => $player) { // get spectators
            $this->getFromDbPlayerRecord($login, $uid);
        }
        //Dispatch event
        Dispatcher::dispatch(new Event(Event::ON_RECORDS_LOADED, $this->currentChallengeRecords));
    }

    /**
     * It will get the list of records of this map from the database
     *
     * @return Record[]
     */
    protected function buildCurrentChallangeRecords()
    {
        $challenge = $this->storage->currentMap;

        $cons = " AND record_nbLaps = " . $this->getNbOfLaps();

        $q = "SELECT * FROM `exp_records`, `exp_players` WHERE `record_challengeuid` = " . $this->db->quote($challenge->uId) . " " . $cons . " AND `exp_records`.`record_playerlogin` = `exp_players`.`player_login` " . $cons . " AND `score_type` = " . $this->db->quote($this->getScoreType()) . " ORDER BY  " . $this->getDbOrderCriteria() . " LIMIT 0, " . $this->config->recordsCount . ";";
        $dbData = $this->db->execute($q);

        if ($dbData->recordCount() == 0) {
            return array();
        }

        $i = 1;
        $records = array();
        $players = array();
        while ($data = $dbData->fetchObject()) {
            $record = new Record();
            $this->currentChallengePlayerRecords[$data->record_playerlogin] = $record;

            $record->place = $i;
            $record->login = $data->record_playerlogin;
            $record->nickName = $data->player_nickname;
            $record->time = $data->record_score;
            $record->nbFinish = $data->record_nbFinish;
            $record->date = $data->record_date;
            $record->avgScore = $data->record_avgScore;
            $record->nation = $data->player_nation;
            $record->ScoreCheckpoints = explode(",", $data->record_checkpoints);
            $record->uId = $this->storage->currentMap->uId;

            if (isset($players[$record->login])) {
                $this->db->execute("DELETE FROM `exp_records` WHERE record_id = " . $data->record_id);
            } else {
                $records[$i - 1] = $record;
                $i++;
            }
        }

        return $records;
    }

    /**
     * get topsums
     *
     * @return array["string login"] = array("stats" => array(0,1,2), total);
     *
     */
    public function getTopSums()
    {
        $q = 'SELECT `record_challengeuid`, GROUP_CONCAT(`record_playerlogin` ORDER BY `record_score` ASC) logins FROM     `exp_records` WHERE `record_challengeuid` IN (' . $this->getUidSqlString() . ') GROUP BY  `record_challengeuid`';
        $sql = $this->db->execute($q);

        $topSums = array();

        foreach ($sql->fetchArrayOfObject() as $value) {
            $logins = explode(",", $value->logins);
            array_flip($logins);
            $logins = array_slice($logins, 0, 3);
            foreach ($logins as $index => $login) {
                if (!array_key_exists($login, $topSums)) {
                    $topSums[$login] = (object)array("stats" => array(0 => 0, 1 => 0, 2 => 0), "total" => 0);
                }
                $topSums[$login]->stats[$index]++;
                $topSums[$login]->total++;
            }
        }

        $players = array_keys($topSums);
        $playerlist = "";

        foreach ($players as $player) {
            $playerlist .= $this->db->quote($player) . ",";
        }

        $q = "SELECT * FROM `exp_players` where `player_login` IN (" . trim($playerlist, ',') . ");";
        $sql = $this->db->execute($q);

        foreach ($sql->fetchArrayOfObject() as $obj) {
            $topSums[$obj->player_login]->{"nickName"} = $obj->player_nickname;
        }

        uasort($topSums, function ($a, $b) {
            if ($a->total == $b->total) {
                return 0;
            }
            return ($a->total > $b->total) ? -1 : 1;
        });

        return $topSums;
    }

    /**
     * gets the records for a map and returns array of record objects
     *
     * @param Map $challenge
     * @param string $plugin
     *
     * @return array(Record)
     */
    public function getRecordsForMap($challenge = null, $plugin = null)
    {
        if ($challenge === null || $challenge == '') {
            $challenge = $this->storage->currentMap;
        }

        $cons = " AND record_nbLaps = " . $this->getNbOfLaps();

        $q = "SELECT * FROM `exp_records`, `exp_players` WHERE `record_challengeuid` = " . $this->db->quote($challenge->uId) . " " . $cons . " AND `exp_records`.`record_playerlogin` = `exp_players`.`player_login` " . $cons . " AND `score_type` = " . $this->db->quote($this->getScoreType()) . " ORDER BY " . $this->getDbOrderCriteria() . " LIMIT 0, " . $this->config->recordsCount . ";";

        $dbData = $this->db->execute($q);

        if ($dbData->recordCount() == 0) {
            return array();
        }

        $i = 1;
        $records = array();

        while ($data = $dbData->fetchObject()) {

            $record = new Record();

            $record->place = $i;
            $record->login = $data->record_playerlogin;
            $record->nickName = $data->player_nickname;
            $record->time = $data->record_score;
            $record->nbFinish = $data->record_nbFinish;
            $record->date = $data->record_date;
            $record->avgScore = $data->record_avgScore;
            $record->nation = $data->player_nation;
            $record->ScoreCheckpoints = explode(",", $data->record_checkpoints);
            $record->uId = $this->storage->currentMap->uId;

            $records[$i - 1] = $record;
            $i++;
        }

        return $records;
    }

    /**
     * getPlayerRecord()
     * Helper function, gets the record of the asked player.
     *
     * @param mixed $login
     * @param mixed $uId
     *
     * @return Record $record
     */
    protected function getFromDbPlayerRecord($login, $uId)
    {

        if (isset($this->currentChallengePlayerRecords[$login])) {
            return $this->currentChallengePlayerRecords[$login];
        }

        $q = "SELECT * FROM `exp_records`, `exp_players` WHERE `record_challengeuid` = " . $this->db->quote($uId) . " AND `record_playerlogin` = " . $this->db->quote($login) . " AND `player_login` = `record_playerlogin` AND `score_type` = " . $this->db->quote($this->getScoreType()) . "  AND record_nbLaps = " . $this->getNbOfLaps() . ";";

        $dbData = $this->db->execute($q);
        if ($dbData->recordCount() > 0) {

            $record = new Record();
            $data = $dbData->fetchObject();

            $record->place = $this->config->recordsCount + 1;
            $record->login = $data->record_playerlogin;
            $record->nickName = $data->player_nickname;
            $record->time = $data->record_score;
            $record->nbFinish = $data->record_nbFinish;
            $record->avgScore = $data->record_avgScore;
            $record->date = $data->record_date;
            $record->nation = $data->player_nation;
            $record->ScoreCheckpoints = explode(",", $data->record_checkpoints);
            $record->uId = $this->storage->currentMap->uId;

            $this->currentChallengePlayerRecords[$login] = $record;
        } else {
            return false;
        }
    }

    public function getCurrentChallangePlayerRecord($login)
    {
        return isset($this->currentChallengePlayerRecords[$login]) ? $this->currentChallengePlayerRecords[$login] : null;
    }

    public function getRecords()
    {
        return $this->currentChallengeRecords;
    }

    public function showTopSums($login)
    {
        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();
        $rankColor   = $colorParser->parseColors("#rank#");

        $items = array();
        $data  = array();
        $x     = 0;
        foreach ($this->getTopSums() as $sumLogin => $value) {
            $items[$x] = array($rankColor . ($x + 1), isset($value->nickName) ? $value->nickName : $sumLogin, $value->stats[0], $value->stats[1], $value->stats[2]);
            $data[$x] = array(-1, -1, -1, -1, -1);
            $x++;
        }

        $this->topSumsWindow->setParam("topSumItems", $items);
        $this->topSumsWindow->setParam("topSumData", $data);
        $this->topSumsWindow->show($login);
    }

    /**
     * showRecsWindow()
     *
     * Display a window for a login with best times
     *
     * @param type $login
     * @param \Maniaplanet\DedicatedServer\Structures\Map $map (optional)
     */
    public function showRecsWindow($login, $map = null)
    {
        if ($map === null) {
            $records = array();
            foreach ($this->currentChallengeRecords as $record) {
                $records[] = clone $record;
            }
            $map = $this->storage->currentMap;
        } else {
            $records = $this->getRecordsForMap($map);
        }
        $currentMap = false;
        if ($map == null || $map->uId == $this->storage->currentMap->uId) {
            $currentMap = true;
        }

        if ($this->config->hideRecords) {
            foreach ($records as $key => $record) {
                if ($record->login != $login) {
                    $records[$key]->time = 'HIDDEN';
                    $records[$key]->avgScore = 'HIDDEN';
                    $records[$key]->nbFinish = 'HIDDEN';
                    $records[$key]->ScoreCheckpoints = array();
                }
            }
        }

        $items = array();
        $data  = array();
        $i     = 0;
        while ($i < sizeof($records)) {
            /** @var Record $record */
            $record = $records[$i];

            $recLogin = $record->login;
            if ($recLogin == $record->nickName) {
                $recLogin = $recLogin . ' '; // hack to make it work
            }

            $items[$i] = array(
                $record->place,
                Gui::fixString($record->nickName),
                $recLogin,
                $this->formatScore($record->time) . " ",
                $this->formatScore($record->avgScore),
                "#" . $record->nbFinish,
                date('d/m/Y', $record->date),
                'Delete',
            );
            $data[$i] = array(-1, -1, -1, -1, -1, -1, -1, $currentMap ? ('exp:eXpansion.' . $this->extendedClassName . ':deleteRecord:' . $record->login) : -1);
            $i++;
        }
        $hideRemoveButtons = !$currentMap || !AdminGroups::hasPermission($login, Permission::LOCAL_RECORDS_DELETE);

        $this->recordsWindow->setParam("hideRemoveButtons", $hideRemoveButtons);
        $this->recordsWindow->setParam("hideMapButtons",    !$currentMap);
        $this->recordsWindow->setParam("sectorsAction",     'exp:eXpansion.' . $this->extendedClassName . ':showSectorWindow');
        $this->recordsWindow->setParam("cpsAction",         'exp:eXpansion.' . $this->extendedClassName . ':showCpWindow');
        $this->recordsWindow->setParam("secCpsAction",      'exp:eXpansion.' . $this->extendedClassName . ':showSecCpWindow');
        $this->recordsWindow->setParam("recordsItems",      $items);
        $this->recordsWindow->setParam("recordsData",       $data);
        $this->recordsWindow->show($login);
    }

    /**
     * exp: callback fired by the delete cell of a row in Records.xml
     */
    public function deleteRecord($login, $recordLogin)
    {
        if (!AdminGroups::hasPermission($login, Permission::LOCAL_RECORDS_DELETE)) {
            $this->eXpChatSendServerMessage("#admin_error#You don't have permission to delete records!", $login);
            return;
        }

        // Gui::fixString() (used by the pager to build the ManiaScript "data" array, see
        // OptimizedPager::formatMsArray) rewrites every "-" into an en-dash "–" to avoid "--"
        // being read as a ManiaScript comment. Real logins never contain an en-dash, so this
        // round-trip conversion is undone before comparing against $record->login.
        $recordLogin = str_replace('–', '-', $recordLogin);

        foreach ($this->currentChallengeRecords as $record) {
            if ($record->login === $recordLogin) {
                $this->actionDelete($login, $record);
                return;
            }
        }
    }

    /**
     * Will show a window with the 100 best ranked players
     *
     * @param $login
     */
    public function showRanksWindow($login)
    {
        $ranks = $this->getRanks();

        $items = array();
        $data  = array();
        $i     = 0;
        while ($i < sizeof($ranks)) {
            $rank = $ranks[$i];

            $items[$i] = array(
                ($i + 1) . ".",
                isset($rank->player_nickname) ? $rank->player_nickname : $rank->login,
                isset($rank->player_wins) ? $rank->player_wins : 0,
                number_format($rank->tscore + 1, 2),
                isset($rank->nbFinish) ? $rank->nbFinish : 0,
                $rank->nbRecords . '/' . $rank->nbMaps,
                $this->formatRankPlayTime(isset($rank->player_timeplayed) ? $rank->player_timeplayed : 0),
                isset($rank->lastRec) ? date("j F Y", $rank->lastRec) : "",
            );
            $data[$i] = array(-1, -1, -1, -1, -1, -1, -1, -1);
            $i++;
        }

        $this->ranksWindow->setParam("rankItems",       $items);
        $this->ranksWindow->setParam("rankData",        $data);
        $this->ranksWindow->show($login);
    }

    /**
     * Play time of a player, as shown in the server ranks window
     */
    private function formatRankPlayTime($time)
    {
        $min = (int)($time / 60);
        $hour = (int)($min / 60);
        $min = $min % 60;
        $day = (int)($hour / 24);
        $hour = $hour % 24;

        return $day . 'd ' . $hour . 'h ' . $min . 'm';
    }

    public function showCpWindow($login, $offset = 0)
    {
        if ($this->config->hideRecords) {
            $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
            return;
        }

        $offset = is_numeric($offset) ? (int)$offset : 0;

        if ($offset > $this->storage->currentMap->nbCheckpoints - 7) {
            $offset = $this->storage->currentMap->nbCheckpoints - 7;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();
        $rankColor   = $colorParser->parseColors("#rank#");

        $items = array();
        $data  = array();
        $i     = 0;
        foreach ($this->currentChallengeRecords as $record) {
            $row = array($rankColor . ($i + 1), $record->nickName);
            // always 7 cells, a record without that checkpoint simply leaves it empty
            for ($x = $offset; $x < $offset + 7; $x++) {
                $row[] = isset($record->ScoreCheckpoints[$x]) ? Time::fromTM($record->ScoreCheckpoints[$x]) : "";
            }
            $items[$i] = $row;
            $data[$i]  = array(-1, -1, -1, -1, -1, -1, -1, -1, -1);
            $i++;
        }

        for ($c = 0; $c < 7; $c++) {
            $this->cpsWindow->setParam("cp" . ($c + 1), "Cp " . ($offset + $c + 1));
        }
        $this->cpsWindow->setParam("cpsItems", $items);
        $this->cpsWindow->setParam("cpsData", $data);
        $this->cpsWindow->setParam("pagerVarName", "localCps");
        $this->cpsWindow->setParam("prevPageAction", 'exp:eXpansion.' . $this->extendedClassName . ':showCpWindow:' . ($offset - 7));
        $this->cpsWindow->setParam("nextPageAction", 'exp:eXpansion.' . $this->extendedClassName . ':showCpWindow:' . ($offset + 7));
        $this->cpsWindow->show($login);
    }

    public function showSecCpWindow($login, $offset = 0)
    {
        if ($this->config->hideRecords) {
            $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
            return;
        }

        $offset = is_numeric($offset) ? (int)$offset : 0;

        if ($offset > $this->storage->currentMap->nbCheckpoints - 7) {
            $offset = $this->storage->currentMap->nbCheckpoints - 7;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();
        $rankColor   = $colorParser->parseColors("#rank#");

        $items = array();
        $data  = array();
        $i     = 0;
        foreach ($this->currentChallengeRecords as $record) {
            $row = array($rankColor . ($i + 1), $record->nickName);
            // always 7 cells, a record without that checkpoint simply leaves it empty
            for ($x = $offset; $x < $offset + 7; $x++) {
                if (!isset($record->ScoreCheckpoints[$x])) {
                    $row[] = "";
                } else if ($x == 0) {
                    // the first sector is the time of the first checkpoint
                    $row[] = Time::fromTM($record->ScoreCheckpoints[$x]);
                } else {
                    $row[] = Time::fromTM($record->ScoreCheckpoints[$x] - $record->ScoreCheckpoints[$x - 1]);
                }
            }
            $items[$i] = $row;
            $data[$i]  = array(-1, -1, -1, -1, -1, -1, -1, -1, -1);
            $i++;
        }

        for ($c = 0; $c < 7; $c++) {
            $this->secCpsWindow->setParam("cp" . ($c + 1), "Sector " . ($offset + $c + 1));
        }
        $this->secCpsWindow->setParam("cpsItems", $items);
        $this->secCpsWindow->setParam("cpsData", $data);
        $this->secCpsWindow->setParam("pagerVarName", "localSecCps");
        $this->secCpsWindow->setParam("prevPageAction", 'exp:eXpansion.' . $this->extendedClassName . ':showSecCpWindow:' . ($offset - 7));
        $this->secCpsWindow->setParam("nextPageAction", 'exp:eXpansion.' . $this->extendedClassName . ':showSecCpWindow:' . ($offset + 7));
        $this->secCpsWindow->show($login);
    }

    public function showCpDiffWindow($login, $params)
    {
        if ($this->config->hideRecords) {
            $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
            return;
        }

        if (!is_numeric($params)) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide a correct number'), $login);
            return;
        }
        $params = intval(preg_replace("/[^0-9]/", "", $params));
        $params-=1;

        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania')) {
            $dedirecs = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania", "getRecords");
        }
        if (isset($dedirecs[$login])) {
            if ($this->getCurrentChallangePlayerRecord($login)) {
                if ($this->getCurrentChallangePlayerRecord($login)->time <= $dedirecs[$login]->time) {
                    $player = $this->getCurrentChallangePlayerRecord($login);
                } else {
                    $player = new Record();
                    $player->place = $dedirecs[$login]->place;
                    $player->nickName = $dedirecs[$login]->nickname;
                    $player->ScoreCheckpoints = explode(",", $dedirecs[$login]->checkpoints);
                }
            } else {
                $player = new Record();
                $player->place = $dedirecs[$login]->place;
                $player->nickName = $dedirecs[$login]->nickname;
                $player->ScoreCheckpoints = explode(",", $dedirecs[$login]->checkpoints);
            }
        } else {
            $player = $this->getCurrentChallangePlayerRecord($login);
        }

        if (!isset($this->currentChallengeRecords[$params])) {
            $this->eXpChatSendServerMessage("#admin_error#There is no such record!", $login);
            return;
        }

        $target = $this->currentChallengeRecords[$params];

        $this->showCpDiff($login, $player, $target);
    }

    public function showCpDiffNoDediWindow($login, $params)
    {
        if ($this->config->hideRecords) {
            $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
            return;
        }

        if (!is_numeric($params)) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide a correct number'), $login);
            return;
        }
        $params = intval(preg_replace("/[^0-9]/", "", $params));
        $params-=1;

        if (!isset($this->currentChallengeRecords[$params])) {
            $this->eXpChatSendServerMessage("#admin_error#There is no such record!", $login);
            return;
        }

        $this->showCpDiff($login, $this->getCurrentChallangePlayerRecord($login), $this->currentChallengeRecords[$params]);
    }

    /**
     * Fill and show CpDiff.xml, comparing the checkpoints of two records
     */
    private function showCpDiff($login, $player, $target)
    {
        if (!$player) {
            $this->eXpChatSendServerMessage("#admin_error#You have no record on this map!", $login);
            return;
        }

        $items = array();
        $data  = array();
        $nbCps = count($player->ScoreCheckpoints);
        for ($x = 0; $x < $nbCps; $x++) {
            if (!isset($target->ScoreCheckpoints[$x])) {
                $items[$x] = array(($x + 1) . ".", Time::fromTM($player->ScoreCheckpoints[$x]), "", "", "");
                $data[$x]  = array(-1, -1, -1, -1, -1);
                continue;
            }

            $diff = $player->ScoreCheckpoints[$x] - $target->ScoreCheckpoints[$x];
            if ($x > 0 && isset($target->ScoreCheckpoints[$x - 1])) {
                $diffCp = $diff - ($player->ScoreCheckpoints[$x - 1] - $target->ScoreCheckpoints[$x - 1]);
            } else {
                $diffCp = $diff;
            }

            $items[$x] = array(
                ($x + 1) . ".",
                Time::fromTM($player->ScoreCheckpoints[$x]),
                Time::fromTM($target->ScoreCheckpoints[$x]),
                ($diff   <= 0 ? '$0f0- ' : '$f00+ ') . Time::fromTM($diff),
                ($diffCp <= 0 ? '$0f0- ' : '$f00+ ') . Time::fromTM($diffCp),
            );
            $data[$x] = array(-1, -1, -1, -1, -1);
        }

        $this->cpDiffWindow->setParam("pagerVarName", "localcpdiff");
        $this->cpDiffWindow->setParam("headerPlayer", $this->cpDiffWindow->handleSpecialChars("#" . $player->place . ": " . $player->nickName));
        $this->cpDiffWindow->setParam("headerTarget", $this->cpDiffWindow->handleSpecialChars("#" . $target->place . ": " . $target->nickName));
        $this->cpDiffWindow->setParam("cpDiffItems", $items);
        $this->cpDiffWindow->setParam("cpDiffData", $data);
        $this->cpDiffWindow->show($login);
    }

    public function showSectorWindow($login)
    {
        if ($this->config->hideRecords) {
            $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
            return;
        }
        
        if (empty($this->currentChallangeSectorTimes)) {

            $secs = array();

            foreach ($this->currentChallengePlayerRecords as $rec) {
                for ($cpt = 0; $cpt < sizeof($this->currentChallangeSectorsCps); $cpt++) {
                    $currentIndex = $this->currentChallangeSectorsCps[$cpt] - 1;
                    $prevIndex = $cpt == 0 ? -1 : $this->currentChallangeSectorsCps[$cpt - 1] - 1;

                    if (isset($rec->ScoreCheckpoints[$currentIndex])) {
                        $old = ($prevIndex < 0) ? 0 : (isset($rec->ScoreCheckpoints[$prevIndex]) ? $rec->ScoreCheckpoints[$prevIndex] : 0);
                        $secs[$cpt][] = array('sectorTime' => $rec->ScoreCheckpoints[$currentIndex] - $old, 'recordObj' => $rec);
                    }
                }
            }

            $i = 0;
            foreach ($secs as $sec) {
                $this->currentChallangeSectorTimes[$i] = $this->array_sort($sec, 'sectorTime');
                $i++;
            }
        }

        $items = array();
        $data  = array();
        $i     = 0;
        foreach ($this->currentChallangeSectorTimes as $sector) {
            $row = array(($i + 1) . ".");
            foreach (array_slice($sector, 0, 5) as $best) {
                $row[] = $best['recordObj']->nickName;
                $row[] = $this->formatScore($best['sectorTime']);
            }
            // always 11 cells, even when less than 5 players have a time on this sector
            while (count($row) < 11) {
                $row[] = "";
            }
            $items[$i] = $row;
            $data[$i]  = array(-1, -1, -1, -1, -1);
            $i++;
        }

        $this->sectorWindow->setParam("sectorItems", $items);
        $this->sectorWindow->setParam("sectorData", $data);
        $this->sectorWindow->show($login);
    }

    protected function calcCP($totalcps)
    {
        $cpsect = floor($totalcps * 0.001);
        $sect = 0;
        $cp = 0;
        $array = array();

        for ($x = 0; $x < $totalcps; $x++) {
            if ($x % ($cpsect + 1) == 0) {
                $cp++;
                $sect++;
                $array[$sect - 1] = $cp;
            } else {
                $cp++;
                $array[$sect - 1] = $cp;
            }
        }

        return $array;
    }

    protected function array_sort($array, $on, $order = SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }
            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    /**
     * Ranks of all players online on the server
     *
     * @return array
     */
    public function getOnlineRanks()
    {
        return $this->player_ranks;
    }

    /**
     * The Total number of player ranked
     *
     * @return int
     */
    public function getTotalRanked()
    {
        if ($this->total_ranks == -1 && !$this->expStorage->isRelay) {
            $q = 'SELECT Count(*) as nbRanked FROM exp_records WHERE record_challengeuid IN (' . $this->getUidSqlString() . ') GROUP BY record_playerlogin';

            $data = $this->db->execute($q);

            if ($data->recordCount() == 0) {
                $this->total_ranks = -1;
            } else {
                $vals = $data->fetchObject();
                $this->total_ranks = $data->recordCount();
            }
        }

        return $this->total_ranks;
    }

    /**
     * Returns the players server rank as it is buffered.
     *
     * @param $login
     *
     * @return int
     */
    public function getPlayerRank($login)
    {
        if ($this->expStorage->isRelay) {
            return -1;
        }

        if (!isset($this->player_ranks[$login])) {

            $nbTrack = sizeof($this->storage->maps);
            $uids = $this->getUidSqlString();

            $q = 'SELECT ((SUM( `rank` ) + (' . $nbTrack . ' - COUNT( * ) ) *' . $this->config->recordsCount . ')/' . $nbTrack . ') AS `points`, COUNT(*) as nbFinish FROM
            (SELECT *, IF(@prev <> exp_records.record_challengeuid, @rn:=-1,@rn), @prev:=exp_records.record_challengeuid, @rn:=@rn+1 AS `rank` FROM `exp_records`, (SELECT @rn:=0) rn, (SELECT @prev:=\'\') prev ORDER BY exp_records.record_challengeuid ASC, exp_records.record_score ASC) temp
            WHERE record_playerlogin = ' . $this->db->quote($login) . ' AND record_challengeuid IN (' . $uids . ')';

            $data = $this->db->execute($q);

            if ($data->recordCount() == 0) {
                $this->player_ranks[$login] = -1;
                return -1;
            } else {
                $vals = $data->fetchObject();
                $points = $vals->points;

                if (empty($points) || $vals->nbFinish <= 5) {
                    $this->player_ranks[$login] = -1;
                    return -1;
                }
            }

            $q = 'SELECT record_playerlogin as betters FROM
            (SELECT *, IF(@prev <> exp_records.record_challengeuid, @rn:=-1,@rn), @prev:=exp_records.record_challengeuid, @rn:=@rn+1 AS `rank` FROM `exp_records`, (SELECT @rn:=0) rn, (SELECT @prev:=\'\') prev ORDER BY exp_records.record_challengeuid ASC, exp_records.record_score ASC) temp
            WHERE record_challengeuid IN (' . $uids . ') GROUP BY record_playerlogin HAVING ((SUM(`rank`) + (' . $nbTrack . ' - Count(*))*' . $this->config->recordsCount . ')/' . $nbTrack . ') < ' . $points;

            $data = $this->db->execute($q);

            if ($data->recordCount() == 0) {
                $this->player_ranks[$login] = 1;
            } else {
                $this->player_ranks[$login] = $data->recordCount() + 1;
            }
        }

        return $this->player_ranks[$login];
    }

    /**
     *  Updates the bufffer of the 100 best ranked players if needed
     *
     * @return array
     */
    public function getRanks()
    {
        if ((empty($this->ranks) || $this->rank_needUpdated) && !$this->expStorage->isRelay) {

            $this->debug("Fetching Server Ranks from Database !");

            $this->rank_needUpdated = false;
            $this->total_ranks = -1;
            $this->getTotalRanked();

            $nbTrack = sizeof($this->storage->maps);
            $uids = $this->getUidSqlString();

            $q = 'SELECT record_playerlogin as login, ((SUM(`rank`) + (' . $nbTrack . ' - Count(*))*' . $this->config->recordsCount . ')/' . $nbTrack . ') as `tscore`, Count(1) as nbRecords,  ' . $nbTrack . ' as nbMaps FROM
            (SELECT *, IF(@prev <> exp_records.record_challengeuid, @rn:=-1,@rn), @prev:=exp_records.record_challengeuid, @rn:=@rn+1 AS `rank` FROM `exp_records`, (SELECT @rn:=0) rn, (SELECT @prev:=\'\') prev ORDER BY exp_records.record_challengeuid ASC, exp_records.record_score ASC) temp
            WHERE record_challengeuid IN (' . $uids . ') GROUP BY record_playerlogin ORDER BY tscore ASC LIMIT 0,100';

            $dbData = $this->db->execute($q);

            $this->ranks = array();

            if ($dbData->recordCount() == 0) {
                return $this->ranks;
            }

            $tempranks = array();
            $loginlist = array();
            $i = 1;

            while ($data = $dbData->fetchObject()) {
                $tempranks[$data->login] = $data;
                $loginlist[] = $data->login;
                $this->player_ranks[$data->login] = $i++;
            }

            $this->debug("Fetching Records from Database !");

            $q = 'SELECT record_playerlogin AS login, SUM(record_nbFinish) as nbFinish, MAX(record_date) AS lastRec FROM exp_records WHERE record_playerlogin IN (' . $this->getLoginSqlString($loginlist) . ') GROUP BY record_playerlogin LIMIT 0,100';

            $dbData = $this->db->execute($q);

            while ($data = $dbData->fetchObject()) {
                $tempranks[$data->login] = (object)array_merge((array)$tempranks[$data->login], (array)$data);
            }

            $this->debug("Fetching Players from Database !");
            $q = 'SELECT player_login as login, player_nickname, player_updated, player_wins, player_timeplayed, player_nation FROM exp_players WHERE player_login IN (' . $this->getLoginSqlString($loginlist) . ') LIMIT 0,100;';

            $dbData = $this->db->execute($q);
            while ($data = $dbData->fetchObject()) {
                $tempranks[$data->login] = (object)array_merge((array)$tempranks[$data->login], (array)$data);
            }

            $this->ranks = array_values($tempranks);
        }

        return $this->ranks;
    }

    /**
     * Chat message displaying rank of player
     */
    public function chat_showRank($login = null)
    {
        if ($login != null) {

            $rank = $this->getPlayerRank($login);
            $rankTotal = $this->getTotalRanked();

            if ($rank > 0) {

                $ranks = $this->getRanks();
                $average = $this->getObjbyPropValue($ranks, "login", $login);

                if ($average) {
                    $this->eXpChatSendServerMessage($this->msg_showRankAndAverage, $login, array($rank, $rankTotal, round($average[0]->tscore + 1, 2)));
                } else {
                    $this->eXpChatSendServerMessage($this->msg_showRank, $login, array($rank, $rankTotal));
                }
            } else {
                $this->eXpChatSendServerMessage($this->msg_noRank, $login);
            }
        }
    }

    /**
     * Chat message displaying the next better ranked player
     */
    public function chat_nextRank($login = null)
    {
        if ($login != null) {

            $rank = $this->getPlayerRank($login);
            $rankTotal = $this->getTotalRanked();

            if ($rank > 0) {

                $ranks = $this->getRanks();
                $average = $this->getObjbyPropValue($ranks, "login", $login);

                if ($average[1] == 0) {
                    $this->eXpChatSendServerMessage($this->msg_no_nextRank, $login);
                    return;
                }

                if ($average) {
                    $this->eXpChatSendServerMessage($this->msg_nextRankAndAverage, $login, array($ranks[$average[1] - 1]->player_nickname, $this->getPlayerRank($ranks[$average[1] - 1]->login), $rankTotal, round($ranks[$average[1] - 1]->tscore + 1, 2), round($ranks[$average[1] - 1]->tscore - $average[0]->tscore, 2)));
                }
            } else {
                $this->eXpChatSendServerMessage($this->msg_noRank, $login);
            }
        }
    }

    public function chat_personalBest($login = null)
    {
        if ($login != null) {
            $record = $this->getCurrentChallangePlayerRecord($login);
            if (!$record) {
                $this->eXpChatSendServerMessage($this->msg_noPB, $login);
            } else {
                $time = $this->formatScore($record->time);
                $avg = $this->formatScore($record->avgScore);

                if ($record->place > 0 && $record->place <= $this->config->recordsCount) {
                    $place = $record->place;
                } else {
                    $place = '--';
                }

                $this->eXpChatSendServerMessage($this->msg_personalBest, $login, array($time, $place, $avg, $record->nbFinish));
            }
        }
    }

    public function actionDelete($login, $record)
    {
        if (!AdminGroups::hasPermission($login, Permission::LOCAL_RECORDS_DELETE)) {
            $this->eXpChatSendServerMessage("#admin_error#You don't have permission to delete records!", $login);
            return;
        }

        Gui::showConfirmDialog($login, 'exp:eXpansion.' . $this->extendedClassName . ':delRec:' . $record->login, "Delete records " . $record->place . " by " . $record->nickName . "?");
    }

    public function delRec($login, $recordLogin)
    {
        if (!AdminGroups::hasPermission($login, Permission::LOCAL_RECORDS_DELETE)) {
            $this->eXpChatSendServerMessage("#admin_error#You don't have permission to delete records!", $login);
            return;
        }

        $record = $this->getCurrentChallangePlayerRecord($recordLogin);
        if (!$record) {
            $this->eXpChatSendServerMessage('Error while remove player record', $login);
            return;
        }
        
        if (!$this->deleteRecordInDatabase($record, $this->getNbOfLaps())) {
            $this->eXpChatSendServerMessage('Error while remove player record', $login);
            return;
        }

        unset($this->currentChallengePlayerRecords[$record->login]);

        foreach ($this->currentChallengeRecords as $i => $rec) {
            if ($rec->login == $record->login) {
                unset($this->currentChallengeRecords[$i]);
                continue;
            }
            if ($rec->place > $record->place) {
                $rec->place--;
            }
        }
        // Re-index to a clean 0-based sequential array (index i <-> place i+1), self-healing any
        // previous gap/duplicate left by an older buggy run of this function.
        $this->currentChallengeRecords = array_values($this->currentChallengeRecords);

        Dispatcher::dispatch(new Event(Event::ON_RECORD_DELETED, $record, $this->currentChallengeRecords));

        $this->eXpChatSendServerMessage('Record deleted', $login);
        $this->showRecsWindow($login);
    }

    public function chat_delRecord($login, $playerLogin = array())
    {
        $playerLogin = array_shift($playerLogin);

        if (!$playerLogin) {
            $this->eXpChatSendServerMessage("This command takes a login as parameter, none entered", $login);
            return;
        }

        $q = "SELECT * FROM `exp_records` WHERE `exp_records`.`record_playerlogin` = " . $this->db->quote($playerLogin) . ";";
        $ret = $this->db->execute($q);

        if ($ret->recordCount() == 0) {
            $this->eXpChatSendServerMessage("Can't delete records: Login %s has no records.", $login, array($playerLogin));
            return;
        }

        Gui::showConfirmDialog($login, 'exp:eXpansion.' . $this->extendedClassName . ':delRecs:' . $playerLogin, "Delete all records by " . $playerLogin . "?");
    }

    public function delRecs($login, $playerLogin)
    {
        if (!AdminGroups::hasPermission($login, Permission::LOCAL_RECORDS_DELETE)) {
            $this->eXpChatSendServerMessage("#admin_error#You don't have permission to delete records!", $login);
            return;
        }

        $q = "DELETE FROM `exp_records` WHERE `exp_records`.`record_playerlogin` = " . $this->db->quote($playerLogin) . ";";
        try {
            $this->db->execute($q);
            Gui::showNotice("All records by " . $playerLogin . " are now deleted\n Widgets and records will update at next map.", $login);
        } catch (\Exception $e) {
            Gui::showNotice("Error deleting records by " . $playerLogin, $login);
        }
    }


    /**
     * Returns an array containing all the uid's of all the maps of the server
     */
    public function getUidArray()
    {
        $uids = array();
        foreach ($this->storage->maps as $map) {
            $uids[] = $map->uId;
        }

        return $uids;
    }

    /**
     * Returns a string to be used to in SQL to flter tracks
     *
     * @return string
     */
    public function getUidSqlString()
    {
        $uids = "";
        foreach ($this->storage->maps as $map) {
            $uids .= $this->db->quote($map->uId) . ",";
        }

        return trim($uids, ",");
    }


    public function getLoginSqlString($logins)
    {
        $out = "";
        foreach ($logins as $login) {
            $out .= $this->db->quote($login) . ",";
        }

        return trim($out, ",");
    }

    public function getObjbyPropValue(&$array, $prop, $value)
    {
        if (!is_array($array)) {
            return false;
        }

        $index = 0;
        foreach ($array as $class) {
            if (!property_exists($class, $prop)) {
                throw new \Exception("Property $prop doesn't exists!");
            }

            if ($class->$prop == $value) {
                return array($class, $index);
            }
            $index++;
        }
        return false;
    }

    public function eXpOnUnload()
    {

        if ($this->recordsWindow instanceof Window) {
            $this->recordsWindow->erase();
        }
        $this->recordsWindow = null;

        if ($this->ranksWindow instanceof Window) {
            $this->ranksWindow->erase();
        }
        $this->ranksWindow = null;

        if ($this->topSumsWindow instanceof Window) {
            $this->topSumsWindow->erase();
        }
        $this->topSumsWindow = null;

        if ($this->sectorWindow instanceof Window) {
            $this->sectorWindow->erase();
        }
        $this->sectorWindow = null;

        if ($this->cpsWindow instanceof Window) {
            $this->cpsWindow->erase();
        }
        $this->cpsWindow = null;

        if ($this->cpDiffWindow instanceof Window) {
            $this->cpDiffWindow->erase();
        }
        $this->cpDiffWindow = null;

        if ($this->secCpsWindow instanceof Window) {
            $this->secCpsWindow->erase();
        }
        $this->secCpsWindow = null;
    }
}
