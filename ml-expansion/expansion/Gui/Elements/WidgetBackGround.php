<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

class WidgetBackGround extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    public function __construct($sizeX, $sizeY, $action = null)
    {
        /** @var Config $config */
        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $xml = '<quad sizen="' . $sizeX + (float)$config->style_list_sizeXOffset . ' ' . $sizeY + (float)$config->style_list_sizeYOffset . '" bgcolor="' . $config->style_widget_bgColorize . '" opacity=" ' . $config->style_widget_bgOpacity . '"';
        if ($action != null) {
            $xml .= ' action="' . $action . '"';
        }
        $xml .= '/>';

        $bg = new \ManiaLive\Gui\Elements\Xml();
        $bg->setContent($xml);
        $this->addComponent($bg);
    }
}
