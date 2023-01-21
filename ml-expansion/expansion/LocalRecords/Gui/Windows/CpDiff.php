<?php

namespace ManiaLivePlugins\eXpansion\LocalRecords\Gui\Windows;

use ManiaLivePlugins\eXpansion\LocalRecords\Gui\Controls\CpDiffItem;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\LocalRecords\LocalRecords;

class CpDiff extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $frame;
    protected $label_CP;
    protected $label_player;
    protected $label_target;
    protected $label_diff;
    protected $label_diff_cp;
    protected $widths = array(1, 4, 4, 4, 4);
    protected $pager;
    protected $items = array();
    protected $label_visit;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $sizeX = 100;
        $scaledSizes = Gui::getScaledSize($this->widths, $sizeX);

        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);


        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, 6);
        $this->frame->setPosY(0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->mainFrame->addComponent($this->frame);

        $this->label_CP = new \ManiaLib\Gui\Elements\Label($scaledSizes[0], 6);
        $this->label_CP->setAlign('left', 'center');
        $this->frame->addComponent($this->label_CP);

        $this->label_player = new \ManiaLib\Gui\Elements\Label($scaledSizes[1], 6);
        $this->label_player->setAlign('left', 'center');
        $this->frame->addComponent($this->label_player);

        $this->label_target = new \ManiaLib\Gui\Elements\Label($scaledSizes[2], 6);
        $this->label_target->setAlign('left', 'center');
        $this->frame->addComponent($this->label_target);

        $this->label_diff = new \ManiaLib\Gui\Elements\Label($scaledSizes[3], 6);
        $this->label_diff->setAlign('left', 'center');
        $this->frame->addComponent($this->label_diff);

        $this->label_diff_cp = new \ManiaLib\Gui\Elements\Label($scaledSizes[4], 6);
        $this->label_diff_cp->setAlign('left', 'center');
        $this->frame->addComponent($this->label_diff_cp);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $scaledSizes = Gui::getScaledSize($this->widths, ($this->getSizeX()) - 5);

        $this->label_CP->setSizeX($scaledSizes[0]);
        $this->label_player->setSizeX($scaledSizes[1]);
        $this->label_target->setSizeX($scaledSizes[2]);
        $this->label_diff->setSizeX($scaledSizes[3]);
        $this->label_diff_cp->setSizeX($scaledSizes[4]);

        $this->pager->setSize($this->getSizeX(), $this->getSizeY() - 12);
        foreach ($this->items as $item) {
            $item->setSizeX($this->getSizeX());
        }
    }

    public function onShow()
    {
        $this->label_CP->setText(__("CP"));
        $this->label_diff->setText(__("Difference"));
        $this->label_diff_cp->setText(__("Difference / CP"));
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->items = null;
        $this->pager->destroy();
        $this->destroyComponents();
        parent::destroy();
    }

    public function populateList($recs)
    {
        $this->label_player->setText(__("#" . $recs[0]->place . ": ". $recs[0]->nickName));
        $this->label_target->setText(__("#" . $recs[1]->place . ": ". $recs[1]->nickName));
        $x = 0;
        $login = $this->getRecipient();

        while ($x < sizeof($recs[0]->ScoreCheckpoints)) {
            $this->items[$x] = new CpDiffItem($x, $login, $recs, $this->widths);
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }
}
