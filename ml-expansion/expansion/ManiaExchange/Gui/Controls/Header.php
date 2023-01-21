<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls;

class Header extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $label;
    protected $time;
    protected $frame;

    public function __construct()
    {
        $sizeX = 120;
        $sizeY = 6;

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(18, 4);
        $spacer->setAlign("left", "center2");
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(2, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        $this->label = new \ManiaLib\Gui\Elements\Label(60, 4);
        $this->label->setAlign('left', 'center');
        $this->label->setText(__("Map name"));
        $this->frame->addComponent($this->label);

        $info = new \ManiaLib\Gui\Elements\Label(25, 4);
        $info->setAlign('left', 'center');
        $info->setText(__("Author"));
        $this->frame->addComponent($info);

        $this->time = new \ManiaLib\Gui\Elements\Label(20, 4);
        $this->time->setAlign('left', 'center');
        $this->time->setText(__("Length"));
        $this->frame->addComponent($this->time);


        $spacer = new \ManiaLib\Gui\Elements\Quad(4, 4);
        $spacer->setAlign('left', 'center');
        $spacer->setStyle("Icons64x64_1");
        $spacer->setSubStyle("StateSuggested");
        $this->frame->addComponent($spacer);


        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(16, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        $this->frame->setSize($this->sizeX, $this->sizeY);
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }

    public function destroy()
    {
        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
