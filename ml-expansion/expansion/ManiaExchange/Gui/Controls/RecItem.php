<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls;

use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLive\Utilities\Time;

class RecItem extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    protected $label_rank;
    protected $label_nick;
    protected $label_score;
    protected $bg;

    public function __construct($indexNumber, $name, $score) {
        $this->sizeY = 3.5;
        $this->bg = new ListBackGround($indexNumber, 25, 3.5);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize(25, 6);
        $this->frame->setPosY(0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->addComponent($this->frame);

        $this->label_place = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_place->setAlign('left', 'center');
        $this->label_place->setScale(0.8);
        $this->label_place->setText(($indexNumber + 1) . ".");
        $this->frame->addComponent($this->label_place);

        $this->label_score = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_score->setAlign('left', 'center');
        $this->label_score->setScale(0.8);
        $this->label_score->setText(Time::fromTM($score));
        $this->frame->addComponent($this->label_score);

        $this->label_nickname = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_nickname->setAlign('left', 'center');
        $this->label_nickname->setScale(0.8);
        $this->label_nickname->setText($name);
        $this->frame->addComponent($this->label_nickname);
    }

    public function onResize($oldX, $oldY)
    {
        $this->bg->setSizeX(50);
        $this->label_place->setSizeX(10);
        $this->label_score->setSizeX(20);
        $this->label_nickname->setSizeX(30);
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }

    /**
     * custom function to remove contents.
     */
    public function erase()
    {
        parent::destroy();
    }
}
