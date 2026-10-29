<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints;

use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Structures\Checkpoint;

class Widgets_BestCheckpoints extends ExpPlugin
{
    public $bestCps = array();
    public $finishTimes = array();
    private $config;
    private $widget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        $this->bestCps = array();
        $this->finishTimes = array();

        $this->widget = new Widget("Widgets_BestCheckpoints\Gui\Widgets\BestCpPanel.xml");
        $this->widget->setName("Best CheckPoints Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(190, 7);
    }

    /**
     * displayWidget(string $login)
     */
    public function displayWidget($checkpoints)
    {
        $rows = array();
        for ($x = 0; $x < $this->config->CPNumber && $x < count($checkpoints); $x++) {
            if (!isset($checkpoints[$x])) {
                continue;
            }

            $time = Time::fromTM($checkpoints[$x]->time);
            if (strpos($time, ":") == 1) {
                $time = "0" . $time;
            }

            $rows[] = array(
                'index'    => $x,
                'rank'     => $x + 1,
                'posX'     => 35 * ($x % 6),
                'posY'     => -5 * floor($x / 6),
                'time'     => $time,
                'nickname' => $this->widget->handleSpecialChars($checkpoints[$x]->nickname),
            );
        }

        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();
        /** @var guiConfig $guiConfig */
        $guiConfig = guiConfig::getInstance();

        $this->widget->setPosition($this->config->bestCpWidget_PosX, $this->config->bestCpWidget_PosY, 0);
        $this->widget->setParam("checkpoints", $rows);
        $this->widget->setParam("bgColorize",  $this->widget->handleSpecialChars($guiConfig->style_widget_bgColorize));
        $this->widget->setParam("rankColor",   $this->widget->handleSpecialChars(str_replace('$', "", $colorParser->getColor("#rank#"))));
        $this->widget->setParam("timeColor",   $this->widget->handleSpecialChars(str_replace('$', "", $colorParser->getColor("#time#"))));
        $this->widget->show(null, true);
    }

    public function onBeginMatch()
    {
        $this->bestCps = array();
        $this->finishTimes = array();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->widget->erase();

        $this->bestCps = array();
        $this->finishTimes = array();
    }

    public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
    {
        if (($checkpointIndex + 1) == $this->storage->currentMap->nbCheckpoints * ($curLap)) {
            $this->finishTimes[$login] = $timeOrScore;
            return;
        }

        if (($checkpointIndex + 1) > $this->storage->currentMap->nbCheckpoints) {
            $cpPassed = $this->storage->currentMap->nbCheckpoints * $curLap;
            if (isset($this->finishTimes[$login])) {
                $timeOrScore = $timeOrScore - $this->finishTimes[$login];
                $checkpointIndex = $checkpointIndex - $cpPassed;
                if ($checkpointIndex < 0) {
                    $checkpointIndex = $this->storage->currentMap->nbCheckpoints - 1;
                }
            } else {
                $checkpointIndex = $checkpointIndex - $cpPassed;
                if ($checkpointIndex < 0) {
                    $checkpointIndex = $this->storage->currentMap->nbCheckpoints - 1;
                }
            }
        }

        if (!isset($this->bestCps[$checkpointIndex])) {
            $this->bestCps[$checkpointIndex] = new checkpoint($checkpointIndex, $login, Core::$players[$login], $timeOrScore);
            $this->displayWidget($this->bestCps);
        } else {
            if ($this->bestCps[$checkpointIndex]->time > $timeOrScore) {
                $this->bestCps[$checkpointIndex] = new checkpoint($checkpointIndex, $login, Core::$players[$login], $timeOrScore);
                $this->displayWidget($this->bestCps);
            }
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        $this->widget->erase();

        $this->bestCps = array();
        $this->finishTimes = array();
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        $this->widget = null;
        parent::eXpOnUnload();
    }
}
