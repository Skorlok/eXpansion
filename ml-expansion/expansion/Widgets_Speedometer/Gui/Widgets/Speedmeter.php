<?php

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

namespace ManiaLivePlugins\eXpansion\Widgets_Speedometer\Gui\Widgets;

/**
 * Description of Speedmeter
 *
 * @author Reaby
 */
class Speedmeter extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{

    public function eXpOnBeginConstruct()
    {
        $this->setName("Speed'o'meter");

        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Widgets_Speedometer\Gui\Script");
        $this->registerScript($script);

        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<frame><label id="speed" posn="0 -6 0" sizen="20 6" halign="center" valign="top" style="TextStaticSmall" textsize="2" textcolor="fff" text=""/>
        <gauge id="bar" posn="0 0 1.0E-5" sizen="30 8" style="EnergyBar" color="3af" grading="0" ratio="0" drawbg="0" drawblockbg="1"/></frame>');
        $this->addComponent($widget);
    }
}
