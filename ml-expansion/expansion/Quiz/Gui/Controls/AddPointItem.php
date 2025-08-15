<?php

namespace ManiaLivePlugins\eXpansion\Quiz\Gui\Controls;

class AddPointItem extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $bg;

    protected $addpointButton;
    protected $removepointButton;

    protected $nickname;

    protected $addpointAction;

    protected $removepointAction;

    protected $pointsLabel;

    protected $frame;


    public function __construct($indexNumber, \ManiaLive\Data\Player $player, $controller, $isAdmin, $login, $sizeX)
    {
        $sizeY = 6;

        if ($isAdmin) {
            $this->addpointAction = $this->createAction(array($controller, 'addPoint'), $player->login);
            $this->removepointAction = $this->createAction(array($controller, 'removePoint'), $player->login);
        }

        $this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);


        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);

        $this->login = new \ManiaLib\Gui\Elements\Label(20, 4);
        $this->login->setAlign('left', 'center');
        $this->login->setText($player->login);
        $this->login->setScale(0.8);
        $this->frame->addComponent($this->login);

        $this->nickname = new \ManiaLib\Gui\Elements\Label(30, 4);
        $this->nickname->setAlign('left', 'center');
        $this->nickname->setScale(0.8);
        $this->nickname->setText($player->nickName);
        $this->frame->addComponent($this->nickname);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        // admin additions
        if ($isAdmin) {
            $this->removepointButton = new \ManiaLive\Gui\Elements\Xml();
            $this->removepointButton->setContent('<frame posn="44 0 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(15, 5, "-1", null, null, "a00", "fff", null, $this->removepointAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->removepointButton);

            $this->pointsLabel = new \ManiaLib\Gui\Elements\Label(10, 5);
            $this->pointsLabel->setTextSize(1);
            $this->pointsLabel->setPosX(13.5);
            $this->pointsLabel->setAlign("center", "center");
            $this->pointsLabel->setText("0");
            $this->frame->addComponent($this->pointsLabel);

            $this->addpointButton = new \ManiaLive\Gui\Elements\Xml();
            $this->addpointButton->setContent('<frame posn="62.5 0 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(15, 5, "+1", null, null, "2a2", "fff", null, $this->addpointAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->addpointButton);
        }

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        $this->frame->setSize($this->sizeX, $this->sizeY);
        $this->bg->setPosX(-2);
        $this->bg->setSize($this->sizeX, $this->sizeY);
        parent::onResize($oldX, $oldY);
    }

    public function setPoints($point)
    {
        $this->pointsLabel->setText($point);
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
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
