<?php

/*
 * Copyright (C) 2014
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

namespace ManiaLivePlugins\eXpansion\Widgets_TM_Obstacle;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Description of Widgets_CheckpointProgress
 *
 * @author Petri
 */
class Widgets_TM_Obstacle extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $config;
    private $widget;
    private $script;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();

        $this->script = new Script("Widgets_TM_Obstacle\Gui\Scripts_Infos");
        $this->script->setParam("playerCount", 10);
        $this->script->setParam("serverLogin", $this->storage->serverLogin);

        $this->widget = new Widget("Widgets_TM_Obstacle\Gui\Widgets\CpProgress.xml");
        $this->widget->setName("Obstacle progress Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(70, 60);
        $this->widget->registerScript($this->script);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }


        $this->displayWidget();
    }

    private function displayWidget()
    {
        $this->script->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
        $this->widget->setPosition($this->config->obstaclePanel_PosX, $this->config->obstaclePanel_PosY, 0);
        $this->widget->show(null, true);
    }

    public function onBeginMatch()
    {
        $this->displayWidget();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $this->widget->erase();
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        $this->widget = null;
        $this->script = null;
    }
}
