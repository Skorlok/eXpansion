<?php

namespace ManiaLivePlugins\eXpansion\Widgets_MapSuggestion;

use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_MapSuggestion extends ExpPlugin
{
    private $widget;

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\\ManiaLivePlugins\\eXpansion\\MapSuggestion\\MapSuggestion'));
    }

    public function eXpOnReady()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $this->widget = new Widget("Widgets_MapSuggestion\Gui\Widgets\MapSuggestionButton.xml");
        $this->widget->setName("Map Suggestion Button");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($config->mapSuggestionButton_PosX, $config->mapSuggestionButton_PosY, 15);
        $this->widget->setSize(10, 10);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        $this->widget->show(null, true);
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;
    }
}
