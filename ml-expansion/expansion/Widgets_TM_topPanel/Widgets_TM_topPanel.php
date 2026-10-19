<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TM_topPanel;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;

class Widgets_TM_topPanel extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var StaticWidget */
    private $widget;

    /** @var Script */
    private $nbPlayerScript;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->widget = new StaticWidget('Widgets_TM_topPanel\Gui\Widgets\TopPanel.xml');
        $this->widget->setName("Top Panel");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 90, -60);
        $this->widget->registerScript(new Script('Widgets_TM_topPanel\Gui\Scripts\clock'));
        $this->widget->registerScript(new Script('Widgets_TM_topPanel\Gui\Scripts\serverInfo'));
        $this->widget->registerScript(new Script('Widgets_TM_topPanel\Gui\Scripts\mapInfo'));

        $this->nbPlayerScript = new Script('Widgets_TM_topPanel\Gui\Scripts\nbPlayer');
        $this->widget->registerScript($this->nbPlayerScript);

        $this->showWidget();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->showWidget();
    }

    private function showWidget()
    {
        $this->nbPlayerScript->setParam('maxPlayers', $this->storage->server->currentMaxPlayers);
        $this->nbPlayerScript->setParam('maxSpecs', $this->storage->server->currentMaxSpectators);

        $this->widget->setParam("ladderLimits", $this->widget->addLang('Ladder limits %1$s - %2$sk', array($this->storage->server->ladderServerLimitMin / 1000, $this->storage->server->ladderServerLimitMax / 1000)));
        $this->widget->setParam("mapName", $this->widget->handleSpecialChars(Formatting::stripCodes($this->storage->currentMap->name, "wosn")));
        $this->widget->setParam("mapAuthor", $this->widget->handleSpecialChars(Formatting::stripCodes($this->storage->currentMap->author, "wosn")));

        $this->widget->show(null, true);
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->nbPlayerScript = null;
    }
}
