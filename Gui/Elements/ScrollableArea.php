<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class ScrollableArea
{
    /**
     * Generate the ScrollableArea XML structure (scrollable container + scrollbar chrome).
     *
     * @param \ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window $mlClass   ManiaLink class name (e.g. "ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window")
     * @param float  $sizeX  Total width
     * @param float  $sizeY  Total height
     * @param string $contentVarName  Name of the variable containing the pre-generated XML string of content to embed in the ScrollableArea frame
     * @param float  $contentSizeY  Height of the content (used to calculate the scrollbar size)
     * @return string
     */
    public static function getXML($mlClass, $sizeX = 120, $sizeY = 90, $contentVarName = '', $contentSizeY = 0)
    {
        if (!is_object($mlClass)) {
            Helper::logError('ScrollableArea: Invalid $mlClass parameter', array("Gui", "ScrollableArea"));
            return "";
        }
        if (!$mlClass instanceof \ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window) {
            Helper::logError('ScrollableArea: $mlClass parameter must be an instance of ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window', array("Gui", "ScrollableArea"));
            return "";
        }

        $areaW    = ($sizeX * 2) - 1;
        $areaH    = $sizeY + 2;
        $vx     = $sizeX - 3;
        $vBgH   = $sizeY - 5;
        $vDownY = -($sizeY - 6);

        $content = $mlClass->getParam($contentVarName);

        $script = new Script("Gui\\Scripts\\ScrollableArea");
        $script->setParam("contentSizeY", $contentSizeY);
        $mlClass->registerOrOverrideScript($script);

        return '<frame id="scrollableArea" posn="-3 3 0"'
             . ' clip="True" clipposn="0 0" clipsizen="' . $areaW . ' ' . $areaH . '">'
             . '<frame id="content" posn="0 0 0">'
             . $content
             . '</frame>'
             . '</frame>'
             . '<quad id="scrollVBg" posn="' . $vx . ' 0 0.5" sizen="4 ' . $vBgH . '" halign="center" valign="top" style="Bgs1InRace" substyle="BgPlayerCard" opacity="0.9"/>'
             . '<quad id="scrollVBar" posn="' . $vx . ' 0 1" sizen="3 15" halign="center" valign="top" style="BgsPlayerCard" substyle="BgRacePlayerName" scriptevents="1"/>'
             . '<quad id="scrollVUp" posn="' . $vx . ' -1 1" sizen="6.5 6.5" halign="center" valign="bottom" style="Icons64x64_1" substyle="ArrowUp" scriptevents="1"/>'
             . '<quad id="scrollVDown" posn="' . $vx . ' ' . $vDownY . ' 1" sizen="6.5 6.5" halign="center" valign="top" style="Icons64x64_1" substyle="ArrowDown" scriptevents="1"/>';
    }
}
