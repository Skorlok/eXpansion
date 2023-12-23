<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TM_Obstacle\Gui\Widgets;

use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

class CpProgress extends Widget
{

    protected $frame;

    /** @var Storage; */
    protected $storage;
    protected $edgeWidget;

    protected function eXpOnBeginConstruct()
    {
        $this->setName("Obstacle progress Widget");
        $this->storage = Storage::getInstance();

        $this->frame = new Frame();
        $this->frame->setLayout(new Column());
        $this->addComponent($this->frame);

        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $this->edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($this->edgeWidget);
        }

        for ($x = 0; $x < 10; $x++) {
            $part = new \ManiaLive\Gui\Elements\Xml();
            $part->setContent('<frame posn="0 ' . $x*-6 . ' 0">
                <label id="player_' . $x . '" posn="30 0 0" sizen="30 9" halign="right" valign="top" style="TextStaticSmall" text="player_0"/>
                <gauge id="gauge_' . $x . '" posn="30 -2 1.0E-5" sizen="30 9" halign="left" valign="center2" style="EnergyBar" color="2f2" grading="1" ratio="0" drawbg="0" drawblockbg="1"/>
                <label id="cp_' . $x . '" posn="60 0 2.0E-5" sizen="10 9" halign="left" valign="top" style="TextStaticSmall" text="1"/>
                </frame>');
            $this->frame->addComponent($part);
        }

        $script = new Script("Widgets_TM_Obstacle\Gui\Scripts_Infos");
        $script->setParam("playerCount", $x);
        $script->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
        $script->setParam("serverLogin", $this->storage->serverLogin);
        $this->registerScript($script);
    }

    public function destroy()
    {
        $this->destroyComponents();
        $this->storage = null;
        parent::destroy();
    }
}
