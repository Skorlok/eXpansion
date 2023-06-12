<?php

namespace ManiaLivePlugins\eXpansion\Database;

use ManiaLib\Utils\Formatting as StringFormatting;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Database\Structures\DbPlayer;

/**
 * Description of Database
 *
 * @author oliverde8
 */
class Database extends ExpPlugin
{
    private $config;

    public function eXpOnInit()
    {
        $this->config = Config::getInstance();
        Gui\Windows\BackupRestore::$mainPlugin = $this;
    }

    public function eXpOnLoad()
    {
        parent::eXpOnLoad();
        try {
            $this->enableDatabase();
        } catch (\Exception $e) {
            $this->dumpException("There seems be a problem while establishing a MySQL connection.", $e);
            die();
        }
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
        $this->initCreateTables();

        foreach ($this->storage->players as $login => $player) { // get players
            $this->onPlayerConnect($login, false);
        }
        foreach ($this->storage->spectators as $login => $player) { // get spectators
            $this->onPlayerConnect($login, false);
        }

        $this->setPublicMethod('getPlayer');
        $this->setPublicMethod('showDbMaintenance');
        $this->updateServerChallenges();
        // add admin command ;)
        $cmd = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::addAdminCommand('dbtools', $this, 'showDbMaintenance', Permission::SERVER_DATABASE);
        $cmd->setHelp('shows administrative window for database');
        $cmd->setMinParam(0);
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->config = Config::getInstance();
    }

    public function onTick()
    {
        if ($this->config->enableBackup) {
            $interval = (intval($this->config->backupInterval * 60));
            if ($interval < 1) {
                $interval = 1;
            }
            if (time() % $interval == 0) {
                $this->exportToSql(null, array("filename" => $this->storage->serverLogin . "_" . date("d-m-Y")));
            }
        }
    }

    public function onPlayerConnect($login, $isSpec)
    {
        $g = "SELECT * FROM `exp_players` WHERE `player_login` = " . $this->db->quote($login) . ";";
        $query = $this->db->execute($g);
        // get player data
        $time = \time();
        $player = $this->storage->getPlayerObject($login);
        $this->storage->getPlayerObject($login)->lastTimeUpdate = $time;

        if ($query->recordCount() == 0) {
            $q = "INSERT INTO `exp_players`
                    (`player_login`,`player_nickname`, `player_nicknameStripped`, `player_updated`, `player_ip`,
                        `player_onlinerights`, `player_nation`, `player_wins`, `player_timeplayed`)
                    VALUES (" . $this->db->quote($player->login) . ",
                            " . $this->db->quote($player->nickName) . ",
                            " . $this->db->quote(StringFormatting::stripStyles($player->nickName)) . ",
                            " . $this->db->quote($time) . ",
                            " . $this->db->quote($player->iPAddress) . ",
                            " . $this->db->quote($player->onlineRights) . ",
                            " . $this->db->quote($player->path) . ",
                            0,
                            0
                            )";
            $this->db->execute($q);

            $dbPlayer = new DbPlayer($player->login, 0, $time, 0);
        } else {
            $q = "UPDATE `exp_players` SET
                `player_nickname` = " . $this->db->quote($player->nickName) . ",
                `player_nicknameStripped` = " . $this->db->quote(StringFormatting::stripStyles($player->nickName)) . ",
                `player_updated` = " . $this->db->quote($time) . ",
                `player_ip` =  " . $this->db->quote($player->iPAddress) . ",
                `player_onlinerights` = " . $this->db->quote($player->onlineRights) . "
             WHERE `player_login` = " . $this->db->quote($login) . ";";
            $this->db->execute($q);

            $query = $query->fetchObject();

            $dbPlayer = new DbPlayer($player->login, $query->player_timeplayed, $time, $query->player_wins);
        }
        $this->expStorage->dbPlayers[$login] = $dbPlayer;
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        $this->updatePlayTime($this->storage->getPlayerObject($login));
        unset($this->expStorage->dbPlayers[$login]);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        $winner = ArrayOfObj::getObjbyPropValue(Core::$rankings, "rank", "1");
        if (sizeof($this->storage->players) > 1 && $this->eXpGetCurrentCompatibilityGameMode() != GameInfos::GAMEMODE_TEAM && !Core::$useTeams && $winner && $winner->bestTime != -1) {
            $this->incrementWins($winner);
        }

        foreach ($this->storage->players as $login => $player) { // get players
            $this->updatePlayTime($player);
        }

        foreach ($this->storage->spectators as $login => $player) { // get spectators
            $this->updatePlayTime($player);
        }
    }

