<?php

namespace ManiaLivePlugins\eXpansion\Halloween;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Halloween extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var StaticWidget */
    private $widget;

    /** @var Script */
    private $script;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        /** @var Config $config */
        $config = Config::getInstance();
        Gui::preloadImage($config->texture);
        Gui::preloadUpdate();

        $this->script = new Script('Halloween\Gui\Script');

        $this->widget = new StaticWidget('Halloween\Gui\Widget\SpiderWidget.xml');
        $this->widget->setName("Spiders");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 0, -60);
        $this->widget->registerScript($this->script);
    }

    public function spiders()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $spiders = array();
        for ($i = 1; $i <= $config->spriteCount; $i++) {
            $spiders[] = $i;
        }

        $this->script->setParam("spiderCount", $config->spriteCount);
        $this->widget->setParam("spiders", $spiders);
        $this->widget->setParam("texture", $this->widget->handleSpecialChars($config->texture));
        $this->widget->show(null, true);
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->widget->erase();
    }

    public function onBeginMatch()
    {
        $this->widget->erase();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $this->spiders();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->script = null;

        parent::eXpOnUnload();
    }
}
