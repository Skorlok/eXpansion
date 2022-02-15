<?php

namespace ManiaLivePlugins\eXpansion\Snow;

class Snow extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        Gui\Windows\SnowParticle::EraseAll();
    }

    public function onBeginMatch()
    {
        Gui\Windows\SnowParticle::EraseAll();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $window = Gui\Windows\SnowParticle::Create(null);
        $window->show();
    }

    public function eXpOnUnload()
    {
        Gui\Windows\SnowParticle::EraseAll();
        parent::eXpOnUnload();
    }
}
