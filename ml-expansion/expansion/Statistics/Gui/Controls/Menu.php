<?php

namespace ManiaLivePlugins\eXpansion\Statistics\Gui\Controls;

class Menu extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    public $frame;
    public static $nbButtons = 0;


    public function __construct()
    {
        self::$nbButtons = 0;
        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($this->getSizeX(), 4);
        $this->frame->setPosY(0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column());
        $this->addComponent($this->frame);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setSize($this->getSizeX(), 4);
    }

    public function addItem($label, $action, $color = null)
    {
        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="0 -' . (self::$nbButtons*6) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($this->getSizeX(), 6, $label, null, null, $color, null, null, $action, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($button);

        self::$nbButtons++;
    }
}
