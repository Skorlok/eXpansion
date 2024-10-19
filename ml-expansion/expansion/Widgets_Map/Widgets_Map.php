<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Map;

use ManiaLivePlugins\eXpansion\Widgets_Map\Gui\Widgets\Map;

class Widgets_Map extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $config;

    public function eXpOnLoad()
    {
        // $this->enableDedicatedEvents();
    }

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->displayWidget(null);
    }

    /**
     * displayWidget(string $login)
     *
     * @param string $login
     */
    public function displayWidget($login)
    {
        $info = Gui\Widgets\Map::Create();
        $info->setPosition($this->config->mapWidget_PosX, $this->config->mapWidget_PosY);
        $info->setSize(60, 15);
        $info->setScale(0.75);
        $info->show();
    }

    public function eXpOnUnload()
    {
        Map::EraseAll();
    }
}
