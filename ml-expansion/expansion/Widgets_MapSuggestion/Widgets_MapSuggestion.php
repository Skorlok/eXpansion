<?php

namespace ManiaLivePlugins\eXpansion\Widgets_MapSuggestion;

use ManiaLive\Gui\ActionHandler;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Widgets_MapSuggestion\Gui\Widgets\MapSuggestionButton;

class Widgets_MapSuggestion extends ExpPlugin
{

    private $action;
    private $config;

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\\ManiaLivePlugins\\eXpansion\\MapSuggestion\\MapSuggestion'));
    }

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $ahandler = ActionHandler::getInstance();
        $this->action = $ahandler->createAction(array($this, "invoke"));

        $button = MapSuggestionButton::Create();
        $button->setActions($this->action);
        $button->setPosition($this->config->mapSuggestionButton_PosX, $this->config->mapSuggestionButton_PosY);
        $button->setSize(10.0, 10.0);
        $button->show();
    }


    public function invoke($login)
    {
        $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\MapSuggestion\\MapSuggestion", "showMapWishWindow", $login);
    }


    public function eXpOnUnload()
    {
        $ahandler = ActionHandler::getInstance();
        $ahandler->deleteAction($this->action);
        $this->action = null;
        MapSuggestionButton::EraseAll();
    }
}
