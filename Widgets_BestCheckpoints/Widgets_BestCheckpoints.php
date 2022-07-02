<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints;

use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Gui\Widgets\BestCpPanel;
use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Structures\Checkpoint;

class Widgets_BestCheckpoints extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public $BestCps = array();

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->displayWidget();
    }

    /**
     * displayWidget(string $login)
     *
     * @param string $login
     */
    public function displayWidget($login = null)
    {
        $info = BestCpPanel::Create($login, true);
        $info->setSize(190, 7);
        $info->show();
    }

    public function onBeginMatch()
    {
        $this->BestCps = array();
        $this->displayWidget();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        BestCpPanel::EraseAll();
        $this->BestCps = array();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        BestCpPanel::EraseAll();
        $this->BestCps = array();
    }

    public function eXpOnUnload()
    {
        BestCpPanel::EraseAll();
        parent::eXpOnUnload();
    }
}
