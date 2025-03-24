<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;

use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Controls\Frame;
use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class ClubLinksSetup extends Window
{
    public static $mainPlugin;
    protected $frame;
    
    public function onConstruct()
    {
        parent::onConstruct();

        $this->frame = new Frame(0, -8, new Column());
        $this->addComponent($this->frame);

        $input = new Inputbox("team1Clublink");
        $input->setLabel("Link to the Clublink for the first team");
        $this->frame->addComponent($input);
        
        $input = new Inputbox("team2Clublink");
        $input->setLabel("Link to the Clublink for the second team");
        $this->frame->addComponent($input);

        $lbl = new Label(100, 30);
        $lbl->setPosition(0, 6);
        $lbl->setText("These settings will override\n the team colors settings !");
        $lbl->setSize(50, 12);
        $this->frame->addComponent($lbl);

        $button = new Button();
        $button->setText("Ok");
        $button->setAction($this->createAction(array($this, "Ok")));
        $button->setPosX(5);
        $button->setPosY(-3);
        $this->frame->addComponent($button);
    }

    public function Ok($login, $data)
    {
        $this->EraseAll();

        self::$mainPlugin->getClubLinks($login, $data);
    }
}
