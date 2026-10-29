<?php

namespace ManiaLivePlugins\eXpansion\Widgets_ServerInfo;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_ServerInfo extends ExpPlugin
{
    
    private $config;
    private $script;
    private $widget;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->script = new Script("Widgets_ServerInfo\Gui\Scripts_Infos");

        $this->widget = new Widget("Widgets_ServerInfo\Gui\Widgets\ServerInfo.xml");
        $this->widget->setName("Server info Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(60, 15);
        $this->widget->registerScript($this->script);
        if ($this->storage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }

        $this->displayWidget();
    }

    private function displayWidget()
    {
        $this->script->setParam("maxPlayers", $this->storage->server->currentMaxPlayers);
        $this->script->setParam("maxSpec", $this->storage->server->currentMaxSpectators);

        $this->widget->setPosition($this->config->serverInfosWidget_PosX, $this->config->serverInfosWidget_PosY, 0);
        $this->widget->setParam("min", $this->storage->server->ladderServerLimitMin / 1000);
        $this->widget->setParam("max", $this->storage->server->ladderServerLimitMax / 1000);
        $this->widget->show(null, true);
    }

    public function onBeginMatch()
    {
        $this->displayWidget();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->script = null;
    }
}
