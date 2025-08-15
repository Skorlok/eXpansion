<?php

namespace ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Windows;

use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Config;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Widgets\Scores;

class ScoreSetup extends Window
{

    protected $frame;


    public function onConstruct()
    {
        parent::onConstruct();

        $this->frame = new Frame(0, -8, new Column());
        $this->addComponent($this->frame);

        $input = new Inputbox("team1Country");
        $input->setLabel("Team1 Country");
        $this->frame->addComponent($input);

        $input = new Inputbox("team1Name");
        $input->setLabel("Team1 Name");
        $this->frame->addComponent($input);

        $input = new Inputbox("team2Country");
        $input->setLabel("Team2 Country");
        $this->frame->addComponent($input);

        $input = new Inputbox("team2Name");
        $input->setLabel("Team2 Name");
        $this->frame->addComponent($input);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="0 -48 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Ok", null, null, null, null, null, $this->createAction(array($this, "Ok")), null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($button);
    }

    public function Ok($login, $data)
    {
        $this->EraseAll();
        $scores = Scores::Create(null);
        $scores->setData($data);
        $scores->setPosition(Config::getInstance()->scoreWidget_PosX, Config::getInstance()->scoreWidget_PosY);
        $scores->setName("ScoreWidget");
        $scores->setScale(0.8);
        $scores->show();
    }
}
