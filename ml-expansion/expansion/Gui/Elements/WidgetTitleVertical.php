<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

/**
 * Description of ListBackGround
 *
 * @author oliverde8
 */
class WidgetTitle extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $bg;
    protected $bgStr;
    protected $lbl_title;
    protected $config;

    public function __construct($sizeX, $sizeY)
    {
        /** @var Config $config */
        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $quad = new \ManiaLib\Gui\Elements\Quad();
        $quad->setColorize($config->style_widget_title_bgColorize);
        $quad->setOpacity($config->style_widget_title_bgOpacity);
        $quad->setPosition($config->style_widget_title_bgXOffset, $config->style_widget_title_bgYOffset);

        $this->bg = clone $quad;
        $this->bg->setStyle('Bgs1InRace');
        $this->bg->setSubStyle('BgWindow4');
        $this->addComponent($this->bg);

        $this->lbl_title = new DicoLabel($sizeX, $sizeY);
        $this->lbl_title->setTextSize($config->style_widget_title_lbSize);
        $this->lbl_title->setTextColor($config->style_widget_title_lbColor);
        $this->lbl_title->setAttribute("rot", 90);
        $this->lbl_title->setAlign("center", "center");
        $this->addComponent($this->lbl_title);

        $this->setSize($sizeX, $sizeY);
    }

    public function onResize($oldX, $oldY)
    {

        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();
        $this->bg->setSize($this->sizeX, $this->sizeY);

        $this->lbl_title->setSizeX($this->sizeX - 2);
        $this->lbl_title->setPosition(($this->sizeX / 2), -1.5);
    }

    public function setAction($action)
    {
        $this->bg->setAction($action);
    }

    public function setText($text)
    {
        $this->lbl_title->setText($text);
    }

    public function setOpacity($opacity)
    {
        $this->bg->setOpacity($opacity);
    }
}
