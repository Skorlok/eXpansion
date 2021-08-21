<?php

namespace ManiaLivePlugins\eXpansion\Dedimania\Gui\Controls;

use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;

/**
 * Description of RecItem
 *
 * @author oliverde8
 */
class CpDiffItem extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    protected $label_rank;
    protected $label_nick;
    protected $label_score;
    protected $label_avgScore;
    protected $label_nbFinish;
    protected $label_login;
    protected $bg;
    protected $button_report;
    protected $widths;

    public function __construct($indexNumber, $login, $record, $widths) {
        $this->widths = $widths;
        $this->sizeY = 6;
        $this->bg = new ListBackGround($indexNumber, 100, 6);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize(100, 6);
        $this->frame->setPosY(0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->addComponent($this->frame);

        $this->label_CP = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_CP->setAlign('left', 'center');
        $this->label_CP->setScale(1);
        $this->label_CP->setText(($indexNumber + 1) . ".");
        $this->frame->addComponent($this->label_CP);

        $this->label_player = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_player->setAlign('left', 'center');
        $this->label_player->setScale(1);
        $this->label_player->setText(\ManiaLive\Utilities\Time::fromTM($record[0]->ScoreCheckpoints[$indexNumber]));
        $this->frame->addComponent($this->label_player);

        $this->label_target = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_target->setAlign('left', 'center');
        $this->label_target->setScale(1);
        $this->label_target->setText(\ManiaLive\Utilities\Time::fromTM($record[1]->ScoreCheckpoints[$indexNumber]));
        $this->frame->addComponent($this->label_target);

        $this->label_diff = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_diff->setAlign('left', 'center');
        $this->label_diff->setScale(1);
        $cp_Diff = $record[0]->ScoreCheckpoints[$indexNumber] - $record[1]->ScoreCheckpoints[$indexNumber];
        if ($cp_Diff <= 0) {
            $cp_Diff = \ManiaLive\Utilities\Time::fromTM($cp_Diff);
            $cp_Diff = '$0f0- ' . $cp_Diff;
        } else {
            $cp_Diff = \ManiaLive\Utilities\Time::fromTM($cp_Diff);
            $cp_Diff = '$f00+ ' . $cp_Diff;
        }
        $this->label_diff->setText($cp_Diff);
        $this->frame->addComponent($this->label_diff);
    }

    public function onResize($oldX, $oldY)
    {
        $scaledSizes = Gui::getScaledSize($this->widths, ($this->getSizeX()) - 5);
        $this->bg->setSizeX($this->getSizeX() - 5);
        $this->label_CP->setSizeX($scaledSizes[0]);
        $this->label_player->setSizeX($scaledSizes[1]);
        $this->label_target->setSizeX($scaledSizes[2]);
        $this->label_diff->setSizeX($scaledSizes[3]);
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }

    /**
     * custom function to remove contents.
     */
    public function erase()
    {
        parent::destroy();
    }
}
