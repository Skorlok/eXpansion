<?php

namespace ManiaLivePlugins\eXpansion\Widgets_MapSuggestion;

use ManiaLive\Gui\ActionHandler;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_MapSuggestion extends ExpPlugin
{

    private $action;
    private $config;
    private $widget;

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\\ManiaLivePlugins\\eXpansion\\MapSuggestion\\MapSuggestion'));
    }

    public function eXpOnReady()
    {
        /** @var Config $config */
        $this->config = Config::getInstance();
        /** @var ActionHandler $ahandler */
        $ahandler = ActionHandler::getInstance();
        $this->action = $ahandler->createAction(array($this, "invoke"));

        $this->widget = new Widget("Widgets_MapSuggestion\Gui\Widgets\MapSuggestionButton.xml");
        $this->widget->setName("Map Suggestion Button");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($this->config->mapSuggestionButton_PosX, $this->config->mapSuggestionButton_PosY, 0);
        $this->widget->setSize(10, 10);
        $this->widget->setParam("action", $this->action);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        $this->widget->show(null, true);
    }

    public function invoke($login)
    {
        $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\MapSuggestion\\MapSuggestion", "showMapWishWindow", $login);
    }

    public function eXpOnUnload()
    {
        /** @var ActionHandler $ahandler */
        $ahandler = ActionHandler::getInstance();
        $ahandler->deleteAction($this->action);
        $this->action = null;
        $this->widget->erase();
        $this->widget = null;
    }
}
