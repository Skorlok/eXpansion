<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Elements\Pager;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class GenericPlayerList extends Window
{
    /** @var  Pager */
    protected $pager;
    /** @var  Inputbox */
    protected $inputbox;
    protected $button;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->inputbox = new Inputbox("login", 50);
        $this->inputbox->setPosition(0, -6);
        $this->inputbox->setLabel("Login to add");
        $this->addComponent($this->inputbox);

        $this->pager = new Pager();
        $this->addComponent($this->pager);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX - 5, $this->sizeY - 20);
        $this->pager->setPosition(2, -12);
    }

    /**
     *
     * @param array $items
     */
    public function populateList($items)
    {
        $this->pager->clearItems();

        foreach ($items as $item) {
            $this->pager->addItem($item);
        }
    }

    public function setAction($action)
    {
        $this->button = new \ManiaLive\Gui\Elements\Xml();
        $this->button->setContent('<frame posn="55 -6 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("add", $this->getRecipient()), null, null, null, null, null, $action, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->button);
    }
}
