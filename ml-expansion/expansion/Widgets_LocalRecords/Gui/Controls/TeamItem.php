<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Controls;

class TeamItem extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $bg;
    protected $bg2;
    protected $nick;
    protected $label;
    protected $time;
    protected $frame;

    public function __construct()
    {
        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

        $sizeX = 40;
        $sizeY = 4;

        $this->label = new \ManiaLib\Gui\Elements\Label(12, 4);
        $this->label->setAlign('right', 'center');
        $this->label->setPosition(12, 0);
        $this->label->setStyle("TextRaceChat");
        $this->label->setTextSize(1);
        if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
            $this->label->setText($var["team1Name"] . ' : ');
            $this->label->setTextColor($var["team1Color"]);
        } else {
            $this->label->setText('$wBlue Team : ');
            $this->label->setTextColor('00f');
        }
        $this->addComponent($this->label);

        $this->label = new \ManiaLib\Gui\Elements\Label(4, 4);
        $this->label->setAlign('right', 'center');
        $this->label->setPosition(17, 0);
        $this->label->setStyle("TextRaceChat");
        $this->label->setId("bluePoints");
        $this->label->setTextSize(1);
        $this->label->setTextColor('fff');
        $this->label->setScriptEvents(1);
        $this->addComponent($this->label);

        $this->label = new \ManiaLib\Gui\Elements\Label(12, 4);
        $this->label->setAlign('right', 'center');
        $this->label->setPosition(32, 0);
        $this->label->setStyle("TextRaceChat");
        $this->label->setTextSize(1);
        if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
            $this->label->setText($var["team2Name"] . ' : ');
            $this->label->setTextColor($var["team2Color"]);
        } else {
            $this->label->setText('$wRed Team : ');
            $this->label->setTextColor('f00');
        }
        $this->addComponent($this->label);

        $this->label = new \ManiaLib\Gui\Elements\Label(4, 4);
        $this->label->setAlign('right', 'center');
        $this->label->setPosition(36, 0);
        $this->label->setStyle("TextRaceChat");
        $this->label->setId("redPoints");
        $this->label->setTextSize(1);
        $this->label->setTextColor('fff');
        $this->label->setScriptEvents(1);
        $this->addComponent($this->label);

        $this->setSize($sizeX, $sizeY);
        $this->setAlign("center", "top");
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
