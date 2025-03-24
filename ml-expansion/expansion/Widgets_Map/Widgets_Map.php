<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Map;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\MapInfo;

class Widgets_Map extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $config;
    private $widget;
    private $action;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->displayWidget();
    }

    public function displayWidget()
    {
        $this->action = ActionHandler::getInstance()->createAction(array($this, "showMapInfo"));

        $this->widget = new Widget("Widgets_Map\Gui\Widgets\Map.xml");
        $this->widget->setName("Mapinfo Widget");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($this->config->mapWidget_PosX, $this->config->mapWidget_PosY, 0);
        $this->widget->setSize(60, 15);
        $this->widget->registerScript(new Script("Widgets_Map\Gui\Scripts_Map"));
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        $this->widget->setParam("action", $this->action);
        $this->widget->show(null, true);
    }

    public function showMapInfo($login)
    {
        if (ManiaExchange::$mxInfo) {
            \call_user_func(ManiaExchange::$openInfosAction, $login);
        } else {
            $window = MapInfo::create($login);
            $window->setMap(null);
            $window->setSize(160, 90);
            $window->show($login);
        }
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        ActionHandler::getInstance()->deleteAction($this->action);
    }
}
