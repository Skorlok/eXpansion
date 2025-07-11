<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Clock;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_Clock extends ExpPlugin
{
    private $config;
    private $widget;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->show();
    }

    public function show()
    {
        $this->widget = new Widget("Widgets_Clock\Gui\Widgets\Clock.xml");
        $this->widget->setName("Local time");
        $this->widget->setLayer("normal");
        if ($this->expStorage->simpleEnviTitle == "SM") {
            $this->widget->setPosition($this->config->clock_PosX_Shootmania, $this->config->clock_PosY_Shootmania, 0);
        } else {
            $this->widget->setPosition($this->config->clock_PosX, $this->config->clock_PosY, 0);
        }
        $this->widget->setSize(16.5, 6);
        $this->widget->registerScript(new Script("Widgets_Clock/Gui/Script"));
        $this->widget->show(null, true);
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        $this->widget = null;
    }
}
