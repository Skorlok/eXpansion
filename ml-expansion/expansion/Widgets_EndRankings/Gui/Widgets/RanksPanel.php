<?php

namespace ManiaLivePlugins\eXpansion\Widgets_EndRankings\Gui\Widgets;

class RanksPanel extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{

    protected $frame;
    protected $items = array();
    protected $bg;
    protected $bgTitle;
    protected $quad;
    protected $lbl;

    protected function onConstruct()
    {

        $sizeX = 38;
        $sizeY = 95;

        $this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround(38, 95);
        $this->addComponent($this->bg);

        $this->bgTitle = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle($sizeX, $sizeY);
        $this->bgTitle->setText(eXpGetMessage("Server Ranks"));
        $this->addComponent($this->bgTitle);

        $this->frame = new \ManiaLive\Gui\Controls\Frame(4, -5);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column(-1));
        $this->addComponent($this->frame);

        $this->setName("Server Ranks");
        parent::onConstruct();
    }

    public function onResize($oldX, $oldY)
    {

        $this->bg->setSize($this->sizeX, $this->sizeY);
        $this->bgTitle->setSize($this->sizeX, 4.2);

        parent::onResize($oldX, $oldY);
    }

    public function setData($ranks)
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = array();
        $this->frame->clearComponents();

        $x = 0;
        foreach ($ranks as $rank) {
            $this->items[$x] = new \ManiaLivePlugins\eXpansion\Widgets_EndRankings\Gui\Controls\RankItem($x, $rank);
            $this->frame->addComponent($this->items[$x]);
            $x++;
            if ($x == 30) {
                break;
            }
        }
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = array();
        $this->destroyComponents();

        parent::destroy();
    }
}
