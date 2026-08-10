<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Dropdown
{
    protected static $counter = 0;

    public static function getXML($mlClass, $name, $itemsVarName = null, $selectedIndex = 0, $sizeX = 35)
    {
        if ($mlClass) {
            $items = $mlClass->getParam($itemsVarName);
            if (!is_array($items)) {
                $items = array("initial");
            }
        } else {
            $items = $itemsVarName;
        }

        $dropdownIndex = self::$counter++;
        if (self::$counter > 100000) {
            self::$counter = 0;
        }

        $itemHandlers = "";
        foreach ($items as $x => $item) {
            $itemHandlers .= '        if (Event.Type == CMlEvent::Type::MouseClick && Event.ControlId == "' . $name . $x . '") {' . "\n";
            $itemHandlers .= '            Label' . $dropdownIndex . '.Value = "' . $item . '";' . "\n";
            $itemHandlers .= '            Output' . $dropdownIndex . '.Value = "' . $x . '";' . "\n";
            $itemHandlers .= '            Frame' . $dropdownIndex . '.Hide();' . "\n";
            $itemHandlers .= '        }' . "\n";
        }

        $xml = '<frame>
            <entry id="' . $name . 'e" posn="1000 1000 0" sizen="' . $sizeX . ' 6" style="" scriptevents="1" textsize="1" textcolor="000" name="' . $name . '"/>
            <label id="' . $name . 'l" posn="0 0 4" sizen="' . $sizeX . ' 4" halign="left" valign="center" bgcolor="000" bgcolorfocus="3af" scriptevents="1" textsize="1" text="' . $items[$selectedIndex] . '" focusareacolor1="000" focusareacolor2="3af"/>
            <frame posn="0 5.4 2" scale="0.9" id="' . $name . 'f">';

        foreach ($items as $index => $item) {
            $xml .= '<label id="' . $name . $index . '" posn="0 -' . ($index*7) . ' 5" sizen="' . $sizeX . ' 7" halign="left" valign="center" bgcolor="000" bgcolorfocus="3af" scriptevents="1" textsize="1" text="' . $item . '" focusareacolor1="000" focusareacolor2="3af"/>';
        }

        $xml .= '</frame>
        </frame>';

        $script = new Script("Gui/Scripts/DropDown");
        $script->setParam("name",          $name);
        $script->setParam("selected",      $selectedIndex);
        $script->setParam("dropdownIndex", $dropdownIndex);
        $script->setParam("itemHandlers",  $itemHandlers);

        if ($mlClass) {
            $mlClass->registerElementScript($script, $name . ':' . implode(',', $items) . ':' . $selectedIndex);
        } else {
            return array($xml, $script);
        }

        return $xml;
    }
}
