<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Gui\Controls\CheckpointElem;
use ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints\Config;

class BestCpPanel extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    protected $cps = array();
    protected $frame;

    protected function eXpOnBeginConstruct()
    {
        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Flow(220, 20));
        $this->frame->setSize(220, 20);
        $this->frame->setPosY(-2);
        $this->addComponent($this->frame);
        $this->setName("Best CheckPoints Widget");
    }

    public function onDraw()
    {
        parent::onDraw();
    }

    public function populateList($checkpoints)
    {
        foreach ($this->cps as $cp) {
            $cp->destroy();
        }

        $this->cps = array();
        $this->frame->clearComponents();

        for ($x = 0; $x < Config::getInstance()->CPNumber && $x < count($checkpoints); $x++) {
            $this->cps[$x] = new CheckpointElem($x, $checkpoints[$x]);
            $this->frame->addComponent($this->cps[$x]);
        }
    }

    public function destroy()
    {
        foreach ($this->cps as $cp) {
            $cp->destroy();
        }
        $this->cps = array();
        $this->destroyComponents();

        parent::destroy();
    }
}
