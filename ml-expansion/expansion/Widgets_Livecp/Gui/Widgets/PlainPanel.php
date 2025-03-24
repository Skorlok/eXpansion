<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Livecp\Gui\Widgets;

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
use ManiaLivePlugins\eXpansion\Widgets_Livecp\Gui\Scripts\PlayerFinish;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

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
    protected $nbFields = 10;
    protected $firstNbFields = 3;
    public $trayWidget;

    protected function eXpOnBeginConstruct()
    {
        $this->setScriptEvents();
        $this->storage = Storage::getInstance();
        $this->setName("CpLive Widget");
        $this->registerScript($this->getScript());
        parent::eXpOnBeginConstruct();
    }

    protected function getScript()
    {
        $script = new PlayerFinish();

        $this->timeScript = $script;
        $this->timeScript->setParam("playerTimes", "[]");
        $this->timeScript->setParam("nbRecord", 510);
        $this->timeScript->setParam("nbFields", 10);
        $this->timeScript->setParam("nbFirstFields", 3);
        $this->timeScript->setParam('varName', 'LocalTime1');

        return $script;
    }

    public function setNbFields($nb)
    {
        $this->timeScript->setParam("nbFields", $nb);
        $this->nbFields = $nb;
    }

    public function setNbFirstFields($nb)
    {
        $this->timeScript->setParam("nbFirstFields", $nb);
        $this->firstNbFields = $nb;
    }

    public function update($gamemode, $map, $ScriptSettings)
    {
        $sizeX = 42;
        $sizeY = 51;

        $windowFrame = new Frame();
        $windowFrame->setAlign("left", "top");
        $windowFrame->setId("Frame");
        $windowFrame->setScriptEvents(true);
        $windowFrame->setSize($sizeX, $sizeY);
        $this->addComponent($windowFrame);

        $bg = new WidgetBackGround($sizeX, (3 + $this->nbFields * 4) + 1.5);
        $windowFrame->addComponent($bg);

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
                <label id="item_1" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Put On TAB View"/>
            </frame>
        
            <frame posn="0 -5 2.0E-5" scriptevents="1">
                <quad id="mQuad_2" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                <label id="item_2" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Rectract Widget"/>
            </frame>
        </frame>');
        $this->addComponent($menu);


        for ($index = 1; $index <= $this->nbFields; $index++) {
            $this->items[$index - 1] = new \ManiaLive\Gui\Elements\Xml();
            $this->items[$index - 1]->setContent('<frame posn="-20.5 '. - $index*4.0 + 4.0 . ' 0">
            <quad id="RecBgBlink_' . $index . '" posn="-1.5 1.5 0" sizen="42 3.5" halign="left" valign="top" bgcolor="0f0" opacity="0.25" hidden="1"/>
            <label id="RecRank_' . $index . '" posn="3 1.5 0" sizen="5 5" halign="right" valign="top" style="TextRaceChat" textsize="1" hidden="0"/>
            <label id="RecCpNumber_' . $index . '" posn="3.5 1.5 0" sizen="5 5" style="TextRaceChat" textsize="1" textcolor="f90" hidden="0"/>
            <label id="RecTime_' . $index . '" posn="10.5 1.5 0" sizen="10 5" style="TextRaceChat" textsize="1" textcolor="3af" hidden="0"/>
            <label id="RecNick_' . $index . '" posn="21.5 1.5 0" sizen="19 5" style="TextRaceChat" textsize="1" textcolor="fff" hidden="0"/>
            <gauge id="RecRatio_' . $index . '" sizen="40 6" drawblockbg="1" style="EnergyBar" drawbg="0" rotation="0" posn="1.5 1.5" grading="1" centered="0" hidden="1"/>
            </frame>');
            $frame->addComponent($this->items[$index - 1]);
        }


        $totalCp = 0;

        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP) {
            if ($map->lapRace) {
                if (array_key_exists("S_ForceLapsNb", $ScriptSettings)) {
                    if ($ScriptSettings['S_ForceLapsNb'] > 0) {
                        $totalCp = $map->nbCheckpoints * $ScriptSettings['S_ForceLapsNb'];
                    } else {
                        $totalCp = $map->nbCheckpoints * $map->nbLaps;
                    }
                } else {
                    $totalCp = $map->nbCheckpoints * $map->nbLaps;
                }
            } else {
                $totalCp = $map->nbCheckpoints;
            }
        } else {
            $totalCp = $map->nbCheckpoints;
        }

        $this->timeScript->setParam("totalCp", $totalCp);

        $bgTitle = new WidgetTitle($sizeX, $sizeY, "LiveCP - Total CP: " . $totalCp, "minimizeButton");
        $windowFrame->addComponent($bgTitle);
    }
}
