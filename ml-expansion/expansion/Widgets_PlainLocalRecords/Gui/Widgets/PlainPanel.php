<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords\Gui\Widgets;

use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;
use ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords\Gui\Controls\Recorditem;
use ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords\Widgets_PlainLocalRecords;

class PlainPanel extends Widget
{

    /** @var Frame */
    protected $frame;

    /**
     * @var Control[]
     */
    protected $items = array();

    /**
     * @var Quad
     */
    protected $bgborder;
    protected $bg;
    protected $bgTitle;
    protected $bgFirst;

    /**
     * @var Button
     */
    protected $layer;

    /** @var Storage */
    public $storage;

    public $timeScript;

    protected $nbFields;

    protected function eXpOnBeginConstruct()
    {
        $this->setName("Local Scores Panel");
    }

    protected function eXpOnEndConstruct()
    {
        $sizeX = 46;
        $sizeY = 95;
        $this->setScriptEvents();
        $this->storage = Storage::getInstance();


        $this->storage = Storage::getInstance();

        $this->_windowFrame = new Frame();
        $this->_windowFrame->setAlign("left", "top");
        $this->_windowFrame->setId("Frame");
        $this->_windowFrame->setScriptEvents(true);
        $this->addComponent($this->_windowFrame);

        $this->bg = new WidgetBackGround($sizeX, $sizeY);
        $this->_windowFrame->addComponent($this->bg);

        $this->bgTitle = new WidgetTitle($sizeX, $sizeY + 2);
        $this->_windowFrame->addComponent($this->bgTitle);

        $this->frame = new Frame();
        $this->frame->setAlign("left", "top");
        $this->frame->setLayout(new Column(-1));
        $this->_windowFrame->addComponent($this->frame);
        $this->setSize($sizeX, $sizeY);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->_windowFrame->setSize($this->sizeX, $this->sizeY);

        $this->bg->setSize($this->sizeX, $this->sizeY + 1);
        $this->bgTitle->setSize($this->sizeX, 4.2);

        $this->frame->setPosition(($this->sizeX / 2) + 1, -5);
    }

    public function update()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = array();
        $this->frame->clearComponents();

        $index = 1;

        $this->bgTitle->setText(eXpGetMessage('Best Scores'));


        $recsData = "";
        $nickData = "";

        for ($index = 1; $index <= $this->nbFields; $index++) {

        }

        $index = 1;

        foreach (Widgets_PlainLocalRecords::$localrecords as $record) {
            if ($index > 23) {
                break;
            }
            $this->items[$index - 1] = new Recorditem($index, $record);
            $this->frame->addComponent($this->items[$index - 1]);
            $index++;
        }
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }

        $this->items = array();

        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
