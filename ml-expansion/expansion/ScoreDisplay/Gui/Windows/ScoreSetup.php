<?php

namespace ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Config;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Widgets\Scores;

class ScoreSetup extends Window
{

    public function onConstruct()
    {
        parent::onConstruct();

        $xml = new \ManiaLive\Gui\Elements\Xml();
        $xml->setContent('<frame posn="0 -8 0">
        <frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team1Country", 35, true, "Team1 Country", null, null, null) . '</frame>
        <frame posn="0 -12 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team1Name", 35, true, "Team1 Name", null, null, null) . '</frame>
        <frame posn="0 -24 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team2Country", 35, true, "Team2 Country", null, null, null) . '</frame>
        <frame posn="0 -36 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("team2Name", 35, true, "Team2 Name", null, null, null) . '</frame>
        <frame posn="0 -48 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Ok", null, null, null, null, null, $this->createAction(array($this, "Ok")), null, null, null, null, null, null) . '</frame>
        </frame>');
        $this->addComponent($xml);
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
