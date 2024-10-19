<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Gui\Widgets\BestCpPanel;
use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Structures\Checkpoint;

class Widgets_BestCheckpoints extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public $bestCps = array();
    public $finishTimes = array();
    private $config;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        $this->bestCps = array();
        $this->finishTimes = array();
    }

    /**
     * displayWidget(string $login)
     *
     * @param string $login
     */
    public function displayWidget($checkpoints)
    {
        $info = BestCpPanel::Create(null, true);
        $info->populateList($checkpoints);
        $info->setPosition($this->config->bestCpWidget_PosX, $this->config->bestCpWidget_PosY);
        $info->setSize(190, 7);
        $info->show();
    }

    public function onBeginMatch()
    {
        $this->bestCps = array();
        $this->finishTimes = array();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        BestCpPanel::EraseAll();
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
        BestCpPanel::EraseAll();
        $this->bestCps = array();
        $this->finishTimes = array();
    }

    public function eXpOnUnload()
    {
        BestCpPanel::EraseAll();
        parent::eXpOnUnload();
    }
}
