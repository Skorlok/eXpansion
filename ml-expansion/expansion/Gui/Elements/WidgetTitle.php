<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class WidgetTitle extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    public function __construct($sizeX, $sizeY, $text = null, $id = null)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $bg = new \ManiaLive\Gui\Elements\Xml();
        $bg->setContent('<quad ' . ($id ? 'id="' . $id . '" scriptevents="1" ' : '') . 'posn="' . ($sizeX / 2) . ' -1.5 0" sizen="' . $sizeX . ' 4" halign="center" valign="center" style="Bgs1InRace" substyle="BgWindow4" opacity="' . $config->style_widget_title_bgOpacity . '" colorize="' . $config->style_widget_title_bgColorize . '"/>');
        $this->addComponent($bg);

        $lbl_title = new DicoLabel($sizeX, $sizeY);
        $lbl_title->setTextSize($config->style_widget_title_lbSize);
        $lbl_title->setTextColor($config->style_widget_title_lbColor);
        $lbl_title->setAlign("center", "center");
        $lbl_title->setStyle("TextCardScores2");
        $lbl_title->setId("widgetTitle");
        $lbl_title->setSizeX($sizeX - 2);
        $lbl_title->setPosition(($sizeX / 2), -1.5);
        if ($text !== null) {
            $lbl_title->setText($text);
        }
        $this->addComponent($lbl_title);
    }

    public static function getXML($sizeX, $sizeY, $text = null, $id = null)
    {
        /** @var Config $config */
        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();
        $xml =  '<quad ' . ($id ? 'id="' . $id . '" scriptevents="1" ' : '') . 'posn="' . ($sizeX / 2) . ' -1.5 0" sizen="' . $sizeX . ' 4" halign="center" valign="center" style="Bgs1InRace" substyle="BgWindow4" opacity="' . $config->style_widget_title_bgOpacity . '" colorize="' . $config->style_widget_title_bgColorize . '"/>';
        $xml .= '<label id="widgetTitle" posn="' . ($sizeX / 2) . ' -1.5 1.0E-5" sizen="' . ($sizeX - 2) . ' ' . $sizeY . '" halign="center" valign="center" style="TextCardScores2" textsize="' . $config->style_widget_title_lbSize . '" textcolor="' . $config->style_widget_title_lbColor . '" textid="' . $text . '"/>';
        return $xml;
    }
}
