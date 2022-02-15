<?php

namespace ManiaLivePlugins\eXpansion\Ants;

class Ants extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public function eXpOnReady()
    {
        parent::eXpOnReady();
        $this->enableDedicatedEvents();
        $config = Config::getInstance();
        \ManiaLivePlugins\eXpansion\Gui\Gui::preloadImage($config->texture);
        \ManiaLivePlugins\eXpansion\Gui\Gui::preloadUpdate();
    }

    public function ants()
    {
        Gui\Widget\AntsWidget::EraseAll();
        $window = Gui\Widget\AntsWidget::Create(null);
        $window->show();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        Gui\Widget\AntsWidget::EraseAll();
    }

    public function onBeginMatch()
    {
        Gui\Widget\AntsWidget::EraseAll();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $window = Gui\Widget\AntsWidget::Create(null);
        $window->show();
    }

    public function eXpOnUnload()
    {
        Gui\Widget\AntsWidget::EraseAll();
        parent::eXpOnUnload();
    }
}
