<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;

class Dropdown
{
    public static function getXML($callerClass, $name, $items = array("initial"), $selectedIndex = 0, $sizeX = 35)
    {
        $xml = '<frame>
            <entry id="' . $name . 'e" posn="1000 1000 0" sizen="' . $sizeX . ' 6" style="" scriptevents="1" textsize="1" textcolor="000" name="' . $name . '"/>
            <label id="' . $name . 'l" posn="0 0 4" sizen="' . $sizeX . ' 4" halign="left" valign="center" bgcolor="000" bgcolorfocus="3af" scriptevents="1" textsize="1" text="' . $items[$selectedIndex] . '" focusareacolor1="000" focusareacolor2="3af"/>
            <frame posn="0 5.4 2" scale="0.9" id="' . $name . 'f">';

        foreach ($items as $index => $item) {
            $xml .= '<label id="' . $name . $index . '" posn="0 -' . ($index*7) . ' 5" sizen="' . $sizeX . ' 7" halign="left" valign="center" bgcolor="000" bgcolorfocus="3af" scriptevents="1" textsize="1" text="' . $item . '" focusareacolor1="000" focusareacolor2="3af"/>';
        }

        $xml .='</frame>
        </frame>';

        $script = new \ManiaLivePlugins\eXpansion\Gui\Scripts\DropDownScript();
        $script->setParam("name", $name);
        $script->setParam("values", $items);
        $script->setParam("selected", $selectedIndex);
        if ($callerClass instanceof Widget) {
            $callerClass->registerScript($script);
        } else {
            return array($xml, $script);
        }

        return $xml;
    }
}
