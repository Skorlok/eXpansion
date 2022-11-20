<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Gui\Windows;

use ManiaLivePlugins\eXpansion\Endurance\Gui\Controls\EnduroScoreItem;
use ManiaLivePlugins\eXpansion\Gui\Gui;

class EnduroScores extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $frame;
    protected $label_place;
    protected $label_score;
    protected $label_nickname;
    protected $label_login;
    protected $pager;
    protected $items = array();

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize(100, 6);
        $this->frame->setPosY(0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->mainFrame->addComponent($this->frame);

        $this->label_place = new \ManiaLib\Gui\Elements\Label(20, 6);
        $this->label_place->setAlign('left', 'center');
        $this->frame->addComponent($this->label_place);

        $this->label_score = new \ManiaLib\Gui\Elements\Label(20, 6);
        $this->label_score->setAlign('left', 'center');
        $this->frame->addComponent($this->label_score);

        $this->label_nickname = new \ManiaLib\Gui\Elements\Label(40, 6);
        $this->label_nickname->setAlign('left', 'center');
        $this->frame->addComponent($this->label_nickname);

        $this->label_login = new \ManiaLib\Gui\Elements\Label(40, 6);
        $this->label_login->setAlign('left', 'center');
        $this->frame->addComponent($this->label_login);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        $this->label_place->setSizeX(27);
        $this->label_score->setSizeX(42);
        $this->label_nickname->setSizeX(63);
        $this->label_login->setSizeX(40);

        $this->pager->setSize(170, 88);
        foreach ($this->items as $item) {
            $item->setSizeX(165);
        }
    }

    public function onShow()
    {
        $this->label_place->setText(__("Rank"));
        $this->label_score->setText(__("Score"));
        $this->label_nickname->setText(__("Nickname"));
        $this->label_login->setText(__("Login"));
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
        $x = 0;
        foreach ($recs as $rec_login => $rec) {
            $this->items[$x] = new EnduroScoreItem($x, $rec_login, $rec['name'], $rec['points']);
            $this->pager->addItem($this->items[$x]);
            $x++;
            if ($x >= 520) {
                break;
            }
        }
    }
}
