<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Livecp;

use ManiaLivePlugins\eXpansion\Widgets_Livecp\Gui\Widgets\LiveCP;
use ManiaLivePlugins\eXpansion\Widgets_Livecp\Gui\Widgets\LiveCP2;

class Widgets_Livecp extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    private $config;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
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
        $info->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
        $info->setPosition($this->config->livecpPanel_PosX, $this->config->livecpPanel_PosY);
        $info->setNbFields($this->config->livecpPanel_nbFields);
        $info->setNbFirstFields($this->config->livecpPanel_nbFirstFields);
        $info->update($this->eXpGetCurrentCompatibilityGameMode(), $this->storage->currentMap, $this->connection->getModeScriptSettings());
        $info->show();

        if (!$gui->disablePersonalHud) {
            $info2 = LiveCP2::Create(null, true);
            $info2->setLayer(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
            $info2->setVisibleLayer("scorestable");
            $info2->setPosition($this->config->livecpPanel_PosX, $this->config->livecpPanel_PosY);
            $info2->setNbFields($this->config->livecpPanel_nbFields);
            $info2->setNbFirstFields($this->config->livecpPanel_nbFirstFields);
            $info2->update($this->eXpGetCurrentCompatibilityGameMode(), $this->storage->currentMap, $this->connection->getModeScriptSettings());
            $info2->show();
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            $this->displayWidget();
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
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
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
