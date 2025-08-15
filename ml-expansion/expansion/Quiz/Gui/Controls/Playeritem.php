<?php

namespace ManiaLivePlugins\eXpansion\Quiz\Gui\Controls;

use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Quiz\Structures\QuizPlayer;

class Playeritem extends Control
{

    protected $background;

    protected $addpointButton;

    protected $removepointButton;

    protected $nickname;

    protected $addpointAction;

    protected $removeAction;

    protected $frame;

    protected $points;

    protected $isAdmin;

    public function __construct($indexNumber, QuizPlayer $player, $controller, $isAdmin, $login, $sizeX)
    {
        $sizeY = 6;
        $this->isAdmin = $isAdmin;

        if ($isAdmin) {
            $this->addpointAction = $this->createAction(array($controller, 'addPoint'), $player->login);
            $this->removeAction = $this->createAction(array($controller, 'removePoint'), $player->login);
        }

        $this->background = new ListBackGround($indexNumber, 90, $sizeY);
        $this->addComponent($this->background);

        $this->frame = new Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new Line());

        $spacer = new Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(Icons64x64_1::EmptyIcon);

        $this->login = new Label(20, 4);
        $this->login->setAlign('left', 'center');
        $this->login->setText($player->login);
        $this->login->setScale(0.8);
        $this->frame->addComponent($this->login);

        $this->nickname = new Label(30, 4);
        $this->nickname->setAlign('left', 'center');
        $this->nickname->setScale(0.8);
        $this->nickname->setText($player->nickName);
        $this->frame->addComponent($this->nickname);

        $spacer = new Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        $this->points = new Label(12, 4);
        $this->points->setAlign('left', 'center');
        $this->points->setScale(0.8);
        $this->points->setText($player->points);
        $this->frame->addComponent($this->points);


        // admin additions
        if ($this->isAdmin) {
            $this->removepointButton = new \ManiaLive\Gui\Elements\Xml();
            $this->removepointButton->setContent('<frame posn="53.6 0 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(15, 5, "-1", null, null, "a22", "fff", null, $this->removeAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->removepointButton);

            $this->addpointButton = new \ManiaLive\Gui\Elements\Xml();
            $this->addpointButton->setContent('<frame posn="62.1 0 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(15, 5, "+1", null, null, "2a2", "fff", null, $this->addpointAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->addpointButton);
        }

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setSize($this->sizeX, $this->sizeY);
        $this->background->setSize($this->getSizeX(), $this->getSizeY());
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }

    /*
     * custom function to remove contents.
     */

    public function erase()
    {
        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
