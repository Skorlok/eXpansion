<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Map;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange;

class Widgets_Map extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    private $widget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents(\ManiaLive\DedicatedApi\Callback\Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);
        $this->registerManialinkCallback('showMapInfo');
        
        /** @var Config $config */
        $config = Config::getInstance();

        $this->widget = new Widget("Widgets_Map\Gui\Widgets\Map.xml");
        $this->widget->setName("Mapinfo Widget");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($config->mapWidget_PosX, $config->mapWidget_PosY, 0);
        $this->widget->setSize(60, 15);
        $this->widget->registerScript(new Script("Widgets_Map\Gui\Scripts_Map"));
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        $this->widget->show(null, true);
    }

    public function showMapInfo($login)
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange') && ManiaExchange::$mxInfo) {
            \call_user_func(ManiaExchange::$openInfosAction, $login);
        } else if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Maps\Maps')) {
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Maps\Maps', "showMapInfo", $login, null);
        }
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;
    }
}
