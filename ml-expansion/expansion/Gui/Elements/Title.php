<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

/**
 * Description of ListBackGround
 *
 * @author oliverde8
 */
class Title extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    protected $bg;
    protected $label;
    protected $config;

    public function __construct($sizeX, $sizeY)
    {
        $this->bg = new TitleBackGround($sizeX, $sizeY);
        $this->bg->setPosY(-2);

        $this->addComponent($this->bg);

        $this->label = new \ManiaLib\Gui\Elements\Label($sizeX, $sizeY);
        $this->label->setAlign("Left", "buttom");
        $this->addComponent($this->label);

        $this->setSize($sizeX, $sizeY);
    }

    public function onResize($oldX, $oldY)
    {
        $this->bg->setSize($this->getSizeX(), $this->getSizeY());
        $this->label->setSize($this->getSizeX(), $this->getSizeY());
    }

    public function setText($text)
    {
        $this->label->setText($text);
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }
}
