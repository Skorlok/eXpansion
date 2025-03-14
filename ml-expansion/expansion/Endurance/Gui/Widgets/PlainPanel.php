<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Gui\Widgets;

use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button as myButton;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;
use ManiaLivePlugins\eXpansion\Endurance\Gui\Controls\EnduroRecorditem;
use ManiaLivePlugins\eXpansion\Endurance\Gui\Scripts\PlayerFinish;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;

class PlainPanel extends Widget
{
    /** @var Frame */
    protected $frame;

    /**
     * @var Control[]
     */
    protected $items = array();

    /**
     * @var Quad
     */
    public $bgborder;
    public $bg;
    public $bgTitle;
    public $bgFirst;

    /**
     * @var Button
     */
    protected $layer;

    /** @var Storage */
    public $storage;
    public $timeScript;
    protected $nbFields;
    protected $firstNbFields;
    public $trayWidget;
    public $edgeWidget;

    protected function eXpOnBeginConstruct()
    {
        $sizeX = 46;
        $sizeY = 95;
        $this->setScriptEvents();
        $this->storage = Storage::getInstance();
        $this->setName("Enduro Panel");
        $this->registerScript($this->getScript());

        $this->storage = Storage::getInstance();

        $this->_windowFrame = new Frame();
        $this->_windowFrame->setAlign("left", "top");
        $this->_windowFrame->setId("Frame");
        $this->_windowFrame->setScriptEvents(true);
        $this->addComponent($this->_windowFrame);

        $this->bg = new WidgetBackGround($sizeX, $sizeY);
        $this->bg->setAction(\ManiaLivePlugins\eXpansion\Endurance\Endurance::$openScoresAction);
        $this->_windowFrame->addComponent($this->bg);

        $this->bgTitle = new WidgetTitle($sizeX, $sizeY);
        $this->bgTitle->setId("minimizeButton");
        $this->_windowFrame->addComponent($this->bgTitle);

        $this->bgFirst = new Quad($sizeX, $sizeY);
        $this->bgFirst->setBgcolor("aaa5");
        $this->bgFirst->setAlign("center", "top");
        $this->_windowFrame->addComponent($this->bgFirst);

        $this->frame = new Frame();
        $this->frame->setAlign("left", "top");
        $this->frame->setLayout(new Column(-1));
        $this->_windowFrame->addComponent($this->frame);

        $this->layer = new myButton(5, 5);
        $this->layer->setIcon("UIConstruction_Buttons", "Down");
        $this->layer->setId("setLayer");
        $this->layer->setDescription("Switch from Race view to Score View(Visible on Tab)", 75);
        $this->addComponent($this->layer);

        $this->edgeWidget = new Script("Gui/Scripts/EdgeWidget");
        $this->registerScript($this->edgeWidget);

        parent::eXpOnBeginConstruct();
    }

    protected function getScript()
    {
        $script = new PlayerFinish();

        $recCount = 1000;
        $this->timeScript = $script;
        $this->timeScript->setParam("playerScores", "[]");
        $this->timeScript->setParam("nbScores", $recCount);
        $this->timeScript->setParam("nbFields", 20);
        $this->timeScript->setParam("nbFirstFields", 5);
        $this->timeScript->setParam('varName', 'LocalTime1');

        return $script;
    }

    public function setDirection($dir)
    {
        $this->setDisableAxis("x");
        if ($this->edgeWidget) {
            $this->unregisterScript($this->edgeWidget);
        }
        $this->trayWidget = new Script("Gui/Scripts/NewTray");
        $this->registerScript($this->trayWidget);
        $this->bgTitle->setDirection($dir);
    }

    protected function autoSetPositions()
    {
        parent::autoSetPositions();
        $nbFields = $this->getParameter('nbFields');
        $nbFieldsFirst = $this->getParameter('nbFirstFields');
        if ($nbFields != null && $nbFieldsFirst != null) {
            $this->setNbFields($nbFields);
            $this->setNbFirstFields($nbFieldsFirst);
        }
    }

    public function setNbFields($nb)
    {
        $this->timeScript->setParam("nbFields", $nb);
        $this->nbFields = $nb;
        $this->setSizeY(3 + $nb * 4);
    }

    public function setNbFirstFields($nb)
    {
        $this->timeScript->setParam("nbFirstFields", $nb);
        $this->firstNbFields = $nb;
        $this->bgFirst->setSize($this->sizeX / 1.5, 0.3);
        $this->bgFirst->setPosY((-4 * $nb) - 3);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->_windowFrame->setSize($this->sizeX, $this->sizeY);

        $this->bgFirst->setPosX(($this->sizeX / 2) + 1);

        $this->bg->setSize($this->sizeX, $this->sizeY + 1.5);

        $this->bgTitle->setSize($this->sizeX, $this->sizeY);

        $this->frame->setPosition(($this->sizeX / 2) + 1, -5.5);
        $this->layer->setPosition($this->sizeX - 6, -2);
    }

    public function update()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = array();
        $this->frame->clearComponents();

        $index = 1;
        $this->bgTitle->setText(eXpGetMessage('Current Points'));

        $recsData = "";
        $nickData = "";

        for ($index = 1; $index <= $this->nbFields; $index++) {
            $this->items[$index - 1] = new EnduroRecorditem($index, false);
            $this->frame->addComponent($this->items[$index - 1]);
        }

        $index = 1;
        foreach (Endurance::$enduro_total_points as $player_login => $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . Gui::fixString($player_login) . '"=>' . $record['points'];
            $nickData .= '"' . Gui::fixString($player_login) . '"=>"' . Gui::fixString($record['name']) . '"';
            $index++;
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        $this->timeScript->setParam("playerScores", $recsData);
        $this->timeScript->setParam("playerNicks", $nickData);
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }

        $this->items = array();

        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
