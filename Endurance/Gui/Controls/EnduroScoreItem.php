<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Gui\Controls;

use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;

class EnduroScoreItem extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    protected $label_rank;
    protected $label_nick;
    protected $label_score;
    protected $label_login;
    protected $label_cp;
    protected $bg;
    protected $button_report;

    public function __construct($indexNumber, $login, $name, $score, $currentCp) {
        $this->sizeY = 3.5;
        $this->bg = new ListBackGround($indexNumber, 100, 3.5);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize(100, 6);
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
        $this->label_score->setText($score);
        $this->frame->addComponent($this->label_score);

        $this->label_nickname = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_nickname->setAlign('left', 'center');
        $this->label_nickname->setScale(0.8);
        $this->label_nickname->setText($name);
        $this->frame->addComponent($this->label_nickname);

        $this->label_login = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_login->setAlign('left', 'center');
        $this->label_login->setScale(0.8);
        $this->label_login->setText($login);
        $this->frame->addComponent($this->label_login);

        $this->label_cp = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_cp->setAlign('left', 'center');
        $this->label_cp->setScale(0.8);
        $this->label_cp->setText($currentCp + 1);
        $this->frame->addComponent($this->label_cp);
    }

    public function onResize($oldX, $oldY)
    {
        $this->bg->setSizeX(195);
        $this->label_place->setSizeX(35);
        $this->label_score->setSizeX(50);
        $this->label_nickname->setSizeX(62);
        $this->label_login->setSizeX(60);
        $this->label_cp->setSizeX(35);
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