    public function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
    {
        $this->updateServerChallenges();
    }

    public function updatePlayTime($player)
    {
        $time = time();
        if (empty($player) || (!$player->spectator && $this->expStorage->isRelay))
            return;

        if (isset($player->lastTimeUpdate)) {
            $playtime = $time - $player->lastTimeUpdate;
            $q = "UPDATE `exp_players` SET `player_timeplayed` = (`player_timeplayed` + $playtime) WHERE `player_login` = " . $this->db->quote($player->login) . ";";
            $this->db->execute($q);
        }
        $player->lastTimeUpdate = $time;
    }

    public function updateServerChallenges()
    {
        if ($this->expStorage->isRelay) {
            return;
        }
        //get database challenges
        $uids = "";
        $mapsByUid = array();
        foreach ($this->storage->maps as $map) {
            $uids .= $this->db->quote($map->uId) . ",";
            $mapsByUid[$map->uId] = $map;
        }
        $uids = trim($uids, ",");
        $g = "SELECT * FROM `exp_maps`  WHERE challenge_uid IN ($uids);";
        $query = $this->db->execute($g);


        while ($data = $query->fetchStdObject()) {
            if (isset($mapsByUid[$data->challenge_uid])) {
                $mapsByUid[$data->challenge_uid]->addTime = $data->challenge_addtime;
                unset($mapsByUid[$data->challenge_uid]);
            }
        }

        if (!empty($mapsByUid)) {
            foreach ($mapsByUid as $map) {
                $this->insertMap($map);
                $map->addTime = time();
            }
        }
    }

