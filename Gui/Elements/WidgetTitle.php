<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class WidgetTitle
{
    public static function getXML($sizeX, $sizeY, $text = null, $id = null)
    {
        /** @var Config $config */
        $config = Config::getInstance();
        $xml =  '<quad ' . ($id ? 'id="' . $id . '" scriptevents="1" ' : '') . 'posn="' . ($sizeX / 2) . ' -1.5 0" sizen="' . $sizeX . ' 4" halign="center" valign="center" style="Bgs1InRace" substyle="BgWindow4" opacity="' . $config->style_widget_title_bgOpacity . '" colorize="' . $config->style_widget_title_bgColorize . '"/>';
        $xml .= '<label id="widgetTitle" posn="' . ($sizeX / 2) . ' -1.5 1.0E-5" sizen="' . ($sizeX - 2) . ' ' . $sizeY . '" halign="center" valign="center" style="TextCardScores2" textsize="' . $config->style_widget_title_lbSize . '" textcolor="' . $config->style_widget_title_lbColor . '" textid="' . $text . '"/>';
        return $xml;
    }
}
