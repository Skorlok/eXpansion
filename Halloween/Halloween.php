<?php

namespace ManiaLivePlugins\eXpansion\Halloween;

class Halloween extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    public $wasWarmup = false;

    public function eXpOnReady()
    {
        parent::eXpOnReady();
        $this->enableDedicatedEvents();
        $config = Config::getInstance();
        \ManiaLivePlugins\eXpansion\Gui\Gui::preloadImage($config->texture);
        \ManiaLivePlugins\eXpansion\Gui\Gui::preloadUpdate();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        Gui\Widget\SpiderWidget::EraseAll();
    }

    public function onBeginMatch()
    {
        Gui\Widget\SpiderWidget::EraseAll();
    }

    public function onBeginRound()
    {
        $this->wasWarmup = $this->connection->getWarmUp();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->wasWarmup)
            return;
        $window = Gui\Widget\SpiderWidget::Create(null);
        $window->show();
    }

    public function eXpOnUnload()
    {
        Gui\Widget\SpiderWidget::EraseAll();
        parent::eXpOnUnload();
    }
}
