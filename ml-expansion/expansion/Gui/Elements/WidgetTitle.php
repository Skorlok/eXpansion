<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class WidgetTitle extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    public function __construct($sizeX, $sizeY, $text = null, $id = null)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $bg = new \ManiaLib\Gui\Elements\Quad();
        $bg->setColorize($config->style_widget_title_bgColorize);
        $bg->setOpacity($config->style_widget_title_bgOpacity);
        $bg->setPosition($config->style_widget_title_bgXOffset, $config->style_widget_title_bgYOffset);
        $bg->setStyle('Bgs1InRace');
        $bg->setSubStyle('BgWindow4');
        $bg->setAlign("center", "center");
        $bg->setSize($sizeX, 4);
        $bg->setPosition(($sizeX / 2), -1.5);
        if ($id !== null) {
            $bg->setId($id);
            $bg->setScriptEvents();
        }
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
}
