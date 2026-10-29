<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

class ServerStatistics extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /** @var Window */
    private $statsWindow;

    /** @var Window */
    private $plotterWindow;
    private $startTime;
    private $ellapsed = 0;
    public $nbPlayerMax = 0;
    public $nbSpecMax = 0;
    private $players = array();
    private $spectators = array();

    /** @var Stats\StatsWindows */
    private $metrics;

    public function eXpOnInit()
    {
        global $lang;
        //The Database plugin is needed.
        $this->addDependency(new \ManiaLive\PluginHandler\Dependency("\\ManiaLivePlugins\\eXpansion\\Database\\Database"));

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->metrics = new Stats\StatsWindows();
        } else {
            $this->metrics = new Stats\StatsLinux();
        }

        $this->startTime = time();
    }

    public function eXpOnLoad()
    {
        parent::eXpOnLoad();
        $this->enableDedicatedEvents();
    }

    public function eXpOnReady()
    {
        parent::eXpOnReady();
        $this->enableTickerEvent();

        $this->registerManialinkCallback('showStats');
        $this->registerManialinkCallback('showPlayers');
        $this->registerManialinkCallback('showMemory');
        $this->registerManialinkCallback('showCpu');

        $this->registerChatCommand("serverstat", "showStats", 0, true);

        $this->statsWindow = new Window("ServerStatistics\Gui\Windows\StatsWindow.xml");
        $this->statsWindow->setName("Server Statistics");
        $this->statsWindow->setSize(85, 72);

        $this->plotterWindow = new Window("ServerStatistics\Gui\Windows\PlotterWindow.xml");
        $this->plotterWindow->setName("Plotter");
        $this->plotterWindow->setSize(170, 110);

        try {
            $this->enableDatabase();
        } catch (\Exception $e) {
            $this->dumpException("Error while establishing MySQL connection!", $e);
            exit(1);
        }

        if (!$this->db->tableExists("exp_server_stats")) {
            $q = "CREATE TABLE `exp_server_stats` (
          `server_login` VARCHAR( 30 ) NOT NULL,
          `server_gamemode` INT( 2 ) NOT NULL,
          `server_nbPlayers` INT( 3 ) NOT NULL,
          `server_nbSpec` INT( 3 ) NOT NULL,
          `server_mlRunTime` INT( 9 ) NOT NULL,
          `server_upTime` INT( 9 ) NOT NULL,
          `server_load` FLOAT(6,4) NOT NULL,
          `server_ramTotal` BIGINT( 15 ) NOT NULL,
          `server_ramFree` BIGINT( 15 ) NOT NULL,
          `server_phpRamUsage` BIGINT( 15 ) NOT NULL,
          `server_updateDate` INT( 9 ) NOT NULL,
          KEY(`server_login`)
          ) CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = MYISAM ;";
            $this->db->execute($q);
        }

        foreach ($this->storage->players as $player) {
            if ($player->isConnected) {
                $this->players[$player->login] = $player->login;
            }
        }
        foreach ($this->storage->spectators as $player) {
            if ($player->isConnected) {
                $this->spectators[$player->login] = $player->login;
            }
        }
        $this->nbSpecMax = sizeof($this->spectators);
        $this->nbPlayerMax = sizeof($this->players);
    }

    public function onTick()
    {

        if ($this->ellapsed % 120 == 0) {
            $memory = $this->metrics->getFreeMemory();
            $q = 'INSERT INTO `exp_server_stats` (`server_login`, `server_gamemode`, `server_nbPlayers`, `server_nbSpec`
          ,`server_mlRunTime`, `server_upTime`, `server_load`, `server_ramTotal`, `server_ramFree`
          , `server_phpRamUsage`, `server_updateDate` )
          VALUES(' . $this->db->quote($this->storage->serverLogin) . ',
          ' . $this->db->quote($this->storage->gameInfos->gameMode) . ',
          ' . $this->db->quote($this->nbPlayerMax) . ',
          ' . $this->db->quote($this->nbSpecMax) . ',
          ' . $this->db->quote(time() - $this->startTime) . ',
          ' . $this->db->quote($this->metrics->getUptime()) . ',
          ' . $this->db->quote($this->metrics->getAvgLoad()) . ',
          ' . $this->db->quote($memory->total) . ',
          ' . $this->db->quote($memory->free) . ',
          ' . $this->db->quote(memory_get_usage()) . ',
          ' . $this->db->quote(time()) . '
          )';

            $this->nbPlayerMax = sizeof($this->players);
            $this->nbSpecMax = sizeof($this->spectators);

            $this->db->execute($q);

            $startTime = (time() - (24 * 60 * 60));
            $this->db->execute("DELETE FROM `exp_server_stats` WHERE `server_updateDate` < $startTime");
        }

        $this->ellapsed = ($this->ellapsed + 1) % 120;
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($isSpectator) {
            $this->spectators[$login] = $login;
            if (sizeof($this->spectators) > $this->nbSpecMax) {
                $this->nbSpecMax = sizeof($this->spectators);
            }
        } else {
            $this->players[$login] = $login;
            if (sizeof($this->players) > $this->nbPlayerMax) {
                $this->nbPlayerMax = sizeof($this->players);
            }
        }
    }

    public function showStats($login, $params = null)
    {
        /** @var \ManiaLivePlugins\eXpansion\Gui\Formaters\LongDate $formatter */
        $formatter = \ManiaLivePlugins\eXpansion\Gui\Formaters\LongDate::getInstance();

        $avgPlayer = 'unknown';
        $avgSpec = 'unknown';
        $sql = 'SELECT AVG(server_nbPlayers) as avgPlayer, AVG(server_nbSpec) as avgSpec FROM exp_server_stats WHERE server_login = ' . $this->db->quote($this->storage->serverLogin);
        foreach ($this->db->execute($sql)->fetchArrayOfObject() as $r) {
            $avgPlayer = $r->avgPlayer;
            $avgSpec = $r->avgSpec;
        }

        $nbPlayer = 'unknown';
        $nbNation = 'unknown';
        $totalPlayersTimes = 'unknown';
        $sql = 'SELECT COUNT(*) as nbPlayer, COUNT(DISTINCT player_nation) as nbNation, SUM(player_timeplayed) as totalPlayersTimes FROM exp_players';
        foreach ($this->db->execute($sql)->fetchArrayOfObject() as $r) {
            $nbPlayer = $r->nbPlayer;
            $nbNation = $r->nbNation;
            $totalPlayersTimes = $formatter->format($r->totalPlayersTimes);
        }

        $this->statsWindow->setTitle('Welcome to : ' . \ManiaLivePlugins\eXpansion\Gui\Gui::fixString($this->storage->server->name));
        $this->statsWindow->setParam("comment",        $this->statsWindow->handleSpecialChars(\ManiaLivePlugins\eXpansion\Gui\Gui::fixString($this->storage->server->comment, true)));
        $this->statsWindow->setParam("upTimeDedi",     $this->statsWindow->handleSpecialChars($formatter->format($this->storage->getDediUpTime())));
        $this->statsWindow->setParam("upTime",         $this->statsWindow->handleSpecialChars($formatter->format($this->storage->getExpansionUpTime())));
        $this->statsWindow->setParam("mapCount",       $this->statsWindow->handleSpecialChars(sizeof($this->storage->maps)));
        $this->statsWindow->setParam("maxPlayers",     $this->statsWindow->handleSpecialChars(strval($this->storage->server->currentMaxPlayers)));
        $this->statsWindow->setParam("avgPlayer",      $this->statsWindow->handleSpecialChars($avgPlayer));
        $this->statsWindow->setParam("maxSpectators",  $this->statsWindow->handleSpecialChars(strval($this->storage->server->currentMaxSpectators)));
        $this->statsWindow->setParam("avgSpec",        $this->statsWindow->handleSpecialChars($avgSpec));
        $this->statsWindow->setParam("ladderLimit",    $this->statsWindow->handleSpecialChars($this->storage->server->ladderServerLimitMin . ' - ' . $this->storage->server->ladderServerLimitMax));
        $this->statsWindow->setParam("visitedBy",      $this->statsWindow->handleSpecialChars($this->statsWindow->addLang('Visited by %s players from %s zones', array($nbPlayer, $nbNation))));
        $this->statsWindow->setParam("playedTogether", $this->statsWindow->handleSpecialChars($this->statsWindow->addLang('Who together played %s', array($totalPlayersTimes))));
        $this->statsWindow->show($login);
    }

    public function showPlayers($login)
    {
        $startTime = (time() - (24 * 60 * 60));

        $datas = $this->db->execute(
            "SELECT `server_nbPlayers` as players, server_nbSpec as specs, "
            . " server_updateDate as date "
            . " FROM exp_server_stats "
            . " WHERE server_updateDate > " . $startTime
            . "     AND server_login = " . $this->db->quote($this->storage->serverLogin)
            . " ORDER BY `server_updateDate` ASC"
        )->fetchArrayOfObject();

        $i = 0;
        $out = array();
        $max = 0;

        foreach ($datas as $data) {
            while ($startTime + ($i * 120) - 60 < $data->date) {
                $out[0][] = 0;
                $out[1][] = 0;
                $i++;
            }
            $i++;
            $out[0][] = $data->players;
            $out[1][] = $data->specs;
            if ($max < $data->players) {
                $max = $data->players;
            }
            if ($max < $data->specs) {
                $max = $data->specs;
            }
        }

        $this->showPlotter($login, "Players", $out, (((int)($max / 5)) + 1) * 5, $this->getXDateLabels($startTime), null, "00f", "f00");
    }

    public function showMemory($login)
    {
        $startTime = (time() - (24 * 60 * 60));

        $datas = $this->db->execute(
            "SELECT `server_ramTotal` as total, "
            . "`server_ramFree` as free, "
            . "`server_phpRamUsage` as phpram, "
            . "server_updateDate as date "
            . " FROM exp_server_stats "
            . " WHERE server_updateDate > " . $startTime
            . "     AND server_login = " . $this->db->quote($this->storage->serverLogin)
            . " ORDER BY `server_updateDate` ASC"
        )->fetchArrayOfObject();

        $out = array();
        $i = 0;
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit != -1) {
            if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
                if ($matches[2] == 'M') {
                    $memory_limit = $matches[1] * 1024 * 1024;
                } elseif ($matches[2] == 'K') {
                    $memory_limit = $matches[1] * 1024;
                }
            }
        }

        foreach ($datas as $data) {
            if ($memory_limit == -1) {
                $memory_limit = $data->total;
            }
            while ($startTime + ($i * 120) - 60 < $data->date) {
                $out[0][] = $memory_limit;
                $out[1][] = 0;
                $i++;
            }
            $i++;
            $out[0][] = $memory_limit;
            $out[1][] = $data->phpram;
        }

        $labels = array();
        for ($i = 0; $i < 5; $i++) {
            $labels[] = ((int)(($memory_limit - ($i * ($memory_limit / 5))) / (1024 * 1024))) . "M";
        }

        $this->showPlotter($login, "Memory usage", $out, $memory_limit, $this->getXDateLabels($startTime), $labels, "f90", "f00");
    }

    public function showCpu($login)
    {
        $startTime = (time() - (24 * 60 * 60));

        $datas = $this->db->execute(
            "SELECT `server_load` as cpuload, server_updateDate as date"
            . " FROM exp_server_stats "
            . " WHERE server_updateDate > " . $startTime
            . "     AND server_login = " . $this->db->quote($this->storage->serverLogin)
            . " ORDER BY `server_updateDate` ASC"
        )->fetchArrayOfObject();

        $out = array();

        $i = 0;
        foreach ($datas as $data) {
            while ($startTime + ($i * 120) - 60 < $data->date) {
                $out[0][] = 0;
                $i++;
            }
            $i++;
            $out[0][] = $data->cpuload;
        }

        $this->showPlotter($login, "Cpu usage", $out, 100, $this->getXDateLabels($startTime), null, "f00", null);
    }

    private function showPlotter($login, $title, $datas, $limitY, $labelsX, $labelsY, $color0, $color1)
    {
        $limitY  = (float)$limitY;
        $labelsX = is_array($labelsX) ? $labelsX : array();
        $nbX     = count($labelsX);

        $xLabels = array();
        foreach ($labelsX as $i => $label) {
            $xLabels[] = array(
                'posX' => ($i * 195 / $nbX) + 5,
                'text' => $this->plotterWindow->handleSpecialChars($label),
            );
        }

        $yLabels = array();
        if (is_array($labelsY) && count($labelsY) > 0) {
            $nbY = count($labelsY);
            foreach ($labelsY as $i => $label) {
                $yLabels[] = array(
                    'posY' => (-$i * 95 / $nbY) - 1,
                    'text' => $this->plotterWindow->handleSpecialChars($label),
                );
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $yLabels[] = array(
                    'posY' => (-$i * 95 / 5) - 1,
                    'text' => (int)((5 - $i) * ($limitY / 5)),
                );
            }
        }

        $curves = array();
        foreach ((is_array($datas) ? $datas : array()) as $series) {
            $points = array();
            foreach ($series as $x => $val) {
                $points[] = array('x' => $x, 'y' => $val);
            }
            if (!empty($points)) {
                $curves[] = array('color' => empty($curves) ? $color0 : $color1, 'points' => $points);
            }
        }

        $gridLines = array();
        $step      = ($limitY > 0) ? (pow(10, round(log($limitY) / 2.303)) / 5) : 1;
        for ($index = 0; $index < $limitY; $index += $step) {
            $gridLines[] = $index;
        }

        $this->plotterWindow->setTitle($title);
        $this->plotterWindow->setParam("labelsX", $xLabels);
        $this->plotterWindow->setParam("labelsY", $yLabels);
        $this->plotterWindow->setParam("limitY", $limitY);
        $this->plotterWindow->setParam("curves", $curves);
        $this->plotterWindow->setParam("gridLines", $gridLines);
        $this->plotterWindow->setParam("useStyle", substr($this->storage->version->build, 0, 4) >= 2017);
        $this->plotterWindow->show($login);
    }

    private function getXDateLabels($startTime)
    {
        $labels = array();
        for ($i = 0; $i < 4; $i++) {
            $labels[] = date("Y-m-d H:i", $startTime + ($i * ((24 * 60 * 60) / 3)));
        }

        return $labels;
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        $this->removePlayer($login);
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {

        $this->players = array();
        foreach ($this->storage->players as $player) {
            if ($player->isConnected) {
                $this->players[$player->login] = $player->login;
            }
        }

        $this->spectators = array();
        foreach ($this->storage->spectators as $player) {
            if ($player->isConnected) {
                $this->spectators[$player->login] = $player->login;
            }
        }
        $this->nbPlayerMax = sizeof($this->players);
        $this->nbSpecMax = sizeof($this->spectators);
    }

    private function removePlayer($login)
    {
        if (array_key_exists($login, $this->spectators)) {
            unset($this->spectators[$login]);
        }
        if (array_key_exists($login, $this->players)) {
            unset($this->players[$login]);
        }
    }

    public function onPlayerInfoChanged($playerInfo)
    {
        $player = \Maniaplanet\DedicatedServer\Structures\PlayerInfo::fromArray($playerInfo);
        $login = $player->login;

        $this->removePlayer($player->login);

        if ($player->pureSpectator) {
            $this->spectators[$login] = $login;
        } else {
            $this->players[$login] = $login;
        }
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();

        if ($this->statsWindow instanceof Window) {
            $this->statsWindow->erase();
        }
        $this->statsWindow = null;

        if ($this->plotterWindow instanceof Window) {
            $this->plotterWindow->erase();
        }
        $this->plotterWindow = null;
    }
}
