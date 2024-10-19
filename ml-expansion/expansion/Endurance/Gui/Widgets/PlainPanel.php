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
    /**
     * @var Control[]
     */
    protected $items = array();

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

    protected function eXpOnBeginConstruct()
    {
        $this->setScriptEvents();
        $this->storage = Storage::getInstance();
        $this->setName("Enduro Panel");
        $this->registerScript($this->getScript());
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
    }

    public function update()
    {
        $sizeX = 42;
        $sizeY = 95;

        $windowFrame = new Frame();
        $windowFrame->setAlign("left", "top");
        $windowFrame->setId("Frame");
        $windowFrame->setScriptEvents(true);
        $windowFrame->setSize($sizeX, $sizeY);
        $this->addComponent($windowFrame);

        $bg = new WidgetBackGround($sizeX, (3 + $this->nbFields * 4) + 1.5, \ManiaLivePlugins\eXpansion\Endurance\Endurance::$openScoresAction);
        $windowFrame->addComponent($bg);

        $bgTitle = new WidgetTitle($sizeX, $sizeY, eXpGetMessage('Current Points'), "minimizeButton");
        $windowFrame->addComponent($bgTitle);

        $bgFirst = new Quad($sizeX, $sizeY);
        $bgFirst->setBgcolor("aaa5");
        $bgFirst->setAlign("center", "top");
        $bgFirst->setPosX(($sizeX / 2) + 1);
        $bgFirst->setPosY((-4 * $this->firstNbFields) - 3);
        $bgFirst->setSize($this->sizeX / 1.5, 0.3);
        $windowFrame->addComponent($bgFirst);

        $frame = new Frame();
        $frame->setAlign("left", "top");
        $frame->setLayout(new Column(-1));
        $frame->setPosition(($sizeX / 2) + 1, -5.5);
        $windowFrame->addComponent($frame);

        $this->layer = new myButton(5, 5);
        $this->layer->setIcon("UIConstruction_Buttons", "Down");
        $this->layer->setId("toggleMicroMenu");
        $this->layer->setDescription("Switch from Race view to Score View(Visible on Tab)", 75);
        $this->layer->setPosY(-1.7);
        $this->addComponent($this->layer);

        $this->trayWidget = new Script("Gui/Scripts/NewTray");
        $this->registerScript($this->trayWidget);
        $this->trayWidget->setParam("sizeX", $sizeX);
        $this->trayWidget->setParam("sizeY", 3 + $this->nbFields * 4);

        $this->setSizeX($sizeX);
        $this->setSizeY(3 + $this->nbFields * 4);


        $guiConfig = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();
        $menu = new \ManiaLive\Gui\Elements\Xml();
        $menu->setContent('<frame id="MicroMenu">
            <frame scriptevents="1">
                <quad id="mQuad_1" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                <label id="item_1" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Show Differences"/>
            </frame>
        
            <frame posn="0 -5 2.0E-5" scriptevents="1">
                <quad id="mQuad_2" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                <label id="item_2" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Rectract Widget"/>
            </frame>
        
            <frame posn="0 -10 4.0E-5" scriptevents="1">
                <quad id="mQuad_3" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                <label id="item_3" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Put On TAB View"/>
            </frame>
        </frame>');
        $this->addComponent($menu);




        $index = 1;

        $recsData = "";
        $nickData = "";

        for ($index = 1; $index <= $this->nbFields; $index++) {
            $this->items[$index - 1] = new EnduroRecorditem($index, false);
            $frame->addComponent($this->items[$index - 1]);
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
}