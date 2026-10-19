<?php

namespace ManiaLivePlugins\eXpansion\SM_EventHelper;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\Events\ScriptmodeEvent;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ManiaLink;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/*
 * Copyright (C) 2014 Reaby
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of SM_EventHelper
 *
 * @author Reaby
 */
class SM_EventHelper extends ExpPlugin
{
    /** @var ManiaLink */
    private $widget;

    /** @var Script */
    private $script;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->registerManialinkCallback('invokeCheckpoint', true);
        $this->registerManialinkCallback('invokeFinish', true);

        $this->script = new Script('SM_EventHelper\Gui\Script');
        $this->script->setParam("cpAction", 'exp:eXpansion.SM_EventHelper:invokeCheckpoint');
        $this->script->setParam("finishAction", 'exp:eXpansion.SM_EventHelper:invokeFinish');

        $this->widget = new ManiaLink('SM_EventHelper\Gui\EventHelper.xml');
        $this->widget->setName("EventHelper");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 900, -60);
        $this->widget->setScripts($this->script);

        $this->showWidget();
    }

    private function showWidget()
    {
        $this->script->setParam("cpCount", $this->storage->currentMap->nbCheckpoints);
        $this->widget->show(null, true);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        $this->widget->erase();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->showWidget();
    }

    public function invokeCheckpoint($login, $entries)
    {
        $timeOrScore = isset($entries['timeOrScore']) ? $entries['timeOrScore'] : null;
        $cpIndex = isset($entries['index']) ? $entries['index'] : null;

        Dispatcher::dispatch(
            // login  , #id   , time  ,  index ,  endblock, , laptime, lapCpIndex, lapEnd
            new ScriptmodeEvent(
                ScriptmodeEvent::LibXmlRpc_OnWayPoint,
                array($login, null, $timeOrScore, $cpIndex, "False", null, null, null)
            )
        );
    }

    public function invokeFinish($login, $entries)
    {
        $timeOrScore = isset($entries['timeOrScore']) ? $entries['timeOrScore'] : null;
        $cpIndex = isset($entries['index']) ? $entries['index'] : null;

        Dispatcher::dispatch(
            // login  , #id      , time  ,  index ,  endblock, , laptime, lapCpIndex, lapEnd
            new ScriptmodeEvent(
                ScriptmodeEvent::LibXmlRpc_OnWayPoint,
                array($login, null, $timeOrScore, $cpIndex, "True", null, null, null)
            )
        );
    }

    public function onBeginMatch()
    {
        $this->showWidget();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof ManiaLink) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->script = null;
    }
}
