<?php

namespace ManiaLivePlugins\eXpansion\Snow;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Snow extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
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

        $this->script = new Script('Snow\Gui\Script');

        $this->widget = new StaticWidget('Snow\Gui\Windows\SnowParticle.xml');
        $this->widget->setName("Snow");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 0, -60);
        $this->widget->registerScript($this->script);
    }

    public function snow()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $particles = array();
        for ($i = 0; $i < $config->particleCount; $i++) {
            $particles[] = array('index' => $i, 'posX' => $i * 4);
        }

        $this->script->setParam("particleCount", $config->particleCount);
        $this->widget->setParam("particles", $particles);
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
        $this->snow();
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
