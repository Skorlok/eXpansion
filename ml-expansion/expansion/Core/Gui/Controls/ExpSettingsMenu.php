<?php

namespace ManiaLivePlugins\eXpansion\Core\Gui\Controls;

class ExpSettingsMenu extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    public $frame;
    private $nbElements = 0;


    public function __construct()
    {
        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($this->getSizeX(), 4);
        $this->frame->setPosition(-2, 0);
        $this->addComponent($this->frame);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setSize($this->getSizeX() / 0.8, 4);
    }

    public function reset()
    {
        $this->frame->destroyComponents();
        $this->nbElements = 0;
    }

    public function addItem($label, $action, $color = null)
    {
        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="0 -' . ($this->nbElements*6) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($this->getSizeX() / 0.8, 6, $label, null, null, ($color ? $color : null), null, null, $action, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($button);
        $this->nbElements++;
    }
}
