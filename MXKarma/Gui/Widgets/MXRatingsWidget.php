<?php

namespace ManiaLivePlugins\eXpansion\MXKarma\Gui\Widgets;

class MXRatingsWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{

    protected $frame;
    protected $starFrame;
    protected $move;
    protected $gauge;

    protected $stars = array();

    public static $parentPlugin;

    protected function eXpOnBeginConstruct()
    {
        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setAlign("left", "top");
        $this->addComponent($this->frame);

        $bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround(34, 10);
        $this->addComponent($bg);

        $title = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle(34, 4);
        $title->setText(eXpGetMessage('MX Map Rating'));
        $this->addComponent($title);

        $this->starFrame = new \ManiaLive\Gui\Controls\Frame();
        $this->starFrame->setPosition(2, -2);
        $this->starFrame->setSize(34, 4);
        $this->frame->addComponent($this->starFrame);
        $this->gauge = new \ManiaLive\Gui\Elements\Xml();

        $this->setName("MX Map Rating Widget");

        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();
        if ($storage->simpleEnviTitle == "TM") {
            $this->edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($this->edgeWidget);
        }
    }

    public function setRating($number, $total)
    {
        $this->frame->clearComponents();

        $test = $number;
        $color = "fff";
        if ($test < 30) {
            $color = "0ad";
        }
        if ($test >= 30) {
            $color = "2af";
        }
        if ($test > 60) {
            $color = "0cf";
        }

        $this->gauge->setContent(
            '<gauge sizen="32 7" drawblockbg="1" style="ProgressBarSmall" color="'
            . $color . '" drawbg="1" rotation="0" posn="0 -3.5" grading="1" ratio="'
            . ($number / 100) . '" centered="0" />'
        );
        $this->frame->addComponent($this->gauge);

        $info = new \ManiaLib\Gui\Elements\Label();
        $info->setTextSize(1);
        $info->setTextColor('fff');
        $info->setAlign("center", "center");
        $info->setTextEmboss();
        $info->setText(round($number, 1) . "% (" . $total . ")");
        $info->setPosition(17, -7, 10);
        $this->frame->addComponent($info);
        $this->redraw();
    }
}
