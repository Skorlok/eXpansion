<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;

use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Controls\Frame;
use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Elements\ColorChooser;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class TeamSetup extends Window
{
    public static $mainPlugin;
    protected $frame;
    
    public function onConstruct()
    {
        parent::onConstruct();

        $this->frame = new Frame(0, -8, new Column());
        $this->addComponent($this->frame);

        $input = new Inputbox("team1Name");
        $input->setLabel("Team1 Name");
        $this->frame->addComponent($input);

        $lbl = new Label(100, 30);
        $lbl->setPosition(0, 6);
        $lbl->setText("Team1 Color (DO NOT ENTER $$)");
        $lbl->setSize(35, 12);
        $this->frame->addComponent($lbl);

        $input = new ColorChooser("team1Color", 35, 3, false);
        $input->setPosX(0);
        $input->setPosY(-20);
        $this->addComponent($input);
        
        $input = new Inputbox("team2Name");
        $input->setLabel("Team2 Name");
        $this->frame->addComponent($input);

        $lbl = new Label(100, 30);
        $lbl->setPosition(0, 6);
        $lbl->setText("Team2 Color (DO NOT ENTER $$)");
        $lbl->setSize(35, 12);
        $this->frame->addComponent($lbl);

        $input = new ColorChooser("team2Color", 35, 3, false);
        $input->setPosX(0);
        $input->setPosY(-44);
        $this->addComponent($input);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="5 -51 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Ok", null, null, null, null, null, $this->createAction(array($this, "Ok")), null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($button);
    }

    public function Ok($login, $data)
    {
        $this->EraseAll();
        
        self::$mainPlugin->setTeamDisplayAfterWindow($login, $data);
    }
}
