<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class ClubLinksSetup extends Window
{
    public static $mainPlugin;
    
    public function onConstruct()
    {
        parent::onConstruct();

        $input = new \ManiaLive\Gui\Elements\Xml();
        $input->setContent('<frame posn="0 -8 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team1Clublink", 35, true, "Link to the Clublink for the first team", null, null, null) . '</frame>');
        $this->addComponent($input);

        $input = new \ManiaLive\Gui\Elements\Xml();
        $input->setContent('<frame posn="0 -20 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team2Clublink", 35, true, "Link to the Clublink for the second team", null, null, null) . '</frame>');
        $this->addComponent($input);

        $label = new \ManiaLive\Gui\Elements\Xml();
        $label->setContent('<label posn="0 -26 1" sizen="50 12" style="TextStaticSmall" text="These settings will override&#10; the team colors settings !"/>');
        $this->addComponent($label);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="5 -47 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Ok", null, null, null, null, null, $this->createAction(array($this, "Ok")), null, null, null, null, null, null) . '</frame>');
        $this->addComponent($button);
    }

    public function Ok($login, $data)
    {
        $this->EraseAll();

        self::$mainPlugin->getClubLinks($login, $data);
    }
}
