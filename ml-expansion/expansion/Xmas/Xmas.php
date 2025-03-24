<?php

namespace ManiaLivePlugins\eXpansion\Xmas;

class Xmas extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        Gui\Windows\XmasWindow::EraseAll();
    }

    public function onBeginMatch()
    {
        Gui\Windows\XmasWindow::EraseAll();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $window = Gui\Windows\XmasWindow::Create(null);
        $window->show();
    }

    public function eXpOnUnload()
    {
        Gui\Windows\XmasWindow::EraseAll();
        parent::eXpOnUnload();
    }
}
