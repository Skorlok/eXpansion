<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LiveRankings;

use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Widgets_LocalRecords;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_LiveRankings extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $raceOn = true;
    private $roundPoints;
    /** @var Config */
    private $config;

    /** @var Script */
    private $trayScript;
    /** @var Script */
    private $cpScript;
    /** @var Script */
    private $cpScript2;
    /** @var Script */
    private $enduranceScript;
    /** @var Script */
    private $timesScript;

    /** @var Widget */
    private $cpWidget;
    /** @var Widget */
    private $cpWidget2;
    /** @var Widget */
    private $enduranceWidget;
    /** @var Widget */
    private $enduranceWidget2;
    /** @var Widget */
    private $timesWidget;
    /** @var Widget */
    private $timesWidget2;

    /** @var Widget the widgets of the current gamemode, among the ones above */
    private $widget;
    /** @var Widget */
    private $widget2;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();

        Dispatcher::register(\ManiaLivePlugins\eXpansion\Endurance\Events\Event::getClass(), $this);

        $this->enableDedicatedEvents();

        $libScript = new Script('Gui/Script_libraries/TimeToText');

        $this->trayScript = new Script("Gui/Scripts/NewTray");

        $this->cpScript = new Script("Widgets_LiveRankings/Gui/Scripts/CpPositions");
        $this->cpScript->setParam('varName', 'LiveTime1');
        // (workaround when we have 2 layers)
        $this->cpScript2 = new Script("Widgets_LiveRankings/Gui/Scripts/CpPositions");
        $this->cpScript2->setParam('varName', 'LiveTime2');

        $this->enduranceScript = new Script("Endurance/Gui/Scripts/PlayerFinish");
        $this->enduranceScript->setParam("nbScores", 50000);
        $this->enduranceScript->setParam('varName', 'Liverankings');

        $this->timesScript = new Script("Widgets_LocalRecords/Gui/Scripts/PlayerFinish");
        $this->timesScript->setParam("nbRecord", 255);
        $this->timesScript->setParam('varName', 'Liverankings');

        $this->cpWidget = new Widget("Widgets_LiveRankings\Gui\Widgets\LiveRanking.xml");
        $this->cpWidget->setName("Live Rankings Panel");
        $this->cpWidget->setLayer("normal");
        $this->cpWidget->registerScript($libScript);
        $this->cpWidget->registerScript($this->cpScript);
        $this->cpWidget->registerScript($this->trayScript);

        $this->cpWidget2 = new Widget("Widgets_LiveRankings\Gui\Widgets\LiveRanking.xml");
        $this->cpWidget2->setName("Live Rankings Panel");
        $this->cpWidget2->setLayer("scorestable");
        $this->cpWidget2->registerScript($libScript);
        $this->cpWidget2->registerScript($this->cpScript2);
        $this->cpWidget2->registerScript($this->trayScript);

        $this->enduranceWidget = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->enduranceWidget->setName("Live Rankings Panel");
        $this->enduranceWidget->setLayer("normal");
        $this->enduranceWidget->setLayer("scorestable");
        $this->enduranceWidget->registerScript($libScript);
        $this->enduranceWidget->registerScript($this->enduranceScript);
        $this->enduranceWidget->registerScript($this->trayScript);

        $this->enduranceWidget2 = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->enduranceWidget2->setName("Live Rankings Panel");
        $this->enduranceWidget2->setLayer("scorestable");
        $this->enduranceWidget2->registerScript($libScript);
        $this->enduranceWidget2->registerScript($this->enduranceScript);
        $this->enduranceWidget2->registerScript($this->trayScript);

        $this->timesWidget = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->timesWidget->setName("Live Rankings Panel");
        $this->timesWidget->setLayer("normal");
        $this->timesWidget->registerScript($libScript);
        $this->timesWidget->registerScript($this->timesScript);
        $this->timesWidget->registerScript($this->trayScript);

        $this->timesWidget2 = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->timesWidget2->setName("Live Rankings Panel");
        $this->timesWidget2->setLayer("scorestable");
        $this->timesWidget2->registerScript($libScript);
        $this->timesWidget2->registerScript($this->timesScript);
        $this->timesWidget2->registerScript($this->trayScript);

        $this->updateLivePanel();

        $this->getRoundsPoints();

        $this->enableScriptEvents("Maniaplanet.StartRound_Start");
    }

	public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Maniaplanet.StartRound_Start":
                $this->onBeginRound();
                break;
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            $this->updateLivePanel();
        }
    }

    public function updateLivePanel($login = null, $update = false)
    {
        $isEndurance = $this->storage->getCleanGamemodeName() == "endurocup";

        if ($update) {
            if ($isEndurance) {
                $data = $this->getEnduranceData();
            } else {
                $data = $this->getTimesData();
            }

            $xml = '<manialink id="liveranking_updater" version="2" name="liveranking_updater">';
            $xml .= '<script><!--';
            $xml .= 'main () {';
            $xml .= 'declare Integer[Text] playerTimesLiverankings for UI = Integer[Text];';
            $xml .= 'playerTimesLiverankings = ' . $data[0] . ';';
            $xml .= 'declare Text[Text] playerNickNameLiverankings for UI = Text[Text];';
            $xml .= 'playerNickNameLiverankings = ' . $data[1] . ';';
            $xml .= 'declare Boolean needUpdateLiverankings for UI = True;';
            $xml .= 'needUpdateLiverankings = True;';
            $xml .= '}';
            $xml .= '--></script>';
            $xml .= '</manialink>';
            $this->connection->sendDisplayManialinkPage($login, $xml);
            return;
        }

        /** @var \ManiaLivePlugins\eXpansion\Gui\Config $gui */
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();

        //gamemode specific settings
        if ($isEndurance) {
            $posX = $this->config->liveRankingPanel_PosX_Endurance;
            $posY = $this->config->liveRankingPanel_PosY_Endurance;
            $nbF = $this->config->liveRankingPanel_nbFields_Endurance;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Endurance;
        } elseif ($gamemode == GameInfos::GAMEMODE_LAPS) {
            $posX = $this->config->liveRankingPanel_PosX_Laps;
            $posY = $this->config->liveRankingPanel_PosY_Laps;
            $nbF = $this->config->liveRankingPanel_nbFields_Laps;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Laps;
        } elseif ($gamemode == GameInfos::GAMEMODE_ROUNDS) {
            $posX = $this->config->liveRankingPanel_PosX_Rounds;
            $posY = $this->config->liveRankingPanel_PosY_Rounds;
            $nbF = $this->config->liveRankingPanel_nbFields_Rounds;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Rounds;
        } elseif ($gamemode == GameInfos::GAMEMODE_TEAM) {
            $posX = $this->config->liveRankingPanel_PosX_Team;
            $posY = $this->config->liveRankingPanel_PosY_Team;
            $nbF = $this->config->liveRankingPanel_nbFields_Team;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Team;
        } elseif ($gamemode == GameInfos::GAMEMODE_CUP) {
            $posX = $this->config->liveRankingPanel_PosX_Cup;
            $posY = $this->config->liveRankingPanel_PosY_Cup;
            $nbF = $this->config->liveRankingPanel_nbFields_Cup;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Cup;
        } else {
            $posX = $this->config->liveRankingPanel_PosX_Default;
            $posY = $this->config->liveRankingPanel_PosY_Default;
            $nbF = $this->config->liveRankingPanel_nbFields_Default;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Default;
        }

        $sizeX = 42;
        $sizeY = 3 + $nbF * 4;

        $this->trayScript->setParam("sizeX", $this->trayScript->getNumber($sizeX));
        $this->trayScript->setParam("sizeY", $this->trayScript->getNumber($sizeY));

        $action = null;
        if ($isEndurance) {
            $data = $this->getEnduranceData();
            $this->enduranceScript->setParam("nbFields", $nbF);
            $this->enduranceScript->setParam("nbFirstFields", $nbFF);
            $this->enduranceScript->setParam("playerScores", $data[0]);
            $this->enduranceScript->setParam("playerNicks", $data[1]);

            $this->widget = $this->enduranceWidget;
            $this->widget2 = $this->enduranceWidget2;
            $action = Endurance::$openScoresAction;
        } elseif ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_LAPS) {
            foreach ($this->getCpParams($nbF, $nbFF) as $key => $value) {
                $this->cpScript->setParam($key, $value);
                $this->cpScript2->setParam($key, $value);
            }

            $this->widget = $this->cpWidget;
            $this->widget2 = $this->cpWidget2;
        } else {
            $data = $this->getTimesData();
            $this->timesScript->setParam("nbFields", $nbF);
            $this->timesScript->setParam("nbFirstFields", $nbFF);
            $this->timesScript->setParam("playerTimes", $data[0]);
            $this->timesScript->setParam("playerNicks", $data[1]);

            $this->widget = $this->timesWidget;
            $this->widget2 = $this->timesWidget2;
        }

        $teams = $this->storage->getTeamsDisplay(array('$00f', '$wBlue Team', '$f00', '$wRed Team'));

        $team1Name = $teams[0]["name"] . ' : ';
        $team1Color = str_replace('$', "", $teams[0]["color"]);
        $team2Name = $teams[1]["name"] . ' : ';
        $team2Color = str_replace('$', "", $teams[1]["color"]);

        $showTeams = $gamemode == GameInfos::GAMEMODE_TEAM;

        $widgets = array($this->widget);
        if (!$gui->disablePersonalHud) {
            $widgets[] = $this->widget2;
        }

        foreach ($widgets as $widget) {
            $widget->setPosition($posX, $posY, 0);
            $widget->setSize($sizeX, $sizeY);
            Widgets_LocalRecords::setPanelParams($widget, $sizeX, $nbF, "Live Rankings", $action);
            $widget->setParam("showTeams", $showTeams);
            if ($showTeams) {
                $widget->setParam("teamsPosY",  -4 * $nbF);
                $widget->setParam("team1Name",  $widget->handleSpecialChars($team1Name));
                $widget->setParam("team1Color", $widget->handleSpecialChars($team1Color));
                $widget->setParam("team2Name",  $widget->handleSpecialChars($team2Name));
                $widget->setParam("team2Color", $widget->handleSpecialChars($team2Color));
            }
            $widget->show($login);
        }
    }

    private function getCpParams($nbField, $nbFirstField)
    {
        $params = array(
            "nbFields" => $nbField,
            "nbFirstFields" => $nbFirstField,
            "totalCp" => $this->storage->currentMap->nbCheckpoints,
            "playerTimes" => 'Integer[Text][Integer]',
            "nickNames" => 'Text[Text]',
            "bestCps" => 'Integer[Integer]',
            "maxCp" => -1,
            "givePoints" => "True",
            "points" => "Integer[Integer]",
            "maxPoint" => 10,
            "nbLaps" => 1,
            "isLaps" => "False",
            "isTeam" => "False",
            "team1Color" => '$3AF',
            "team2Color" => '$D00',
            "playerTeams" => "Integer[Text]",
        );

        $nbLaps = $this->storage->currentMap->nbLaps;
        $ScriptSettings = $this->connection->getModeScriptSettings();
        if (array_key_exists("S_ForceLapsNb", $ScriptSettings) && $ScriptSettings['S_ForceLapsNb'] != -1) {
            $nbLaps = $ScriptSettings['S_ForceLapsNb'];
        }
        if (isset($ScriptSettings['S_MaxPointsPerRound'])) {
            $params["maxPoint"] = $ScriptSettings['S_MaxPointsPerRound'];
        }

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        if ($gamemode == GameInfos::GAMEMODE_LAPS || $this->storage->currentMap->nbLaps > 1) {
            $params["isLaps"] = "True";
            $params["nbLaps"] = $nbLaps;
        }

        if ($gamemode == GameInfos::GAMEMODE_LAPS) {
            $params["givePoints"] = "False";
        }

        if ($gamemode == GameInfos::GAMEMODE_TEAM) {
            $params["isTeam"] = "True";

            $teamsDisplay = $this->storage->getTeamsDisplay(array('$3AF', 'Blue', '$D00', 'Red'));
            $params["team1Color"] = $teamsDisplay[0]["color"];
            $params["team2Color"] = $teamsDisplay[1]["color"];
        }

        if (!empty($this->roundPoints)) {
            $params["points"] = "[" . implode(",", $this->roundPoints) . "]";
        }

        if (!self::$raceOn) {
            return $params;
        }


        $nbCheckpoints = array();
        $playerCps = array();
        $playerNickNames = array();
        $bestCps = array();
        $biggestCp = -1;

        foreach (Core::$playerInfo as $login => $player) {
            $lastCpIndex = count($player->checkpoints) - 1;
            $playerNickNames[$player->login] = $player->nickName;

            if ($player->isPlaying && $lastCpIndex >= 0 && isset($player->checkpoints[$lastCpIndex]) && $player->checkpoints[$lastCpIndex] > 0) {

                if ($lastCpIndex > $biggestCp) {
                    $biggestCp = $lastCpIndex;
                }

                $lastCpTime = $player->checkpoints[$lastCpIndex];
                $player = $this->storage->getPlayerObject($login);
                $playerCps[$lastCpIndex][$login] = $lastCpTime;
                $playerTeams[$login] = $player->teamId;
            }
        }

        $newPlayerCps = array();
        foreach ($playerCps as $coIndex => $cpsTimes) {
            arsort($cpsTimes);
            $newPlayerCps[$coIndex] = $cpsTimes;
        }

        $playerTimes = "[";
        $NickNames = "[";
        $teams = "[";

        $index = 1;
        foreach ($playerNickNames as $login => $nickname) {
            if ($index > 1) {
                $NickNames .= ', ';
            }
            $NickNames .= '"' . $login . '"=>"' . Gui::fixString2(Formatting::stripLinks($nickname)) . '"';
            $index++;
        }
        $NickNames .= "]";

        $cpCount = 0;
        $teamCont = 0;
        foreach ($newPlayerCps as $cpIndex => $cpTimes) {
            if ($cpCount != 0) {
                $playerTimes .= ", ";
            }
            $playerTimes .= $cpIndex . "=>[";

            $cCount = 0;
            $nbCheckpoints[$cpIndex] = 0;
            foreach ($cpTimes as $login => $score) {
                if ($cCount != 0) {
                    $playerTimes .= ", ";
                }
                if ($teamCont != 0) {
                    $teams .= ", ";
                }
                $playerTimes .= '"' . $login . "\"=>" . $score;
                $teams .= '"' . $login . "\"=>" . ($playerTeams[$login] == 1 ? 0 : 1);
                $nbCheckpoints[$cpIndex]++;
                $cCount++;
                $teamCont++;

                if (!isset($bestCps[$cpIndex]) || $score < $bestCps[$cpIndex]) {
                    $bestCps[$cpIndex] = $score;
                }
            }

            $playerTimes .= "]";
            $cpCount++;

        }
        $playerTimes .= "]";
        $teams .= "]";

        $bestCpsText = '';
        foreach ($bestCps as $cpIndex => $time) {
            if ($bestCpsText != "") {
                $bestCpsText .= ', ';
            }
            $bestCpsText .= $cpIndex . '=>' . $time;
        }

        $bestCps = '[' . $bestCpsText . ']';


        if ($teamCont != 0) {
            $params["playerTeams"] = $teams;
        }

        if (!empty($newPlayerCps)) {
            $params["playerTimes"] = $playerTimes;
            $params["nickNames"] = $NickNames;
            $params["maxCp"] = $biggestCp + 1;
            $params["bestCps"] = $bestCps;
        }

        return $params;
    }

    private function getEnduranceData()
    {
        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (Endurance::$enduro_total_points as $player_login => $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . $player_login . '"=>' . $record['points'];
            $nickData .= '"' . $player_login . '"=>"' . Gui::fixString2(Formatting::stripLinks($record['name'])) . '"';
            $index++;
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        return array($recsData, $nickData);
    }

    private function getTimesData()
    {
        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (Core::$rankings as $player) {
            if (!empty($player->bestTime) && $player->bestTime > 0) {
                if ($index > 1) {
                    $recsData .= ', ';
                    $nickData .= ', ';
                }
                $recsData .= '"' . $player->login . '"=>' . $player->bestTime;
                $nickData .= '"' . $player->login . '"=>"' . Gui::fixString2(Formatting::stripLinks($player->nickName)) . '"';
                $index++;
            }
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        return array($recsData, $nickData);
    }

    public function hideLivePanel()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        if ($this->widget2 instanceof Widget) {
            $this->widget2->erase();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        self::$raceOn = false;
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->hideLivePanel();
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        $this->hideLivePanel();
    }

    public function onScoresCalculated($scores)
    {
        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_LAPS) {
            return;
        }

        if (self::$raceOn == true) {
            $this->updateLivePanel(null, true);
        }
    }

    public function getRoundsPoints()
    {
        $points = $this->connection->getRoundCustomPoints();
        if (empty($points)) {
            $this->roundPoints = array(10, 6, 4, 3, 2, 1);
        } else {
            $this->roundPoints = $points;
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if (self::$raceOn == true) {
            return;
        }

        $this->getRoundsPoints();
        self::$raceOn = false;
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->updateLivePanel();
        }
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        if (self::$raceOn == true) {
            return;
        }

        self::$raceOn = false;
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->updateLivePanel();
        }
        self::$raceOn = true;
    }

    public function onBeginRound()
    {
        //We need to reset the panel for next Round
        self::$raceOn = false;
        $this->getRoundsPoints();
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->updateLivePanel();
        }
        self::$raceOn = true;
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (self::$raceOn == true) {
            $this->updateLivePanel($login);
        }
    }

    public function onEnduranceScoresUpdated($update)
    {
        $this->updateLivePanel(null, $update);
    }

    public function onEndurancePanelHide()
    {
        $this->hideLivePanel();
    }

    public function eXpOnUnload()
    {
        $this->hideLivePanel();
        $this->config = null;
        $this->trayScript = null;
        $this->cpScript = null;
        $this->cpScript2 = null;
        $this->enduranceScript = null;
        $this->timesScript = null;
        $this->cpWidget = null;
        $this->cpWidget2 = null;
        $this->enduranceWidget = null;
        $this->enduranceWidget2 = null;
        $this->timesWidget = null;
        $this->timesWidget2 = null;
        $this->widget = null;
        $this->widget2 = null;

        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Endurance\Events\Event::getClass(), $this);
    }
}
