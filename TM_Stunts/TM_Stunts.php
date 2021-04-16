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

/**
 * Description of TM_Stunts
 *
 * @author Reaby
 */
class TM_Stunts extends ExpPlugin
{

    private $stuntWindow;

    private $counter;

    public function eXpOnReady()
    {
        $this->stuntWindow = Gui\Widgets\StuntWidget::Create(null, true);
        $this->stuntWindow->setSize(60, 12);
        $this->stuntWindow->setPosition(-30, 58);
        $this->enableScriptEvents("LibXmlRpc_OnStunt");
        $this->enableScriptEvents("Trackmania.Event.Stunt");
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Trackmania.Event.Stunt":
                call_user_func_array(array($this, "LibXmlRpc_OnStunt"),
                    json_decode($array[0], true)
                );
                break;
        }
    }

    public function onTick()
    {
        if ($this->counter % 10 == 0) {
            $this->stuntWindow->setLabels("StuntName 180", "");
            $this->stuntWindow->show("reaby");
        }

        $this->counter++;
    }

    public function LibXmlRpc_OnStunt(
        $login,
        $points,
        $combo,
        $stuntsscore,
        $factor,
        $figure,
        $angle,
        $isstraight,
        $isreversed,
        $ismasterjump
    ) {
        $figure = str_replace("::EStuntFigure::", "", $figure);

        if ($angle || ($figure != "StraightJump" && $figure != "RespawnPenalty")) {
            if ($isreversed) {
                $figure = "Reversed" . $figure;
            }
            if ($angle == 0) {
                $angle = "";
            }
            $split = preg_split('/(?=\p{Lu})/u', $figure);
            $figure = implode(" ", $split) . " " . $angle;
            $this->stuntWindow->setLabels($figure, $points);
            $this->stuntWindow->show($login);
        }
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
    }
}
