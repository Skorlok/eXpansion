<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Speedometer;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;

class Widgets_Speedometer extends ExpPlugin
{

    private $widget;
    private $config;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        $this->showWidget();
    }

    public function showWidget()
    {
        $this->widget = new Widget("Widgets_Speedometer\Gui\Widgets\Speedmeter.xml");
        $this->widget->setName("Speed'o'meter");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($this->config->speedometerWidget_PosX, $this->config->speedometerWidget_PosY, 0);
        $this->widget->setSize(45, 7);
        $this->widget->registerScript(new Script("Widgets_Speedometer\Gui\Script"));
        $this->widget->show(null, true);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && Endurance::$last_round == false) {
            return;
        }
        $this->widget->erase();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->widget->show(null, true);
    }

    public function onBeginMatch()
    {
        $this->widget->show(null, true);
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        $this->widget = null;
    }
}
