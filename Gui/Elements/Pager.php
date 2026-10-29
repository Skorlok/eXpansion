<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class Pager
{

    /**
     * Generate the Pager XML structure (scrollable container + scrollbar chrome).
     *
     * @param Window $mlClass   ManiaLink class name (e.g. "ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window")
     * @param float  $sizeX  Total width
     * @param float  $sizeY  Total height
     * @param float  $itemSizeY  Height of one item row
     * @return string
     */
    public static function getXML($mlClass, $sizeX = 100, $sizeY = 50, $itemSizeY = 6)
    {
        if (!is_object($mlClass)) {
            Helper::logError('Pager: Invalid $mlClass parameter', array("Gui", "Pager"));
            return "";
        }
        if (!$mlClass instanceof Window) {
            Helper::logError('Pager: $mlClass parameter must be an instance of ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window', array("Gui", "Pager"));
            return "";
        }

        $xml  = '<frame posn="' . ($sizeX - 3) . ' -5 0">';
        $xml .= '<quad id="ScrollBg" posn="0 0 0" sizen="4 ' . ($sizeY - 9) . '" halign="center" valign="top" style="Bgs1InRace" substyle="BgPlayerCard" opacity="0.9"/>';
        $xml .= '<quad id="ScrollBar" posn="0 0 1" sizen="3 15" halign="center" valign="top" style="BgsPlayerCard" substyle="BgRacePlayerName" scriptevents="1"/>';
        $xml .= '<quad id="ScrollDown" posn="0 -' . ($sizeY - 10) . ' 0" sizen="6.5 6.5" halign="center" valign="top" style="Icons64x64_1" substyle="ArrowDown" scriptevents="1"/>';
        $xml .= '<quad id="ScrollUp" posn="0 -1 0" sizen="6.5 6.5" halign="center" valign="bottom" style="Icons64x64_1" substyle="ArrowUp" scriptevents="1"/>';
        $xml .= '</frame>';

        $script = new Script("Gui\Scripts\Pager");
        $script->setParam("sizeY",      $script->getNumber($itemSizeY));
        $script->setParam("pagerSizeY", $script->getNumber($sizeY));
        $mlClass->registerOrOverrideScript($script);

        return $xml;
    }
}
