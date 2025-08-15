<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Windows;

use ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Controls\InfoLine;

/**
 * Server Control panel Main window
 *
 * @author Petri
 */
class StatsWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    /** @var \ManiaLivePlugins\eXpansion\ServerStatistics\ServerStatistics */
    public static $mainPlugin;
    protected $frame;
    protected $contentFrame;
    protected $btn1;
    protected $btn2;
    protected $btn3;
    protected $btnDb;

    public function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->btn1 = new \ManiaLive\Gui\Elements\Xml();
        $this->btn1->setContent('<frame posn="0 0 1" scale="1.066666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(6, 6, null, array(__("Number of Players statistics", $login), 30), null, null, null, null, \ManiaLivePlugins\eXpansion\ServerStatistics\ServerStatistics::$serverPlayerAction, null, null, array("Icons128x128_1", 'Rankings'), null, null, null) . '</frame>');
        $this->frame->addComponent($this->btn1);

        $this->btn2 = new \ManiaLive\Gui\Elements\Xml();
        $this->btn2->setContent('<frame posn="6.4 0 1" scale="1.066666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(6, 6, null, array(__("Memory usage statistics", $login), 30), null, null, null, null, \ManiaLivePlugins\eXpansion\ServerStatistics\ServerStatistics::$serverMemAction, null, null, "http://files.oliver-decramer.com/data/maniaplanet/images/eXpansion/ramStat.png", null, null, null) . '</frame>');
        $this->frame->addComponent($this->btn2);

        $this->btn3 = new \ManiaLive\Gui\Elements\Xml();
        $this->btn3->setContent('<frame posn="12.8 0 1" scale="1.066666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(6, 6, null, array(__("Cpu  usage statistic", $login), 30), null, null, null, null, \ManiaLivePlugins\eXpansion\ServerStatistics\ServerStatistics::$serverCpuAction, null, null, "http://files.oliver-decramer.com/data/maniaplanet/images/eXpansion/cpuStat.png", null, null, null) . '</frame>');
        $this->frame->addComponent($this->btn3);

        $this->mainFrame->addComponent($this->frame);

        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
    }

    public function setData($data, \ManiaLive\Data\Storage $storage)
    {
        $this->contentFrame = new \ManiaLive\Gui\Controls\Frame();
        $this->contentFrame->setLayout(new \ManiaLib\Gui\Layouts\Column(80, 100));
        $this->contentFrame->setScale(.8);
        $this->mainFrame->addComponent($this->contentFrame);
        $this->contentFrame->setPositionY(5);

        $this->contentFrame->addComponent(new InfoLine(25, 'Comment', \ManiaLivePlugins\eXpansion\Gui\Gui::fixString($storage->server->comment, true), 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'Dedicated Up Time', $data['upTimeDedi'], 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'eXpansaion Up Time', $data['upTime'], 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'Map Count', sizeof($storage->maps), 0));

        $this->contentFrame->addComponent(new InfoLine(5, 'Max Players', $storage->server->currentMaxPlayers, 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'Average Players', $data['avgPlayer'], 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'Max Spectators', $storage->server->currentMaxPlayers, 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'Average Spectators', $data['avgSpec'], 0));
        $this->contentFrame->addComponent(new InfoLine(5, 'Ladder Limit', $storage->server->ladderServerLimitMin . ' - ' . $storage->server->ladderServerLimitMax, 0));

        $label = new \ManiaLib\Gui\Elements\Label(70, 12);
        $label->setText("Visited by " . $data['nbPlayer'] . ' players from ' . $data['nbNation'] . ' zones');
        $this->contentFrame->addComponent($label);

        $label = new \ManiaLib\Gui\Elements\Label(70, 12);
        $label->setText('Who together played ' . $data['totalPlayersTimes']);
        $label->setPositionY(7.5);
        $this->contentFrame->addComponent($label);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setPosition(2, -($this->sizeY - 6));
    }

    public function destroy()
    {
        $this->frame->clearComponents();
        $this->connection = null;
        $this->storage = null;

        parent::destroy();
    }
}