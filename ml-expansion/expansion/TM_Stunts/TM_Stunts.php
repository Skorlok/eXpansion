<?php

/*
 * Copyright (C) 2015 Reaby
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

namespace ManiaLivePlugins\eXpansion\TM_Stunts;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Description of TM_Stunts
 *
 * @author Reaby
 */
class TM_Stunts extends ExpPlugin
{

    private $config;

    private $widget;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();

        $this->widget = new Widget("TM_Stunts\Gui\Widgets\StuntWidget.xml");
        $this->widget->setName("Stunts Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(60, 12);
        $this->widget->registerScript(new Script("TM_Stunts/Gui/Script"));

        $this->enableScriptEvents("LibXmlRpc_OnStunt");
        $this->enableScriptEvents("Trackmania.Event.Stunt");
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Trackmania.Event.Stunt":
                call_user_func_array(array($this, "onPlayerStunt"), json_decode($array[0], true));
                break;
        }
    }

    public function onPlayerStunt($time,$login,$racetime,$laptime,$stuntsscore,$figure,$angle,$points,$combo,$isstraight,$isreverse,$ismasterjump,$factor) {
        $figure = str_replace("::EStuntFigure::", "", $figure);

        if ($angle || ($figure != "StraightJump" && $figure != "RespawnPenalty")) {
            if ($isreverse) {
                $figure = "Reversed" . $figure;
            }
            if ($angle == 0) {
                $angle = "";
            }
            $split = preg_split('/(?=\p{Lu})/u', $figure);
            $figure = implode(" ", $split) . " " . $angle;

            $this->widget->setPosition($this->config->stuntWidget_PosX, $this->config->stuntWidget_PosY, 10);
            $this->widget->setParam("text", $figure);
            $this->widget->show($login);
        }
    }

    public function LibXmlRpc_OnStunt($login,$points,$combo,$totalScore,$factor,$stuntname,$angle,$isStraight,$isReversed,$isMasterJump) {
        $stuntname = str_replace("::EStuntFigure::", "", $stuntname);

        if ($angle || ($stuntname != "StraightJump" && $stuntname != "RespawnPenalty")) {
            if ($isReversed) {
                $stuntname = "Reversed" . $stuntname;
            }
            if ($angle == 0) {
                $angle = "";
            }
            $split = preg_split('/(?=\p{Lu})/u', $stuntname);
            $stuntname = implode(" ", $split) . " " . $angle;

            $this->widget->setPosition($this->config->stuntWidget_PosX, $this->config->stuntWidget_PosY, 10);
            $this->widget->setParam("text", $stuntname);
            $this->widget->show($login);
        }
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
    }
}
