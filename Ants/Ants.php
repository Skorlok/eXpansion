<?php

namespace ManiaLivePlugins\eXpansion\Ants;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Ants extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
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

        $this->script = new Script('Ants\Gui\Script');

        $this->widget = new StaticWidget('Ants\Gui\Widget\AntsWidget.xml');
        $this->widget->setName("Ants");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 0, -60);
        $this->widget->registerScript($this->script);
    }

    public function ants()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $ants = array();
        for ($i = 1; $i <= $config->spriteCount; $i++) {
            $ants[] = $i;
        }

        $this->script->setParam("antCount", $config->spriteCount);
        $this->widget->setParam("ants", $ants);
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
        $this->ants();
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
