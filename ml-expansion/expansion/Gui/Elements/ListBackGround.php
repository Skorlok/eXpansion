<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class ListBackGround extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $bg;
    protected $config;

    public function __construct($indexNumber, $sizeX, $sizeY)
    {
        $config = Config::getInstance();
        /** @var Config $config */
        $this->config = $config;

        $this->bg = new \ManiaLib\Gui\Elements\Quad($sizeX, $sizeY);
        $this->bg->setAlign('left', 'center');
        $this->bg->setOpacity(0.8);

        if (sizeof($config->style_list_bgStyle) == sizeof($config->style_list_bgSubStyle) && sizeof($config->style_list_bgStyle) > 0) {
            $this->bg->setStyle($config->style_list_bgStyle[$indexNumber % sizeof($config->style_list_bgStyle)]);
            $this->bg->setSubStyle($config->style_list_bgSubStyle[$indexNumber % sizeof($config->style_list_bgSubStyle)]);
            $this->bg->setModulateColor($config->style_list_bgColor[$indexNumber % sizeof($config->style_list_bgColor)]);
        }

        $this->bg->setPosition($config->style_list_posXOffset, $config->style_list_posYOffset);

        $this->addComponent($this->bg);
        $this->setSize($sizeX, $sizeY);
    }

    public function setAction($action)
    {
        $this->bg->setAction($action);
    }

    public function onResize($oldX, $oldY)
    {
        $this->bg->setSize($this->getSizeX() + (float)$this->config->style_list_sizeXOffset, $this->getSizeY() + (float)$this->config->style_list_sizeYOffset);
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }

    public function destroy()
    {
        $this->config = null;
    }

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
