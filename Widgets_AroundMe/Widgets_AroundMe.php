<?php

namespace ManiaLivePlugins\eXpansion\Widgets_AroundMe;

use ManiaLivePlugins\eXpansion\Helpers\Formatting;
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
    /** @var Script */
    private $script;
    /** @var Widget */
    private $widget;

    public function eXpOnLoad()
    {
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->script = new Script('Widgets_AroundMe/Gui/Scripts/CpPositions');
        $this->script->setParam('varName', 'aroundMe');

        $this->widget = new Widget("Widgets_AroundMe\Gui\Widgets\AroundMe.xml");
        $this->widget->setName("Around Me Panel");
        $this->widget->setLayer("normal");
        $this->widget->setSize(30, 10);
        $this->widget->registerScript(new Script('Gui/Script_libraries/TimeToText'));
        $this->widget->registerScript($this->script);


        $this->updateAroundMe();

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

    public function updateAroundMe($login = null)
    {
        $data = array('Integer[Text][Integer]', 'Text[Text][Integer]', 'Integer[Integer]', -1);
        if (Widgets_AroundMe::$raceOn) {
            $data = $this->getWidgetData();
        }

        $this->script->setParam("playerTimes", $data[0]);
        $this->script->setParam("nickNames", $data[1]);
        $this->script->setParam("bestCps", $data[2]);
        $this->script->setParam("maxCp", $data[3]);

        $this->widget->setPosition($this->config->aroundmeWidget_PosX, $this->config->aroundmeWidget_PosY, 0);
        $this->widget->show($login);
    }

    private function getWidgetData()
    {
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

                $playerCps[$lastCpIndex][$login] = $player->checkpoints[$lastCpIndex];
                $playerNickNames[$lastCpIndex][$login] = Formatting::stripColors($player->nickName);
            }
        }

        if (empty($playerCps)) {
            return array('Integer[Text][Integer]', 'Text[Text][Integer]', 'Integer[Integer]', -1);
        }

        $newPlayerCps = array();
        foreach ($playerCps as $coIndex => $cpsTimes) {
            arsort($cpsTimes);
            $newPlayerCps[$coIndex] = $cpsTimes;
        }

        $playerTimes = "[";
        $NickNames = "[";

        $cpCount = 0;
        foreach ($newPlayerCps as $cpIndex => $cpTimes) {
            if ($cpCount != 0) {
                $playerTimes .= ", ";
                $NickNames .= ", ";
            }
            $playerTimes .= $cpIndex . "=>[";
            $NickNames .= $cpIndex . "=>[";

            $cCount = 0;
            foreach ($cpTimes as $login => $score) {
                if ($cCount != 0) {
                    $playerTimes .= ", ";
                    $NickNames .= ", ";
                }
                $playerTimes .= '"' . $login . "\"=>" . $score;
                $NickNames .= '"' . $login . "\"=>\"" . Gui::fixString($playerNickNames[$cpIndex][$login]) . "\"";
                $cCount++;

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

        $bestCpsText = '';
        foreach ($bestCps as $cpIndex => $time) {
            if ($bestCpsText != "") {
                $bestCpsText .= ', ';
            }
            $bestCpsText .= $cpIndex . '=>' . $time;
        }

        return array($playerTimes, $NickNames, '[' . $bestCpsText . ']', $biggestCp + 1);
    }

    public function hideAroundMe()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        self::$raceOn = false;
        $this->hideAroundMe();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        $this->hideAroundMe();
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
        $this->updateAroundMe($login);
    }

    public function eXpOnUnload()
    {
        $this->hideAroundMe();
        $this->config = null;
        $this->script = null;
        $this->widget = null;
    }
}
