<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class ListBackGround
{
    public static function getXML($indexNumber, $sizeX, $sizeY, $action = null)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $w = $sizeX + (float)$config->style_list_sizeXOffset;
        $h = $sizeY + (float)$config->style_list_sizeYOffset;

        $style    = '';
        $substyle = '';
        $color    = '';

        if (sizeof($config->style_list_bgStyle) == sizeof($config->style_list_bgSubStyle) && sizeof($config->style_list_bgStyle) > 0) {
            $idx      = $indexNumber % sizeof($config->style_list_bgStyle);
            $style    = $config->style_list_bgStyle[$idx];
            $substyle = $config->style_list_bgSubStyle[$idx];
            $color    = $config->style_list_bgColor[$idx];
        }

        $xml = '<quad posn="' . $config->style_list_posXOffset . ' ' . $config->style_list_posYOffset . ' 0" sizen="' . $w . ' ' . $h . '" halign="left" valign="center" opacity="0.8"';

        if ($style !== '') {
            $xml .= ' style="' . $style . '" substyle="' . $substyle . '" modulatecolor="' . $color . '"';
        }

        if ($action !== null) {
            $xml .= ' action="' . $action . '"';
        }

        return $xml . '/>';
    }
}
