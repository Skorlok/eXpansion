<?php

namespace ManiaLivePlugins\eXpansion\Custom321Go;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Description of Custom321Go
 *
 * @author Petri Järvisalo <petri.jarvisalo@gmail.com>
 */
class Custom321Go extends ExpPlugin
{
    /** @var StaticWidget */
    private $widget;

    public function eXpOnReady()
    {
        parent::eXpOnReady();

        $this->enableDedicatedEvents();

        /** @var Config $config */
        $config = Config::getInstance();
        Gui::preloadImage($config->sprite1);
        Gui::preloadImage($config->sprite2);
        Gui::preloadUpdate();

        $this->widget = new StaticWidget('Custom321Go\Gui\Widgets\Countdown.xml');
        $this->widget->setName("countdown");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 0, -60);
        $this->widget->registerScript(new Script('Custom321Go\Gui\Scripts'));

        $this->showHud();
    }

    public function showHud()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $this->widget->setParam("sprite1", $config->sprite1);
        $this->widget->setParam("sprite2", $config->sprite2);
        $this->widget->show(null, true);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $this->widget->erase();
    }

    public function onBeginMatch()
    {
        $this->showHud();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;
    }
}