    public function insertMap($data, $login = 'n/a')
    {
        if (empty($data->mood)) {
            $connection = $this->connection;
            try {
                $data = $connection->getMapInfo($data->fileName);
            } catch (\Exception $e) {
            }
        }

        $q = "INSERT INTO `exp_maps` (`challenge_uid`,
                                    `challenge_name`,
                                    `challenge_nameStripped`,
                                    `challenge_file`,
                                    `challenge_author`,
                                    `challenge_environment`,
                                    `challenge_mood`,
                                    `challenge_bronzeTime`,
                                    `challenge_silverTime`,
                                    `challenge_goldTime`,
                                    `challenge_authorTime`,
                                    `challenge_copperPrice`,
                                    `challenge_lapRace`,
                                    `challenge_nbLaps`,
                                    `challenge_nbCheckpoints`,
                                    `challenge_addedby`,
                                    `challenge_addtime`)
                                VALUES (" . $this->db->quote($data->uId) . ",
                                " . $this->db->quote($data->name) . ",
                                " . $this->db->quote(StringFormatting::stripStyles($data->name)) . ",
                                " . $this->db->quote($data->fileName) . ",
                                " . $this->db->quote($data->author) . ",
                                " . $this->db->quote($data->environnement) . ",
                                " . $this->db->quote($data->mood) . ",
                                " . $this->db->quote($data->bronzeTime) . ",
                                " . $this->db->quote($data->silverTime) . ",
                                " . $this->db->quote($data->goldTime) . ",
                                " . $this->db->quote($data->authorTime) . ",
                                " . $this->db->quote($data->copperPrice) . ",
                                " . $this->db->quote($data->lapRace ? 1 : 0) . ",
                                " . $this->db->quote($data->nbLaps) . ",
                                " . $this->db->quote($data->nbCheckpoints) . ",
                                " . $this->db->quote($login) . ",
                                " . $this->db->quote(time()) . ")";
        $this->db->execute($q);
    }

    public function initCreateTables()
    {
        if (!$this->db->tableExists('exp_players')) {
            $this->createPlayersTable();
        }

        $playerDB = $this->db->execute("DESCRIBE `exp_players`")->fetchArrayOfObject();

        if ($playerDB[5]->Type != 'int(12)') {
            $q = "ALTER TABLE `exp_players` CHANGE `player_timeplayed` `player_timeplayed` INT( 12 ) NOT NULL DEFAULT '0';";
            $this->db->execute($q);
        }
        if ($playerDB[3]->Type != 'int(12)') {
            $q = "ALTER TABLE `exp_players` CHANGE `player_updated` `player_updated` INT( 12 ) NOT NULL DEFAULT '0';";
            $this->db->execute($q);
        }


        if (!$this->db->tableExists('exp_maps')) {
            $this->createMapTable();
        }

        $mapsDB = $this->db->execute("DESCRIBE `exp_maps`")->fetchArrayOfObject();

        if (!$mapsDB[1]->Key) {
            $this->db->execute('ALTER TABLE exp_maps ADD KEY(challenge_uid);');
        }

        if (count($mapsDB) < 53) {
            $sql = "ALTER TABLE `exp_maps` 
                ADD `mx_trackID` INT UNSIGNED NULL , 
                ADD `mx_userID` INT UNSIGNED NULL , 
                ADD `mx_username` VARCHAR(100) NULL , 
                ADD `mx_uploadedAt` DATETIME NULL , 
                ADD `mx_updatedAt` DATETIME NULL , 
                ADD `mx_typeName` VARCHAR(100) NULL , 
                ADD `mx_mapType` VARCHAR(255) NULL , 
                ADD `mx_titlePack` VARCHAR(255) NULL , 
                ADD `mx_styleName` VARCHAR(255) NULL , 
                ADD `mx_displayCost` INT NULL , 
                ADD `mx_modName` VARCHAR(255) NULL , 
                ADD `mx_lightMap` INT NULL ,
                ADD `mx_exeVersion` VARCHAR(50) NULL ,
                ADD `mx_exeBuild` VARCHAR(100) NULL , 
                ADD `mx_environmentName` VARCHAR(255) NULL ,
                ADD `mx_vehicleName` VARCHAR(255) NULL , 
                ADD `mx_unlimiterRequired` BOOLEAN NULL , 
                ADD `mx_routeName` VARCHAR(255) NULL , 
                ADD `mx_lengthName` VARCHAR(100) NULL , 
                ADD `mx_laps` INT NULL,
                ADD `mx_difficultyName` VARCHAR(100) NULL,
                ADD `mx_replayTypeName` VARCHAR(100) NULL,
                ADD `mx_replayWRID` INT UNSIGNED NULL,
                ADD `mx_replayWRTime` INT NULL,
                ADD `mx_replayWRUserID` INT NULL,
                ADD `mx_replayWRUsername` VARCHAR(255) NULL,
                ADD `mx_ratingVoteCount` INT NULL,
                ADD `mx_ratingVoteAverage` FLOAT NULL,
                ADD `mx_replayCount` INT NULL,
                ADD `mx_trackValue` INT NULL,
                ADD `mx_comments` TEXT NULL,
                ADD `mx_commentsCount` INT NULL,
                ADD `mx_awardCount` INT NULL,
                ADD `mx_hasScreenshot` BOOLEAN NULL,
                ADD `mx_hasThumbnail` BOOLEAN NULL,
                CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
            $this->db->execute($sql);
        }
    }

    public function createPlayersTable()
    {
        $q = "CREATE TABLE IF NOT EXISTS `exp_players` (
                `player_login` varchar(50) NOT NULL,
                `player_nickname` varchar(100) NOT NULL,
                `player_nicknameStripped` varchar(100) NOT NULL,
                `player_updated` mediumint(9) NOT NULL DEFAULT '0',
                `player_wins` mediumint(9) NOT NULL DEFAULT '0',
                `player_timeplayed` mediumint(9) NOT NULL DEFAULT '0',
                `player_onlinerights` varchar(10) NOT NULL,
                `player_ip` varchar(50),
                `player_nation` varchar(100),
                PRIMARY KEY (`player_login`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=MyISAM;";
        $this->db->execute($q);
    }

    public function createMapTable()
    {
        $q = "CREATE TABLE IF NOT EXISTS `exp_maps` (
                `challenge_id` MEDIUMINT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `challenge_uid` VARCHAR( 27 ) NOT NULL ,
                `challenge_name` VARCHAR( 300 ) NOT NULL ,
                `challenge_nameStripped` VARCHAR( 100 ) NOT NULL ,
                `challenge_file` VARCHAR( 200 ) NOT NULL ,
                `challenge_author` VARCHAR( 30 ) NOT NULL ,
                `challenge_environment` VARCHAR( 15 ) NOT NULL,

                `challenge_mood` VARCHAR( 50 ) NOT NULL,
                `challenge_bronzeTime` INT( 10 ) NOT NULL,
                `challenge_silverTime` INT( 10 ) NOT NULL,
                `challenge_goldTime` INT( 10 ) NOT NULL,
                `challenge_authorTime` INT( 10 ) NOT NULL,
                `challenge_copperPrice` INT( 10 ) NOT NULL,
                `challenge_lapRace` INT( 3 ) NOT NULL,
                `challenge_nbLaps` INT( 3 ) NOT NULL,
                `challenge_nbCheckpoints` INTEGER( 3 ) NOT NULL,
                `challenge_addedby` VARCHAR(200),
                `challenge_addtime` INT(9)
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = MYISAM ;";
        $this->db->execute($q);
    }

    public function getPlayer($login)
    {
        if (isset($this->expStorage->dbPlayers[$login])) {
            return $this->expStorage->dbPlayers[$login];
        }

        $g = "SELECT * FROM `exp_players` WHERE `player_login` = " . $this->db->quote($login) . ";";

        $query = $this->db->execute($g);

        if ($query->recordCount() == 0) {
            return null;
        } else {
            $player = $query->fetchStdObject();

            return $player;
        }
    }

    public function incrementWins($player)
    {
        $q = "UPDATE `exp_players` SET `player_wins` = (`player_wins` + 1) WHERE `player_login` = " . $this->db->quote($player->login) . ";";
        $this->db->execute($q);
        if ($this->config->showWins) {
            $q = "SELECT `player_wins` FROM `exp_players` WHERE `player_login` = " . $this->db->quote($player->login) . ";";
            $query = $this->db->execute($q);

            $data = $query->fetchStdObject();
            $w = $data->player_wins;

            $msg_pub = eXpGetMessage('#rank#Congratulations to #variable#%1$s#rank# for their #variable#%2$s#rank# win!');
            $msg_self = eXpGetMessage('#rank#Congratulations for your #variable#%1$s#rank# win!');

            $wins = $this->numberize($w);
            if ($w <= 100 && $w % 10 == 0) {
                $this->eXpChatSendServerMessage($msg_pub, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, "wosnm"), $wins));
            } elseif ($w % 25 == 0) {
                $this->eXpChatSendServerMessage($msg_pub, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, "wosnm"), $wins));
            } else {
                $this->eXpChatSendServerMessage($msg_self, $player->login, array($wins));
            }
        }
    }

    public function numberize($num)
    {
        if ($num >= 10 && $num <= 20) {
            $num = $num . 'th';
        } elseif (substr($num, -1) == 1) {
            $num = $num . 'st';
        } elseif (substr($num, -1) == 2) {
            $num = $num . 'nd';
        } elseif (substr($num, -1) == 3) {
            $num = $num . 'rd';
        } else {
            $num = $num . 'th';
        }

        return $num;
    }

    public function showDbMaintenance($login)
    {
        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::SERVER_DATABASE)) {
            $window = Gui\Windows\Maintainance::Create($login);
            $window->init($this->db);
            $window->setTitle(__('Database Maintenance'));
            $window->centerOnScreen();
            $window->setSize(160, 100);

            $window->show();
        }
    }

    public function showBackupRestore($login)
    {
        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::SERVER_DATABASE)) {
            $window = Gui\Windows\BackupRestore::Create($login);
            $window->init($this->db);
            $window->setTitle(__('Database Backup and Restore'));
            $window->centerOnScreen();
            $window->setSize(160, 100);
            $window->show();
        }
    }

    public function exportToSql($login, $inputboxes = "")
    {
        if (empty($inputboxes['filename'])) {
            if ($login !== null) {
                $this->connection->chatSendServerMessage("No backup filename given, canceling backup!", $login);
            }
            return false;
        }

        if (!is_dir("./backup")) {
            if (!mkdir("./backup", 0777)) {
                if ($login !== null) {
                    $this->connection->chatSendServerMessage("Error while creating backup folder", $login);
                }
                return false;
            }
        }

        $fileName = "./backup/" . $inputboxes['filename'] . ".sql";

        if (file_exists($fileName)) {
            if ($login !== null) {
                $this->connection->chatSendServerMessage("Backup file already exists, canceling backup!", $login);
            }
            return false;
        }

        $fileHandler = fopen($fileName, "wb");
        if ($login !== null) {
            $this->connection->chatSendServerMessage("Creating database backup...", $login);
        }

        $dbconfig = \ManiaLive\Database\Config::getInstance();
        $dbName = $dbconfig->database;

        $tables = $this->db->execute('SHOW TABLES in `' . $dbName . '`;')->fetchArrayOfRow();

        foreach ($tables as $table) {
            $create = $this->db->execute('SHOW CREATE TABLE `' . $table[0] . '`;')->fetchAssoc();

            if (fwrite($fileHandler, "-- --------------------------------------------------------\n\n") === false) {
                throw new \Exception("Writting to file failed!", 4);
            }
            if (fwrite($fileHandler, "--\n-- Table structure for table `" . $table[0] . "`\n--\n\n") === false) {
                throw new \Exception("Writting to file failed!", 4);
            }
            if (fwrite($fileHandler, "DROP TABLE IF EXISTS `" . $table[0] . "`;\n\n") === false) {
                throw new \Exception("Writting to file failed!", 4);
            }
            if (fwrite($fileHandler, $create['Create Table'] . ";\n\n") === false) {
                throw new \Exception("Writting to file failed!", 4);
            }
            if (fwrite($fileHandler, "--\n-- Dumping data for table `" . $table[0] . "`\n--\n\n") === false) {
                throw new \Exception("Writting to file failed!", 4);
            }
            
            $data = $this->db->execute("SELECT * FROM `" . $table[0] . "`;")->fetchArrayOfRow();
            foreach ($data as $row) {
                $vals = array();
                foreach ($row as $val) {
                    $vals[] = is_null($val) ? "NULL" : $this->db->quote($val);
                }
                if (fwrite($fileHandler, "INSERT INTO `" . $table[0] . "` VALUES(" . implode(", ", $vals) . ");\n") === false) {
                    throw new \Exception("Writting to file failed!", 4);
                }
            }
            if (fwrite($fileHandler, "\n") === false) {
                throw new \Exception("Writting to file failed!", 4);
            }
        }

        fclose($fileHandler);

        if ($login !== null) {
            $this->connection->chatSendServerMessage("Backup Complete!", $login);
            Gui\Windows\BackupRestore::erase($login);
            $this->showBackupRestore($login);
        }
    }
}
