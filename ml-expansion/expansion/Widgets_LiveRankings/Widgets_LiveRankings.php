<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LiveRankings;

use ManiaLib\Utils\Formatting;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_LiveRankings extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $raceOn = true;
    private $roundPoints;
    private $config;

    private $widget;
    private $widget2;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();

        Dispatcher::register(\ManiaLivePlugins\eXpansion\Endurance\Events\Event::getClass(), $this);

        $this->enableDedicatedEvents();
        $this->updateLivePanel();

        $this->getRoundsPoints();
    
        $this->enableScriptEvents("Maniaplanet.StartRound_Start");
    }
	
	public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Maniaplanet.StartRound_Start":
                $this->onBeginRound(0);
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
        if ($update) {
            $xml = '<manialink id="liveranking_updater" version="2" name="liveranking_updater">';
            $xml .= '<script><!--';
            if ($this->storage->getCleanGamemodeName() == "endurocup") {
                $xml .= $this->getWidgetScriptEndurance(null, null, true);
            } else {
                $xml .= $this->getWidgetScript(null, null, true);
            }
            $xml .= '--></script>';
            $xml .= '</manialink>';
            $this->connection->sendDisplayManialinkPage($login, $xml);
            return;
        }

        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();

        //gamemode specific settings
        if ($this->storage->getCleanGamemodeName() == "endurocup") {
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

        if ($this->widget instanceof Widget) {
            $this->widget->erase($login);
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase($login);
            }
        }

        $sizeX = 42;
        $sizeY = 3 + $nbF * 4;
        
        $trayScript = $this->getTrayScript($sizeX, $nbF);
        if (($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_LAPS) && $this->storage->getCleanGamemodeName() != "endurocup") {
            $widgetScript = $this->getWidgetCpScript($nbF, $nbFF, "normal");
            $widgetScriptScore = $this->getWidgetCpScript($nbF, $nbFF, "scorestable"); // workaround when we have 2 layers
        } else {
            if ($this->storage->getCleanGamemodeName() == "endurocup") {
                $widgetScript = $this->getWidgetScriptEndurance($nbF, $nbFF);
            } else {
                $widgetScript = $this->getWidgetScript($nbF, $nbFF);
            }
        }

        $team1Name = '$wBlue Team : ';
        $team1Color = '00f';
        $team2Name = '$wRed Team : ';
        $team2Color = 'f00';

        if ($gamemode == GameInfos::GAMEMODE_TEAM) {
            $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();
            if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
                $team1Name = $var["team1Name"] . ' : ';
                $team1Color = $var["team1Color"];
            }
            if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
                $team2Name = $var["team2Name"] . ' : ';
                $team2Color = $var["team2Color"];
            }
        }

        
        if (($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_LAPS) && $this->storage->getCleanGamemodeName() != "endurocup") {
            $this->widget = new Widget("Widgets_LiveRankings\Gui\Widgets\LiveRanking.xml");
        } else {
            $this->widget = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        }
        $this->widget->setName("Live Rankings Panel");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($posX, $posY, 0);
        $this->widget->setSize($sizeX, $sizeY);
        $this->widget->setParam("sizeX", $sizeX);
        $this->widget->setParam("nbFields", $nbF);
        $this->widget->setParam("title", "Live Rankings");
        $this->widget->setParam("action", null);
        if ($this->storage->getCleanGamemodeName() == "endurocup") {
            $this->widget->setParam("action", Endurance::$openScoresAction);
        }
        $this->widget->setParam("guiConfig", guiConfig::getInstance());
        $this->widget->setParam("colorParser", ColorParser::getInstance());
        if ($gamemode == GameInfos::GAMEMODE_TEAM) {
            $this->widget->setParam("team1Name", $team1Name);
            $this->widget->setParam("team1Color", $team1Color);
            $this->widget->setParam("team2Name", $team2Name);
            $this->widget->setParam("team2Color", $team2Color);
        }
        $this->widget->registerScript(new Script('Gui/Script_libraries/TimeToText'));
        $this->widget->registerScript(new Script('Gui/Script_libraries/mQuery/TextExt'));
        $this->widget->registerScript($widgetScript);
        $this->widget->registerScript($trayScript);
        $this->widget->show($login);

        /** @var ManiaLivePlugins\eXpansion\Gui\Gui $gui */
        if (!$gui->disablePersonalHud) {
            if (($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_LAPS) && $this->storage->getCleanGamemodeName() != "endurocup") {
                $this->widget2 = new Widget("Widgets_LiveRankings\Gui\Widgets\LiveRanking.xml");
                $this->widget2->registerScript(new Script('Gui/Script_libraries/TimeToText')); // this script is a dependency for the $widgetScriptScore, we need to load it first
                $this->widget2->registerScript($widgetScriptScore);
            } else {
                $this->widget2 = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
                $this->widget2->registerScript(new Script('Gui/Script_libraries/TimeToText'));
                $this->widget2->registerScript($widgetScript);
            }
            $this->widget2->setName("Live Rankings Panel");
            $this->widget2->setLayer("scorestable");
            $this->widget2->setPosition($posX, $posY, 0);
            $this->widget2->setSize($sizeX, $sizeY);
            $this->widget2->setParam("sizeX", $sizeX);
            $this->widget2->setParam("nbFields", $nbF);
            $this->widget2->setParam("title", "Live Rankings");
            $this->widget2->setParam("action", null);
            if ($this->storage->getCleanGamemodeName() == "endurocup") {
                $this->widget2->setParam("action", Endurance::$openScoresAction);
            }
            $this->widget2->setParam("guiConfig", guiConfig::getInstance());
            $this->widget2->setParam("colorParser", ColorParser::getInstance());
            if ($gamemode == GameInfos::GAMEMODE_TEAM) {
                $this->widget2->setParam("team1Name", $team1Name);
                $this->widget2->setParam("team1Color", $team1Color);
                $this->widget2->setParam("team2Name", $team2Name);
                $this->widget2->setParam("team2Color", $team2Color);
            }
            $this->widget2->registerScript(new Script('Gui/Script_libraries/mQuery/TextExt'));
            $this->widget2->registerScript($trayScript);
            $this->widget2->show($login);
        }
    }

    public function getWidgetCpScript($nbField, $nbFirstField, $layer = "normal")
    {
        $script = new Script("Widgets_LiveRankings/Gui/Scripts/CpPositions");
        $script->setParam("nbFields", $nbField);
        $script->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
        $script->setParam("nbFirstFields", $nbFirstField);
        $script->setParam('varName', 'LiveTime1');
        if ($layer == "scorestable") {
            $script->setParam('varName', 'LiveTime2');
        }
        $script->setParam("playerTimes", 'Integer[Text][Integer]');
        $script->setParam("nickNames", 'Text[Text]');
        $script->setParam("bestCps", 'Integer[Integer]');
        $script->setParam("maxCp", -1);

        $script->setParam("givePoints", "True");
        $script->setParam("points", "Integer[Integer]");
        $script->setParam("nbLaps", 1);
        $script->setParam("isLaps", "False");
        $script->setParam("isTeam", "False");
        $script->setParam("team1Color", '$3AF');
        $script->setParam("team2Color", '$D00');
        $script->setParam("playerTeams", "Integer[Text]");


        $teamMaxPoint = 10;
        $ScriptSettings = $this->connection->getModeScriptSettings();
        if (array_key_exists("S_ForceLapsNb", $ScriptSettings)) {
            if ($ScriptSettings['S_ForceLapsNb'] != -1) {
                $nbLaps = $ScriptSettings['S_ForceLapsNb'];
            } else {
                $nbLaps = $this->storage->currentMap->nbLaps;
            }
        }
        if (isset($ScriptSettings['S_MaxPointsPerRound'])) {
            $teamMaxPoint = $ScriptSettings['S_MaxPointsPerRound'];
        }

        $script->setParam("maxPoint", $teamMaxPoint);

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        if ($gamemode == GameInfos::GAMEMODE_LAPS) {
            $script->setParam("isLaps", "True");
            $script->setParam("nbLaps", $nbLaps);
        } elseif ($gamemode == GameInfos::GAMEMODE_ROUNDS && $this->storage->currentMap->nbLaps > 1) {
            $script->setParam("isLaps", "True");
            $script->setParam("nbLaps", $nbLaps);
        } elseif ($gamemode == GameInfos::GAMEMODE_TEAM && $this->storage->currentMap->nbLaps > 1) {
            $script->setParam("isLaps", "True");
            $script->setParam("nbLaps", $nbLaps);
        } elseif ($gamemode == GameInfos::GAMEMODE_CUP && $this->storage->currentMap->nbLaps > 1) {
            $script->setParam("isLaps", "True");
            $script->setParam("nbLaps", $nbLaps);
        }

        if ($gamemode == GameInfos::GAMEMODE_LAPS) {
            $script->setParam("givePoints", "False");
        }

        if ($gamemode == GameInfos::GAMEMODE_TEAM) {
            $script->setParam("isTeam", "True");

            $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

            if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
                $script->setParam("team1Color", '$' . $var["team1Color"]);
                $script->setParam("team2Color", '$' . $var["team2Color"]);
            } else {
                $script->setParam("team1Color", '$3AF');
                $script->setParam("team2Color", '$D00');
            }
        }

        if (!empty($this->roundPoints)) {
            $script->setParam("points", "[" . implode(",", $this->roundPoints) . "]");
        }




        if (!self::$raceOn) {
            return $script;
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
            $NickNames .= '"' . Gui::fixString($login) . '"=>"' . Gui::fixString(Formatting::stripLinks($nickname)) . '"';
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


        if ($teamCont == 0) {
            $script->setParam("playerTeams", "Integer[Text]");
        } else {
            $script->setParam("playerTeams", $teams);
        }

        if (!empty($newPlayerCps)) {
            $script->setParam("playerTimes", $playerTimes);
            $script->setParam("nickNames", $NickNames);
            $script->setParam("maxCp", $biggestCp + 1);
            $script->setParam("bestCps", $bestCps);
        } else {
            $script->setParam("playerTimes", 'Integer[Text][Integer]');
            $script->setParam("nickNames", 'Text[Text]');
            $script->setParam("maxCp", -1);
        }

        return $script;
    }

    public function getWidgetScriptEndurance($nbField, $nbFirstField, $update = false)
    {
        if (!$update) {
            $script = new Script("Endurance/Gui/Scripts/PlayerFinish");
            $script->setParam("nbScores", 500);
            $script->setParam("nbFields", $nbField);
            $script->setParam("nbFirstFields", $nbFirstField);
            $script->setParam('varName', 'Liverankings');
        }

        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (Endurance::$enduro_total_points as $player_login => $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . Gui::fixString($player_login) . '"=>' . $record['points'];
            $nickData .= '"' . Gui::fixString($player_login) . '"=>"' . Gui::fixString($record['name']) . '"';
            $index++;
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        if (!$update) {
            $script->setParam("playerScores", $recsData);
            $script->setParam("playerNicks", $nickData);
        } else {
            return "main () {
                declare Integer[Text] playerTimesLiverankings for UI = Integer[Text];
                playerTimesLiverankings.clear();
                playerTimesLiverankings = $recsData;

                declare Text[Text] playerNickNameLiverankings for UI = Text[Text];
                playerNickNameLiverankings.clear();
                playerNickNameLiverankings = $nickData;

                declare Boolean needUpdateLiverankings for UI = True;
                needUpdateLiverankings = True;
            }";
        }

        return $script;
    }

    public function getWidgetScript($nbField, $nbFirstField, $update = false)
    {
        if (!$update) {
            $script = new Script("Widgets_LocalRecords/Gui/Scripts/PlayerFinish");
            $script->setParam("nbRecord", 255);
            $script->setParam("nbFields", $nbField);
            $script->setParam("nbFirstFields", $nbFirstField);
            $script->setParam('varName', 'Liverankings');
        }

        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (Core::$rankings as $player) {
            if (!empty($player->bestTime) && $player->bestTime > 0) {
                if ($index > 1) {
                    $recsData .= ', ';
                    $nickData .= ', ';
                }
                $recsData .= '"' . Gui::fixString($player->login) . '"=>' . $player->bestTime;
                $nickData .= '"' . Gui::fixString($player->login) . '"=>"' . Gui::fixString(Formatting::stripLinks($player->nickName)) . '"';
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

        if (!$update) {
            $script->setParam("playerTimes", $recsData);
            $script->setParam("playerNicks", $nickData);
        } else {
            return "main () {
                declare Integer[Text] playerTimesLiverankings for UI = Integer[Text];
                playerTimesLiverankings.clear();
                playerTimesLiverankings = $recsData;

                declare Text[Text] playerNickNameLiverankings for UI = Text[Text];
                playerNickNameLiverankings.clear();
                playerNickNameLiverankings = $nickData;

                declare Boolean needUpdateLiverankings for UI = True;
                needUpdateLiverankings = True;
            }";
        }

        return $script;
    }

    public function getTrayScript($sizeX, $nbField)
    {
        $script = new Script("Gui/Scripts/NewTray");
        $script->setParam("sizeX", $sizeX);
        $script->setParam("sizeY", 3 + $nbField * 4);
        return $script;
    }

    public function showLivePanel($login)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->show($login);
            if ($this->widget2 instanceof Widget) {
                $this->widget2->show($login);
            }
        }
    }

    public function hideLivePanel()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase();
            }
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
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->updateLivePanel();
            self::$raceOn = true;
        } else {
            $this->hideLivePanel();
        }
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
            $this->showLivePanel($login);
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
        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Endurance\Events\Event::getClass(), $this);
    }
}
