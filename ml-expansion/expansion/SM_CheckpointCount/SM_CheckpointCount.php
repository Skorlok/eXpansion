<?php

namespace ManiaLivePlugins\eXpansion\SM_CheckpointCount;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;

class SM_CheckpointCount extends ExpPlugin
{

    private $config;
    private $widget;

    /*public function eXpOnLoad()
    {
        $this->enableScriptEvents("LibXmlRpc_OnWayPoint");
    }*/

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();

        $this->widget = new Widget("SM_CheckpointCount\Gui\Widgets\CpPanel.xml");
        $this->widget->setName("Checkpoint counter");
        $this->widget->setLayer("normal");
        $this->widget->setSize(35, 6);

        $this->displayWidget();
    }

    /**
     * displayWidget(string $login)
     * Refreshes and Displays checpoint counter widget to player
     * * If no login is given, widget is displayed for all players
     *
     * @param string $login |null
     */
    protected function displayWidget($login = null, $cpIndex = "-")
    {
        $text = '$fff' . $cpIndex . " / " . ($this->storage->currentMap->nbCheckpoints - 1);
        if ($cpIndex == ($this->storage->currentMap->nbCheckpoints - 1)) {
            $text = '$f00Finish now';
        }

        $this->widget->setPosition($this->config->checkpointCounter_PosX, $this->config->checkpointCounter_PosY, 0);
        $this->widget->setParam("text", $text);
        $this->widget->show($login, true);
    }

    /*public function LibXmlRpc_OnWayPoint($login, $blockId, $time, $cpIndex, $isEndBlock, $lapTime, $lapNb, $isLapEnd)
    {
        $cp = $cpIndex;
        if ($isEndBlock) {
            $cp = "-";
        }
        $this->displayWidget($login, $cp);
    }*/

    public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
    {
        $this->displayWidget($login, $checkpointIndex + 1);
    }

    public function onPlayerFinish($playerUid, $login, $timeOrScore)
    {
        $this->displayWidget($login, "-");
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
    }

    public function onBeginMatch()
    {
        $this->displayWidget();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
    }
}
