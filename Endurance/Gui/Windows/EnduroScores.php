<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Gui\Windows;

use ManiaLivePlugins\eXpansion\Endurance\Gui\Controls\EnduroScoreItem;
use ManiaLivePlugins\eXpansion\Gui\Gui;

class EnduroScores extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $frame;
    protected $label_place;
    protected $label_nickname;
    protected $label_score;
    protected $label_login;
    protected $label_cp;
    protected $pager;
    protected $items = array();

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

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

        $this->label_cp = new \ManiaLib\Gui\Elements\Label(20, 6);
        $this->label_cp->setAlign('left', 'center');
        $this->frame->addComponent($this->label_cp);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        $this->label_place->setSizeX(27);
        $this->label_score->setSizeX(40);
        $this->label_nickname->setSizeX(50);
        $this->label_login->setSizeX(40);
        $this->label_cp->setSizeX(27);

        $this->pager->setSize(200, 88);
        foreach ($this->items as $item) {
            $item->setSizeX(195);
        }
    }

    public function onShow()
    {
        $this->label_place->setText(__("Rank"));
        $this->label_score->setText(__("Score"));
        $this->label_nickname->setText(__("Nickname"));
        $this->label_login->setText(__("Login"));
        $this->label_cp->setText(__("Current ckeckpoint"));
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

    public function populateList($recs, $players_cp_index)
    {
        $x = 0;
        $login = $this->getRecipient();

        foreach ($recs as $rec_login => $rec) {
            
            if (array_key_exists($rec_login, $players_cp_index)) {
                $player_cp = $players_cp_index[$rec_login];
            } else {
                $player_cp = -1;
            }
            
            $this->items[$x] = new EnduroScoreItem($x, $rec_login, $rec['name'], $rec['points'], $player_cp);
            $this->pager->addItem($this->items[$x]);
            $x++;
            if ($x >= 520) {
                break;
            }
        }
    }
}
