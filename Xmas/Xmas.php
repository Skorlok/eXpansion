<?php

namespace ManiaLivePlugins\eXpansion\Xmas;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Xmas extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var StaticWidget */
    private $widget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        /** @var Config $config */
        $config = Config::getInstance();
        Gui::preloadImage($config->texture);
        Gui::preloadUpdate();

        $this->widget = new StaticWidget('Xmas\Gui\Windows\XmasWindow.xml');
        $this->widget->setName("Xmas lights");
        $this->widget->setLayer("normal");
        $this->widget->registerScript(new Script('Xmas\Gui\Script'));
    }

    public function lights()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $this->widget->setPosition(-($config->width * $config->repeat * $config->scale) / 2, $config->height * $config->scale, -60);

        $lights = array();
        for ($i = 0; $i < $config->repeat; $i++) {
            $lights[] = array('index' => $i, 'posX' => $config->posX + $i * $config->width);
        }

        $this->widget->setParam("lights", $lights);
        $this->widget->setParam("width", $config->width);
        $this->widget->setParam("height", $config->height);
        $this->widget->setParam("posY", $config->posY);
        $this->widget->setParam("posZ", $config->posZ);
        $this->widget->setParam("scale", $config->scale);
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
        $this->lights();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;

        parent::eXpOnUnload();
    }
}
