<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class WidgetBackGround
{
    public static function getXML($sizeX, $sizeY, $action = null)
    {
        /** @var Config $config */
        $config = Config::getInstance();
        return '<quad ' . ($action ? 'action="' . $action . '" ' : '') . 'sizen="' . ($sizeX + (float)$config->style_list_sizeXOffset) . ' ' . ($sizeY + (float)$config->style_list_sizeYOffset) . '" bgcolor="' . $config->style_widget_bgColorize . '" opacity=" ' . $config->style_widget_bgOpacity . '"/>';
    }
}
