<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Elements\ColorChooser;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class TeamSetup extends Window
{
    public static $mainPlugin;
    
    public function onConstruct()
    {
        parent::onConstruct();

        $input = new \ManiaLive\Gui\Elements\Xml();
        $input->setContent('<frame posn="0 -8 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team1Name", 35, true, "Team1 Name", null, null, null) . '</frame>');
        $this->addComponent($input);

        $label = new \ManiaLive\Gui\Elements\Xml();
        $label->setContent('<label posn="0 -14 2.0E-5" sizen="35 12" style="TextStaticSmall" text="Team1 Color (DO NOT ENTER $$)"/>');
        $this->addComponent($label);

        $input = new ColorChooser("team1Color", 35, 3, false);
        $input->setPosX(0);
        $input->setPosY(-20);
        $this->addComponent($input);

        $input = new \ManiaLive\Gui\Elements\Xml();
        $input->setContent('<frame posn="0 -32 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team2Name", 35, true, "Team2 Name", null, null, null) . '</frame>');
        $this->addComponent($input);

        $label = new \ManiaLive\Gui\Elements\Xml();
        $label->setContent('<label posn="0 -38 5.0E-5" sizen="35 12" style="TextStaticSmall" text="Team2 Color (DO NOT ENTER $$)"/>');
        $this->addComponent($label);

        $input = new ColorChooser("team2Color", 35, 3, false);
        $input->setPosX(0);
        $input->setPosY(-44);
        $this->addComponent($input);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="5 -59 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Ok", null, null, null, null, null, $this->createAction(array($this, "Ok")), null, null, null, null, null, null) . '</frame>');
        $this->addComponent($button);
    }

    public function Ok($login, $data)
    {
        $this->EraseAll();
        
        self::$mainPlugin->setTeamDisplayAfterWindow($login, $data);
    }
}
