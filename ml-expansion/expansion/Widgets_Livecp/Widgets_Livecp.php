<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Livecp;

use ManiaLivePlugins\eXpansion\Widgets_Livecp\Gui\Widgets\LiveCP;
use ManiaLivePlugins\eXpansion\Widgets_Livecp\Gui\Widgets\LiveCP2;

class Widgets_Livecp extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        if (strtolower($this->connection->getScriptName()['CurrentValue']) != "endurocup.script.txt") {
            $this->displayWidget();
        }
    }

    private function displayWidget()
    {
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        LiveCP::EraseAll();
        LiveCP2::EraseAll();

        $info = LiveCP::Create(null, true);
        $info->update($this->eXpGetCurrentCompatibilityGameMode(), $this->storage->currentMap, $this->connection->getModeScriptSettings());
        $info->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
        $info->setPosition(120, -1);
        $info->show();

        if (!$gui->disablePersonalHud) {
            $info2 = LiveCP2::Create(null, true);
            $info2->update($this->eXpGetCurrentCompatibilityGameMode(), $this->storage->currentMap, $this->connection->getModeScriptSettings());
            $info2->setLayer(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
            $info2->setVisibleLayer("scorestable");
            $info2->setPosition(120, -1);
            $info2->show();
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->displayWidget();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if (strtolower($this->connection->getScriptName()['CurrentValue']) != "endurocup.script.txt") {
            $this->displayWidget();
        }
    }

    public function onBeginMatch()
    {
        if (!\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro) {
            $this->displayWidget();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        LiveCP::EraseAll();
        LiveCP2::EraseAll();
    }

    public function eXpOnUnload()
    {
        LiveCP::EraseAll();
        LiveCP2::EraseAll();
        $this->disableDedicatedEvents();
    }
}
