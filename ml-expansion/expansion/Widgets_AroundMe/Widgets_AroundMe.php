<?php

namespace ManiaLivePlugins\eXpansion\Widgets_AroundMe;

use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLib\Utils\Formatting;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_AroundMe extends ExpPlugin
{

    public static $raceOn;

    /** @var Config */
    private $config;

    private $widget;

    public function eXpOnLoad()
    {
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->updateAroundMe();
        
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

    public function updateAroundMe($login = null)
    {
        $this->widget = new Widget("Widgets_AroundMe\Gui\Widgets\AroundMe.xml");
        $this->widget->setName("Around Me Panel");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($this->config->aroundmeWidget_PosX, $this->config->aroundmeWidget_PosY, 0);
        $this->widget->setSize(30, 10);
        $this->widget->registerScript(new Script('Gui/Script_libraries/TimeToText'));
        $this->widget->registerScript($this->getScript());
        $this->widget->show($login);
    }

    protected function getScript()
    {
        $script = new Script('Widgets_AroundMe/Gui/Scripts/CpPositions');
        $script->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
        $script->setParam("nbFields", 20);
        $script->setParam("nbFirstFields", 5);
        $script->setParam('varName', 'aroundMe');
        $script->setParam("playerTimes", 'Integer[Text][Integer]');
        $script->setParam("nickNames", 'Text[Text][Integer]');
        $script->setParam("bestCps", 'Integer[Integer]');
        $script->setParam("maxCp", -1);

        $script->setParam("givePoints", "True");
        $script->setParam("points", "Integer[Integer]");
        $script->setParam("nbLaps", 1);
        $script->setParam("isLaps", "False");
        $script->setParam("isTeam", "False");
        $script->setParam("playerTeams", "Integer[Text]");
        $script->setParam('getCurrentTimes', 'True');

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();

        $teamMaxPoint = 10;
        if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
            $settings = $this->connection->getModeScriptSettings();
            if (array_key_exists("S_ForceLapsNb", $settings)) {
                $nbLaps = $settings['S_ForceLapsNb'] == -1 ? 1 : $settings['S_ForceLapsNb'];
            }
            if (isset($settings['S_MaxPointsPerRound'])) {
                $teamMaxPoint = $settings['S_MaxPointsPerRound'];
            }
        } else {
            $teamMaxPoint = $this->storage->gameInfos->teamPointsLimit;
        }

        if ($gamemode == GameInfos::GAMEMODE_LAPS) {
            $script->setParam("isLaps", "True");

            if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
                $script->setParam("nbLaps", $nbLaps);
            } else {
                $script->setParam("nbLaps", $this->storage->gameInfos->lapsNbLaps);
            }
        } elseif ($gamemode == GameInfos::GAMEMODE_ROUNDS && $this->storage->gameInfos->roundsForcedLaps > 0) {
            $script->setParam("isLaps", "True");

            if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
                $script->setParam("nbLaps", $nbLaps);
            } else {
                $script->setParam("nbLaps", $this->storage->gameInfos->roundsForcedLaps);
            }
        } elseif ($gamemode == GameInfos::GAMEMODE_TEAM && $this->storage->gameInfos->roundsForcedLaps > 0) {
            $script->setParam("isLaps", "True");

            if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
                $script->setParam("nbLaps", $$nbLaps);
            } else {
                $script->setParam("nbLaps", $this->storage->gameInfos->roundsForcedLaps);
            }
        } elseif ($gamemode == GameInfos::GAMEMODE_CUP && $this->storage->gameInfos->roundsForcedLaps > 0) {
            $script->setParam("isLaps", "True");

            if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
                $script->setParam("nbLaps", $nbLaps);
            } else {
                $script->setParam("nbLaps", $this->storage->gameInfos->roundsForcedLaps);
            }
        }

        if ($gamemode == GameInfos::GAMEMODE_TEAM) {
            $script->setParam("isTeam", "True");
        }

        if (!Widgets_AroundMe::$raceOn) {
            return $script;
        }

        $nbCheckpoints = array();
        $playerCps = array();
        $playerNickNames = array();
        $bestCps = array();
        $biggestCp = -1;

        foreach (Core::$playerInfo as $login => $player) {
            $lastCpIndex = count($player->checkpoints) - 1;
            if ($player->isPlaying && $lastCpIndex >= 0 && isset($player->checkpoints[$lastCpIndex]) && $player->checkpoints[$lastCpIndex] > 0) {

                if ($lastCpIndex > $biggestCp) {
                    $biggestCp = $lastCpIndex;
                }

                $lastCpTime = $player->checkpoints[$lastCpIndex];
                $player = $this->storage->getPlayerObject($login);
                $playerCps[$lastCpIndex][$login] = $lastCpTime;
                $playerNickNames[$lastCpIndex][$player->login] = Formatting::stripColors($player->nickName);
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

        $cpCount = 0;
        $teamCont = 0;
        foreach ($newPlayerCps as $cpIndex => $cpTimes) {
            if ($cpCount != 0) {
                $playerTimes .= ", ";
                $NickNames .= ", ";
            }
            $playerTimes .= $cpIndex . "=>[";
            $NickNames .= $cpIndex . "=>[";

            $cCount = 0;
            $nbCheckpoints[$cpIndex] = 0;
            foreach ($cpTimes as $login => $score) {
                if ($cCount != 0) {
                    $playerTimes .= ", ";
                    $NickNames .= ", ";
                }
                if ($teamCont != 0) {
                    $teams .= ", ";
                }
                $playerTimes .= '"' . $login . "\"=>" . $score;
                $NickNames .= '"' . $login . "\"=>\"" . Gui::fixString($playerNickNames[$cpIndex][$login]) . "\"";
                $teams .= '"' . $login . "\"=>" . ($playerTeams[$login] == 1 ? 0 : 1);
                $nbCheckpoints[$cpIndex]++;
                $cCount++;
                $teamCont++;

                if (!isset($bestCps[$cpIndex]) || $score < $bestCps[$cpIndex]) {
                    $bestCps[$cpIndex] = $score;
                }
            }

            $playerTimes .= "]";
            $NickNames .= "]";
            $cpCount++;

        }
        $playerTimes .= "]";
        $NickNames .= "]";
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
            $script->setParam("nickNames", 'Text[Text][Integer]');
            $script->setParam("maxCp", -1);
        }

        return $script;
    }

    public function showAroundMe($login)
    {
        $this->updateAroundMe($login);
    }

    public function hideAroundMe()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        self::$raceOn = false;
        $this->hideAroundMe();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->updateAroundMe();
            self::$raceOn = true;
        } else {
            $this->hideAroundMe();
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        self::$raceOn = false;
        $this->hideAroundMe();
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        self::$raceOn = false;
        $this->hideAroundMe();
        $this->updateAroundMe();
        self::$raceOn = true;
    }

    public function onBeginRound()
    {
        //We need to reset the panel for next Round
        self::$raceOn = false;
        $this->hideAroundMe();
        $this->updateAroundMe();
        self::$raceOn = true;
    }


    public function onPlayerConnect($login, $isSpectator)
    {
        $this->showAroundMe($login);
    }

    public function eXpOnUnload()
    {
        $this->hideAroundMe();
    }
}
