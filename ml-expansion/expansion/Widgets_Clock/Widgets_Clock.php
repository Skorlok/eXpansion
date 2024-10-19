<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Clock;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Widgets_Clock\Gui\Widgets\Clock;

class Widgets_Clock extends ExpPlugin
{
    private $config;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->show();
    }

    public function show()
    {
        $widget = Clock::Create(null);
        if ($this->expStorage->simpleEnviTitle == "SM") {
            $widget->setPosition($this->config->clock_PosX_Shootmania, $this->config->clock_PosY_Shootmania);
        } else {
            $widget->setPosition($this->config->clock_PosX, $this->config->clock_PosY);
        }
        $widget->show();
    }

    public function eXpOnUnload()
    {
        Clock::EraseAll();
    }
}
