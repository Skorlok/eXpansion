<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class ScrollableArea
{
    public static function getXML($sizeX = 120, $sizeY = 90, $content = '')
    {
        $areaW    = ($sizeX * 2) - 1;
        $areaH    = $sizeY + 2;
        $vx     = $sizeX - 3;
        $vBgH   = $sizeY - 5;
        $vDownY = -($sizeY - 6);

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

    public static function getScriptML()
    {
        return new Script("Gui\\Scripts\\ScrollableArea");
    }
}
