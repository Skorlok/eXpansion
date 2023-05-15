<?php

namespace ManiaLivePlugins\eXpansion\Votes\Gui\Widgets;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\Gauge;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\DicoLabel;

class VoteManagerWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    public static $parentPlugin;
    protected $script;
    protected $frame;
    protected $gauge;
    protected $timeLeft;

    protected function eXpOnBeginConstruct()
    {
        $this->setName("Vote Manager Widget");

        $bg = new WidgetBackGround(90, 20);
        $this->addComponent($bg);

        $title = new WidgetTitle(90, 8);
        $title->setText(eXpGetMessage('Current vote'));
        $this->addComponent($title);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setAlign("left", "top");
        $this->addComponent($this->frame);

        $this->gauge = new Gauge(90, 6);
        $this->gauge->setPosition(0, -14);
        $this->gauge->setGrading(1);
        $this->gauge->setStyle(Gauge::ProgressBarSmall);
        $this->gauge->setId("CountdownBar");
        $this->gauge->setDrawBg(true);
        $this->gauge->setDrawBlockBg(true);
        $this->gauge->setColorize("3D5");
        $this->frame->addComponent($this->gauge);

        $this->timeLeft = new Label();
        $this->timeLeft->setTextSize(1);
        $this->timeLeft->setTextColor('fff');
        $this->timeLeft->setAlign("center", "center");
        $this->timeLeft->setTextEmboss();
        $this->timeLeft->setPosition(45, -17, 5);
        $this->timeLeft->setId("CountdownText");
        $this->timeLeft->setScriptEvents();
        $this->frame->addComponent($this->timeLeft);

        $actionYes = $this->createAction(array(self::$parentPlugin, "handlePlayerVote"), "yes");
        $this->button_yes = new Button(14, 7);
        $this->button_yes->setAction($actionYes);
        $this->button_yes->setText("F1");
        $this->button_yes->colorize("0D0");
        $this->button_yes->setPosX(1);
        $this->button_yes->setPosY(-11);
        $this->button_yes->setId("button_yes");
        $this->addComponent($this->button_yes);

        $actionNo = $this->createAction(array(self::$parentPlugin, "handlePlayerVote"), "no");
        $this->button_no = new Button(14, 7);
        $this->button_no->setAction($actionNo);
        $this->button_no->setText("F2");
        $this->button_no->colorize("F00");
        $this->button_no->setPosX(77);
        $this->button_no->setPosY(-11);
        $this->button_no->setId("button_no");
        $this->addComponent($this->button_no);


        $this->quad_yes = new Quad(29.1, 6);
        $this->quad_yes->setAlign("left", "center2");
        $this->quad_yes->setPosX(16);
        $this->quad_yes->setPosY(-11);
        $this->quad_yes->setColorize("0D0");
        $this->quad_yes->setId("bgYes");
        $this->quad_yes->setStyle("Bgs1InRace");
        $this->quad_yes->setSubStyle('BgCard');
        $this->quad_yes->setHidden(1);
        $this->addComponent($this->quad_yes);

        $this->quad_yes2 = new Quad(29.1, 6);
        $this->quad_yes2->setAlign("left", "center2");
        $this->quad_yes2->setPosX(16);
        $this->quad_yes2->setPosY(-11);
        $this->quad_yes2->setColorize("0A08");
        $this->quad_yes2->setId("bgYes2");
        $this->quad_yes2->setStyle("Bgs1InRace");
        $this->quad_yes2->setSubStyle('BgColorContour');
        $this->quad_yes2->setHidden(1);
        $this->addComponent($this->quad_yes2);

        $this->quad_no = new Quad(29.01, 6);
        $this->quad_no->setAlign("right", "center2");
        $this->quad_no->setPosX(74);
        $this->quad_no->setPosY(-11);
        $this->quad_no->setColorize("F00");
        $this->quad_no->setId("bgNo");
        $this->quad_no->setStyle("Bgs1InRace");
        $this->quad_no->setSubStyle('BgCard');
        $this->quad_no->setHidden(1);
        $this->addComponent($this->quad_no);

        $this->quad_no2 = new Quad(29.01, 6);
        $this->quad_no2->setAlign("right", "center2");
        $this->quad_no2->setPosX(74);
        $this->quad_no2->setPosY(-11);
        $this->quad_no2->setColorize("C008");
        $this->quad_no2->setId("bgNo2");
        $this->quad_no2->setStyle("Bgs1InRace");
        $this->quad_no2->setSubStyle('BgColorContour');
        $this->quad_no2->setHidden(1);
        $this->addComponent($this->quad_no2);


        $this->yesButton = new Quad(5, 5);
        $this->yesButton->setPosX(12);
        $this->yesButton->setPosY(-11);
        $this->yesButton->setAlign("left", "center2");
        $this->yesButton->setStyle("Icons64x64_1");
        $this->yesButton->setSubStyle("ShowLeft2");
        $this->yesButton->setId('confirmVoteYes');
        $this->yesButton->setHidden(1);
        $this->addComponent($this->yesButton);

        $this->noButton = new Quad(5, 5);
        $this->noButton->setPosX(73);
        $this->noButton->setPosY(-11);
        $this->noButton->setAlign("left", "center2");
        $this->noButton->setStyle("Icons64x64_1");
        $this->noButton->setSubStyle("ShowRight2");
        $this->noButton->setId('confirmVoteNo');
        $this->noButton->setHidden(1);
        $this->addComponent($this->noButton);


        $this->quad_ratio = new Quad(0.5, 8);
        $this->quad_ratio->setAlign("left", "center2");
        $this->quad_ratio->setPosX(45);
        $this->quad_ratio->setPosY(-11);
        $this->quad_ratio->setPosZ(10);
        $this->quad_ratio->setColorize("000F");
        $this->quad_ratio->setId("bgRatio");
        $this->quad_ratio->setStyle("Bgs1InRace");
        $this->quad_ratio->setSubStyle('BgCard');
        $this->quad_ratio->setHidden(1);
        $this->addComponent($this->quad_ratio);

        $this->blabel = new DicoLabel(80, 9);
        $this->blabel->setId("textLabel");
        $this->blabel->setStyle("TextCardSmallScores2");
        $this->blabel->setTextSize(2);
        $this->blabel->setTextEmboss(true);
        $this->blabel->setAlign("center", "top");
        $this->blabel->setPosX(45);
        $this->blabel->setPosY(-3.5);
        $this->blabel->setHidden(1);
        $this->addComponent($this->blabel);

        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $this->script = new Script("Votes/Gui/ScriptTM");
            $this->script->setParam("actionYes", $actionYes);
            $this->script->setParam("actionNo", $actionNo);
            $this->registerScript($this->script);
        } else {
            $this->script = new Script("Votes/Gui/ScriptSM");
            $this->script->setParam("actionYes", $actionYes);
            $this->script->setParam("actionNo", $actionNo);
            $this->registerScript($this->script);
        }
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
    }

    public function setDatas($vote)
    {
        $voteData = "";
        $index = 1;
        foreach ($vote->playerVotes as $voteLogin => $voteValue) {
            if ($index > 1) {
                $voteData .= ', ';
            }
            $voteData .= '"' . $voteLogin . '"=>"' . $voteValue . '"';
            $index++;
        }

        if (empty($voteData)) {
            $voteData = 'Text[Text]';
        } else {
            $voteData = '[' . $voteData . ']';
        }

        $this->script->setParam("countdown", $vote->votingTime);
        $this->script->setParam("startTime", $vote->timestamp);
        $this->script->setParam("votes", $voteData);
        $this->script->setParam("ratio", sprintf("%0.1f", ($vote->voteRatio * 58) + 16));
        $this->script->setParam("voteText", $vote->voteText);
        $this->script->setParam("voters", $vote->voters);
    }
}
